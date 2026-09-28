<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$bookingId = filter_var($_POST['booking_id'] ?? $_GET['booking_id'] ?? '', FILTER_VALIDATE_INT);
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

        $stmt = $pdo->prepare(
            "SELECT payment_id FROM payment WHERE booking_id = :id AND payment_status = 'paid' LIMIT 1"
        );
        $stmt->execute(['id' => $bookingId]);
        $paid = (bool) $stmt->fetch();
    }
}

if ($booking && !$paid && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $method = $_POST['method'] ?? '';
    if (!in_array($method, ['toyyibpay', 'qr'], true)) {
        $errors[] = 'Please choose a payment method.';
    }

    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please try again.';
    }

    if (!$errors) {
        header('Location: payment_method.php?booking_id=' . $bookingId . '&method=' . urlencode($method));
        exit;
    }
}

$base = '../';
$active = '';
$pageTitle = 'Payment — Casadive Villa';
$pageCss = 'style/payment.css';

require __DIR__ . '/views/payment.view.php';
