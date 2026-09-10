<?php
/**
 * Halaman pembayaran.
 * Fail ini mengendalikan langkah pembayaran deposit bagi satu tempahan dan sahkan transaksi tersebut.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$bookingId = filter_var($_POST['booking_id'] ?? $_GET['booking_id'] ?? '', FILTER_VALIDATE_INT); // boleh datang dari URL atau borang
$booking = null;
$items = [];
$errors = [];
$paid = false;

if ($bookingId !== false) {
    $stmt = $pdo->prepare(
        'SELECT b.*, c.full_name, c.phone
         FROM booking b JOIN customer c ON c.customer_id = b.customer_id
         WHERE b.booking_id = :id'
    );
    $stmt->execute(['id' => $bookingId]);
    $booking = $stmt->fetch();

    if ($booking) {
        $stmt = $pdo->prepare(
            'SELECT bi.price, a.accommodation_name, a.accommodation_type
             FROM booking_item bi
             JOIN accommodation a ON a.accommodation_id = bi.accommodation_id
             WHERE bi.booking_id = :id'
        );
        $stmt->execute(['id' => $bookingId]);
        $items = $stmt->fetchAll();

        // check dulu — elak pelanggan bayar dua kali untuk tempahan yang sama
        $stmt = $pdo->prepare(
            "SELECT payment_id FROM payment WHERE booking_id = :id AND payment_status = 'paid' LIMIT 1"
        );
        $stmt->execute(['id' => $bookingId]);
        $paid = (bool) $stmt->fetch();
    }
}

// page ni cuma untuk pelanggan PILIH kaedah bayaran (online banking) — rekod
// pembayaran sebenar jadi kemudian dalam payment_method.php / process_payment.php
if ($booking && !$paid && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $method = $_POST['method'] ?? '';
    if (!in_array($method, ['toyyibpay', 'qr'], true)) {
        $errors[] = 'Please choose a payment method.';
    }

    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please try again.';
    }

    if (!$errors) {
        // hantar terus ke page seterusnya, bawa sekali kaedah yang dia pilih
        header('Location: payment_method.php?booking_id=' . $bookingId . '&method=' . urlencode($method));
        exit;
    }
}

$base = '../'; // page ni dalam folder customer/, naik satu tahap untuk pergi root
$active = ''; // takde menu navbar yang perlu di-highlight untuk page ni
$pageTitle = 'Payment — Casadive Villa';
$pageCss = 'style/payment.css';

// semua logic dah selesai kat atas ni — baris bawah papar HTML page dia.
// HTML/borang tu disimpan berasingan dalam folder views/ supaya file ni tak jadi terlalu panjang.
require __DIR__ . '/views/payment.view.php';
