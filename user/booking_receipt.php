<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login(['manager', 'staff']);

$currentUser = current_user();
$bookingId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$bookingId) {
    header('Location: ' . ($currentUser['role'] === 'manager' ? 'admin_dashboard.php' : 'staff_dashboard.php'));
    exit;
}

$stmt = $pdo->prepare(
    'SELECT b.*, c.full_name, c.phone, c.plate_num
     FROM booking b JOIN customer c ON c.customer_id = b.customer_id
     WHERE b.booking_id = :id'
);
$stmt->execute(['id' => $bookingId]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: ' . ($currentUser['role'] === 'manager' ? 'admin_dashboard.php' : 'staff_dashboard.php'));
    exit;
}

$stmt = $pdo->prepare(
    'SELECT bi.quantity, bi.price, a.accommodation_name, a.accommodation_type,
            a.price AS nightly_price, a.price_weekend AS nightly_price_weekend
     FROM booking_item bi
     JOIN accommodation a ON a.accommodation_id = bi.accommodation_id
     WHERE bi.booking_id = :id'
);
$stmt->execute(['id' => $bookingId]);
$items = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT deposit_paid FROM payment WHERE booking_id = :id AND payment_status = 'paid' ORDER BY payment_id DESC LIMIT 1");
$stmt->execute(['id' => $bookingId]);
$amountPaid = (float) ($stmt->fetchColumn() ?: 0);

$checkIn = new DateTime($booking['check_in']);
$checkOut = new DateTime($booking['check_out']);
$nights = max(1, $checkOut->diff($checkIn)->days);
$stay = compute_stay_price(1, 1, $checkIn, $checkOut);

$backUrl = $currentUser['role'] === 'manager' ? 'admin_dashboard.php' : 'staff_dashboard.php';

require __DIR__ . '/views/booking_receipt.view.php';
