<?php
/**
 * Halaman berjaya / selesai.
 * Sahkan bahawa deposit sesuatu tempahan telah dibayar dan papar butiran akhir.
 * Ini adalah penghujung aliran pembayaran (payment.php -> payment_method.php -> process_payment.php -> sini).
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$methodLabels = ['qr' => 'QR / DuitNow', 'online_banking' => 'FPX Online Banking', 'toyyibpay' => 'ToyyibPay'];

// carian guna "no rujukan + no phone" sama macam mybooking.php — ni elak orang lain
// tengok booking orang lain just dengan teka-teka ID kat URL
$bookingId = parse_booking_ref((string) ($_GET['ref'] ?? $_GET['booking_id'] ?? ''));
$phone = trim((string) ($_GET['phone'] ?? ''));
$booking = null;
$items = [];
$payment = null;

if ($bookingId !== false && $phone !== '') {
    $stmt = $pdo->prepare(
        'SELECT b.*, c.full_name, c.phone
         FROM booking b JOIN customer c ON c.customer_id = b.customer_id
         WHERE b.booking_id = :id AND c.phone = :phone'
    );
    $stmt->execute(['id' => $bookingId, 'phone' => $phone]);
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
            "SELECT payment_method, deposit_paid FROM payment
             WHERE booking_id = :id AND payment_status = 'paid'
             ORDER BY payment_id DESC LIMIT 1"
        );
        $stmt->execute(['id' => $bookingId]);
        $payment = $stmt->fetch(); // ambil rekod payment terbaru untuk booking ni
    }
}

$base = '../'; // page ni dalam folder customer/, naik satu tahap untuk pergi root
$active = ''; // takde menu navbar yang perlu di-highlight untuk page ni
$pageTitle = 'Payment Successful — Casadive Villa';
$pageCss = 'style/sucess_payment.css';

// semua logic dah selesai kat atas ni — baris bawah papar HTML page dia.
// HTML/borang tu disimpan berasingan dalam folder views/ supaya file ni tak jadi terlalu panjang.
require __DIR__ . '/views/sucess_payment.view.php';
