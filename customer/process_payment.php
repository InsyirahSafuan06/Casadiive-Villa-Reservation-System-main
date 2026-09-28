<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/email_notify.php';

$allowedBanks = ['Bank Islam', 'Maybank', 'CIMB Bank', 'Public Bank', 'RHB Bank', 'Hong Leong Bank'];

$bookingId = filter_var($_POST['booking_id'] ?? '', FILTER_VALIDATE_INT);
$method = $_POST['method'] ?? '';
$bank = trim((string) ($_POST['bank'] ?? ''));
$phone = trim((string) ($_POST['phone'] ?? ''));
$booking = null;
$errors = [];
$paid = false;

if (!in_array($method, ['online_banking'], true)) {
    $method = '';
}
if (!in_array($bank, $allowedBanks, true)) {
    $bank = '';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $errors[] = 'This page can only be reached from the payment confirmation form.';
} elseif (!csrf_verify()) {
    $errors[] = 'Your session expired. Please try again.';
}

if (!$errors && $bookingId !== false) {
    $stmt = $pdo->prepare(
        'SELECT b.*, c.full_name, c.phone
         FROM booking b JOIN customer c ON c.customer_id = b.customer_id
         WHERE b.booking_id = :id AND c.phone = :phone'
    );
    $stmt->execute(['id' => $bookingId, 'phone' => $phone]);
    $booking = $stmt->fetch();

    if ($booking) {
        $stmt = $pdo->prepare(
            "SELECT payment_id FROM payment WHERE booking_id = :id AND payment_status = 'paid' LIMIT 1"
        );
        $stmt->execute(['id' => $bookingId]);
        $paid = (bool) $stmt->fetch();
    }
}

if (!$errors && !$booking) {
    $errors[] = 'We couldn\'t find that booking. Please start again from the booking form.';
} elseif (!$errors && $method === '') {
    $errors[] = 'Missing payment method. Please choose a payment method again.';
} elseif (!$errors && !($_POST['confirm'] ?? false)) {
    $errors[] = 'Please confirm the payment details before proceeding.';
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
