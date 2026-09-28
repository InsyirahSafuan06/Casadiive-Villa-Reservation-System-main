<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/toyyibpay.php';

$refId = parse_booking_ref((string) ($_GET['ref'] ?? ''));
$phone = trim((string) ($_GET['phone'] ?? ''));

function toyyibpay_pay_fail(string $message): never
{
    http_response_code(400);
    echo '<p style="font-family:sans-serif;max-width:480px;margin:60px auto;text-align:center;">'
        . htmlspecialchars($message)
        . '</p><p style="text-align:center;"><a href="mybooking.php">Back to MyBooking</a></p>';
    exit;
}

if ($refId === false || $phone === '') {
    toyyibpay_pay_fail('Invalid booking reference.');
}

$stmt = $pdo->prepare(
    'SELECT b.*, c.full_name, c.phone, c.email
     FROM booking b JOIN customer c ON c.customer_id = b.customer_id
     WHERE b.booking_id = :id AND c.phone = :phone'
);
$stmt->execute(['id' => $refId, 'phone' => $phone]);
$booking = $stmt->fetch();

if (!$booking) {
    toyyibpay_pay_fail('We could not find that booking.');
}

if (!in_array($booking['booking_status'], ['pending', 'confirmed'], true)) {
    toyyibpay_pay_fail('This booking is no longer available for online payment.');
}

$stmt = $pdo->prepare("SELECT payment_id FROM payment WHERE booking_id = :id AND payment_status = 'paid' LIMIT 1");
$stmt->execute(['id' => $refId]);
if ($stmt->fetch()) {
    header('Location: sucess_payment.php?ref=' . $refId . '&phone=' . urlencode($phone));
    exit;
}

if (!toyyibpay_configured()) {
    toyyibpay_pay_fail('Online payment is temporarily unavailable. Please choose another payment method or contact us.');
}

$stmt = $pdo->prepare(
    "SELECT a.accommodation_name, a.accommodation_type
     FROM booking_item bi JOIN accommodation a ON a.accommodation_id = bi.accommodation_id
     WHERE bi.booking_id = :id"
);
$stmt->execute(['id' => $refId]);
$items = $stmt->fetchAll();
$types = array_unique(array_column($items, 'accommodation_type'));
$typeLabel = in_array('Villa', $types, true) && in_array('Campsite', $types, true)
    ? 'Villa & Campsite'
    : ($types[0] ?? 'Booking');

$categoryCode = toyyibpay_category_code_for((string) ($items[0]['accommodation_name'] ?? ''));

$bookingRef = format_booking_ref($refId);
$amountSen = (int) round(booking_grand_total($booking) * 100);

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$siteBaseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . $scriptDir;
$returnUrl = $siteBaseUrl . '/toyyibpay_return.php';

$billEmail = $booking['email'] ?: preg_replace('/[^0-9]/', '', $booking['phone']) . '@no-email.casadivevilla.local';

$billCode = toyyibpay_create_bill([
    'categoryCode' => $categoryCode,
    'billName' => substr($bookingRef . ' - ' . $typeLabel, 0, 30),
    'billDescription' => substr('Casadive Villa booking ' . $bookingRef . ' (' . $typeLabel . ')', 0, 100),
    'billAmount' => $amountSen,
    'billReturnUrl' => $returnUrl,
    'billCallbackUrl' => $returnUrl,
    'billExternalReferenceNo' => (string) $refId,
    'billTo' => $booking['full_name'],
    'billEmail' => $billEmail,
    'billPhone' => $booking['phone'],
]);

if (!$billCode) {
    toyyibpay_pay_fail('We could not start the ToyyibPay payment right now. Please try again later or choose another payment method.');
}

header('Location: ' . toyyibpay_bill_payment_url($billCode));
exit;
