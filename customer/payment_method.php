<?php
/**
 * Halaman pengesahan pembayaran.
 * Paparkan tempahan dan kaedah pembayaran yang dipilih pada payment.php untuk semakan
 * sebelum deposit benar-benar dicaj dalam process_payment.php.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$allowedBanks = ['Bank Islam', 'Maybank', 'CIMB Bank', 'Public Bank', 'RHB Bank', 'Hong Leong Bank'];
$methodLabels = ['qr' => 'QR / DuitNow', 'online_banking' => 'FPX Online Banking'];

$bookingId = filter_var($_POST['booking_id'] ?? $_GET['booking_id'] ?? '', FILTER_VALIDATE_INT);
$method = $_POST['method'] ?? $_GET['method'] ?? '';
$bank = trim((string) ($_POST['bank'] ?? $_GET['bank'] ?? ''));
$booking = null;
$items = [];
$errors = [];
$paid = false;
$nights = 0;

// value-value ni datang dari URL yang payment.php bina, tapi kita check balik sini —
// jangan sekali percaya value tu betul just sebab dia sampai melalui redirect "Location:"
if (!in_array($method, ['qr', 'online_banking'], true)) {
    $method = '';
}
if ($method === 'online_banking') {
    if (!in_array($bank, $allowedBanks, true)) {
        $bank = '';
    }
} else {
    $bank = ''; // QR takyah pilih bank, so kosongkan je
}

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
            'SELECT bi.price, a.accommodation_name
             FROM booking_item bi
             JOIN accommodation a ON a.accommodation_id = bi.accommodation_id
             WHERE bi.booking_id = :id'
        );
        $stmt->execute(['id' => $bookingId]);
        $items = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            "SELECT payment_id FROM payment WHERE booking_id = :id AND payment_status = 'paid' LIMIT 1"
        );
        $stmt->execute(['id' => $bookingId]);
        $paid = (bool) $stmt->fetch();

        $checkIn = DateTime::createFromFormat('Y-m-d', (string) $booking['check_in']);
        $checkOut = DateTime::createFromFormat('Y-m-d', (string) $booking['check_out']);
        if ($checkIn && $checkOut) {
            $nights = (int) $checkIn->diff($checkOut)->days; // untuk papar "X night(s)" kat summary
        }
    }
}

// pelanggan kena tick "I confirm" dulu sebelum kita teruskan cas deposit —
// page ni sendiri tak sentuh database, cuma redirect ke process_payment.php
if ($booking && !$paid && $method !== '' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please try again.';
    }
    if (!$errors && !($_POST['confirm'] ?? false)) {
        $errors[] = 'Please confirm the payment details before proceeding.';
    }

    if (!$errors) {
        header('Location: process_payment.php?booking_id=' . $bookingId . '&method=' . urlencode($method) . '&bank=' . urlencode($bank));
        exit;
    }
}

$base = '../'; // page ni dalam folder customer/, naik satu tahap untuk pergi root
$active = ''; // takde menu navbar yang perlu di-highlight untuk page ni
$pageTitle = 'Payment Confirmation — Casadive Villa';
$pageCss = 'style/payment_method.css';

// semua logic dah selesai kat atas ni — baris bawah papar HTML page dia.
// HTML/borang tu disimpan berasingan dalam folder views/ supaya file ni tak jadi terlalu panjang.
require __DIR__ . '/views/payment_method.view.php';
