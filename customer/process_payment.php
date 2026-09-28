<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/email_notify.php';

$allowedBanks = ['Bank Islam', 'Maybank', 'CIMB Bank', 'Public Bank', 'RHB Bank', 'Hong Leong Bank'];

$bookingId = filter_var($_GET['booking_id'] ?? '', FILTER_VALIDATE_INT);
$method = $_GET['method'] ?? '';
$bank = trim((string) ($_GET['bank'] ?? ''));
$booking = null;
$errors = [];
$paid = false;

if (!in_array($method, ['online_banking'], true)) {
    $method = '';
}
if (!in_array($bank, $allowedBanks, true)) {
    $bank = '';
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
            "SELECT payment_id FROM payment WHERE booking_id = :id AND payment_status = 'paid' LIMIT 1"
        );
        $stmt->execute(['id' => $bookingId]);
        $paid = (bool) $stmt->fetch();
    }
}

if (!$booking) {
    $errors[] = 'We couldn\'t find that booking. Please start again from the booking form.';
} elseif ($method === '') {
    $errors[] = 'Missing payment method. Please choose a payment method again.';
}

if ($booking && !$paid && !$errors) {
    try {
        $paid = record_booking_payment($pdo, $bookingId, booking_grand_total($booking), $method);
    } catch (Throwable $e) {
        $errors[] = 'Something went wrong while recording your payment. Please try again.';
    }

    if ($paid) {
        send_status_email($pdo, $bookingId, 'confirmed');
    }
}

$redirectUrl = $booking
    ? 'sucess_payment.php?ref=' . $bookingId . '&phone=' . urlencode($booking['phone'])
    : '';

$base = '../';
$active = '';
$pageTitle = 'Processing Payment — Casadive Villa';
$pageCss = 'style/process_payment.css';

require __DIR__ . '/views/process_payment.view.php';
