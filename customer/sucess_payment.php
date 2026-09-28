<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$methodLabels = ['qr' => 'QR / DuitNow', 'online_banking' => 'FPX Online Banking', 'toyyibpay' => 'ToyyibPay'];

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
        $payment = $stmt->fetch();
    }
}

$base = '../';
$active = '';
$pageTitle = 'Payment Successful — Casadive Villa';
$pageCss = 'style/sucess_payment.css';

require __DIR__ . '/views/sucess_payment.view.php';
