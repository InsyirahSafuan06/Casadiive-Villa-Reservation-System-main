<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/email_notify.php';
require_once __DIR__ . '/../includes/toyyibpay.php';

$isBrowserRequest = $_SERVER['REQUEST_METHOD'] === 'GET';
$billCode = trim((string) ($_REQUEST['billcode'] ?? ''));

function toyyibpay_return_finish(bool $isBrowserRequest, string $redirectUrl): never
{
    if ($isBrowserRequest) {
        header('Location: ' . $redirectUrl);
    } else {
        http_response_code(200);
    }
    exit;
}

if ($billCode === '') {
    toyyibpay_return_finish($isBrowserRequest, 'mybooking.php');
}

$transaction = toyyibpay_get_latest_transaction($billCode);

$bookingId = $transaction ? filter_var($transaction['billExternalReferenceNo'] ?? '', FILTER_VALIDATE_INT) : false;

if (!$transaction || $bookingId === false) {
    toyyibpay_return_finish($isBrowserRequest, 'mybooking.php');
}

$stmt = $pdo->prepare(
    'SELECT b.booking_id, c.phone AS customer_phone
     FROM booking b JOIN customer c ON c.customer_id = b.customer_id
     WHERE b.booking_id = :id'
);
$stmt->execute(['id' => $bookingId]);
$booking = $stmt->fetch();

if (!$booking) {
    toyyibpay_return_finish($isBrowserRequest, 'mybooking.php');
}

$redirectUrl = 'sucess_payment.php?ref=' . $bookingId . '&phone=' . urlencode($booking['customer_phone']);

if (($transaction['billpaymentStatus'] ?? null) === '1') {
    $amountPaid = (float) ($transaction['billpaymentAmount'] ?? 0);
    $paidNow = record_booking_payment($pdo, (int) $bookingId, $amountPaid, 'toyyibpay', 'ToyyibPay bill ' . $billCode);

    if ($paidNow) {
        send_status_email($pdo, (int) $bookingId, 'confirmed');
    }

    toyyibpay_return_finish($isBrowserRequest, $redirectUrl);
}

toyyibpay_return_finish(
    $isBrowserRequest,
    'mybooking.php?ref=' . urlencode(format_booking_ref((int) $bookingId)) . '&phone=' . urlencode($booking['customer_phone'])
);
