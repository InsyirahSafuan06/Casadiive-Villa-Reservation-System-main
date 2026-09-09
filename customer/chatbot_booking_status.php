<?php
/**
 * Endpoint JSON untuk AI Assistant — semak status booking.
 * Sama trust model macam mybooking.php: booking reference + phone number yang digunakan
 * masa booking jadi "password" ringkas. Tak match langsung (id salah ATAU phone salah),
 * kita bagi respons yang SAMA — supaya orang tak boleh teka wujud tak booking ID tu.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');

$ref = filter_input(INPUT_GET, 'ref', FILTER_VALIDATE_INT);
$phone = trim((string) ($_GET['phone'] ?? ''));

if ($ref === false || $ref === null || $phone === '') {
    echo json_encode(['found' => false]);
    exit;
}

$stmt = $pdo->prepare(
    'SELECT b.booking_id, b.check_in, b.check_out, b.total_guest, b.total_amount,
            b.deposit_amount, b.booking_status
     FROM booking b
     JOIN customer c ON c.customer_id = b.customer_id
     WHERE b.booking_id = :ref AND c.phone = :phone'
);
$stmt->execute(['ref' => $ref, 'phone' => $phone]);
$booking = $stmt->fetch();

if (!$booking) {
    echo json_encode(['found' => false]);
    exit;
}

$stmt = $pdo->prepare(
    'SELECT GROUP_CONCAT(a.accommodation_name SEPARATOR ", ") AS accommodations
     FROM booking_item bi JOIN accommodation a ON a.accommodation_id = bi.accommodation_id
     WHERE bi.booking_id = :id'
);
$stmt->execute(['id' => $booking['booking_id']]);
$accommodations = $stmt->fetchColumn() ?: '—';

$stmt = $pdo->prepare('SELECT payment_status FROM payment WHERE booking_id = :id ORDER BY payment_id DESC LIMIT 1');
$stmt->execute(['id' => $booking['booking_id']]);
$paymentStatus = $stmt->fetchColumn() ?: null;

$stmt = $pdo->prepare("SELECT deposit_paid FROM payment WHERE booking_id = :id AND payment_status = 'paid' ORDER BY payment_id DESC LIMIT 1");
$stmt->execute(['id' => $booking['booking_id']]);
$amountPaid = (float) ($stmt->fetchColumn() ?: 0);

echo json_encode([
    'found' => true,
    'booking_id' => (int) $booking['booking_id'],
    'booking_ref' => format_booking_ref((int) $booking['booking_id']),
    'status' => format_status($booking['booking_status']),
    'check_in' => $booking['check_in'],
    'check_out' => $booking['check_out'],
    'total_guest' => (int) $booking['total_guest'],
    'accommodations' => $accommodations,
    'payment_status' => $paymentStatus ? format_status($paymentStatus) : null,
    'balance_due' => round(booking_grand_total($booking) - $amountPaid, 2),
]);
