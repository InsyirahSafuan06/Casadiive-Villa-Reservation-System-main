<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/toyyibpay.php';

# calling function parse_booking_ref() that assign to variable name $refId untuk tukar booking ref (cth CDV123) jadi id integer
$refId = parse_booking_ref((string) ($_GET['ref'] ?? ''));
# calling function trim() that assign to variable name $phone untuk buang whitespace nombor phone dari url
$phone = trim((string) ($_GET['phone'] ?? ''));

#fungsi untuk papar mesej error dan stop proses terus
function toyyibpay_pay_fail(string $message): never
{
    # calling function http_response_code() untuk set response code 400 (bad request)
    http_response_code(400);
    # calling function htmlspecialchars() untuk escape mesej sebelum papar, elak XSS
    echo '<p style="font-family:sans-serif;max-width:480px;margin:60px auto;text-align:center;">'
        . htmlspecialchars($message)
        . '</p><p style="text-align:center;"><a href="mybooking.php">Back to MyBooking</a></p>';
    exit;
}

# check kalau $refId tak valid atau $phone kosong, terus gagalkan proses
if ($refId === false || $phone === '') {
    # calling function toyyibpay_pay_fail() untuk papar error "Invalid booking reference" dan stop
    toyyibpay_pay_fail('Invalid booking reference.');
}

# calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query ambil booking ikut id & phone
$stmt = $pdo->prepare(
    'SELECT b.*, c.full_name, c.phone, c.email
     FROM booking b JOIN customer c ON c.customer_id = b.customer_id
     WHERE b.booking_id = :id AND c.phone = :phone'
);
# calling method execute() dari object $stmt untuk jalankan query dgn value $refId & $phone
$stmt->execute(['id' => $refId, 'phone' => $phone]);
# calling method fetch() dari object $stmt that assign to variable name $booking untuk ambil 1 row data booking
$booking = $stmt->fetch();

# check kalau booking tak jumpa, gagalkan proses
if (!$booking) {
    # calling function toyyibpay_pay_fail() untuk papar error "booking not found" dan stop
    toyyibpay_pay_fail('We could not find that booking.');
}

# calling function in_array() untuk check status booking masih pending/confirmed (boleh dibayar online)
if (!in_array($booking['booking_status'], ['pending', 'confirmed'], true)) {
    # calling function toyyibpay_pay_fail() untuk papar error booking tak available untuk bayaran online
    toyyibpay_pay_fail('This booking is no longer available for online payment.');
}

# calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query check payment yang dah paid
$stmt = $pdo->prepare("SELECT payment_id FROM payment WHERE booking_id = :id AND payment_status = 'paid' LIMIT 1");
# calling method execute() dari object $stmt untuk jalankan query check status paid
$stmt->execute(['id' => $refId]);
# check kalau booking ni dah ada rekod paid, terus redirect ke page success
if ($stmt->fetch()) {
    # calling function header() untuk redirect browser ke page sucess_payment.php sbb dah paid
    header('Location: sucess_payment.php?ref=' . $refId . '&phone=' . urlencode($phone));
    exit;
}

# calling function toyyibpay_configured() untuk check setting ToyyibPay dah lengkap ke belum
if (!toyyibpay_configured()) {
    # calling function toyyibpay_pay_fail() untuk papar error online payment tak available buat masa ni
    toyyibpay_pay_fail('Online payment is temporarily unavailable. Please choose another payment method or contact us.');
}

# calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query ambil senarai accommodation dalam booking ni
$stmt = $pdo->prepare(
    "SELECT a.accommodation_name, a.accommodation_type
     FROM booking_item bi JOIN accommodation a ON a.accommodation_id = bi.accommodation_id
     WHERE bi.booking_id = :id"
);
# calling method execute() dari object $stmt untuk jalankan query ikut $refId
$stmt->execute(['id' => $refId]);
# calling method fetchAll() dari object $stmt that assign to variable name $items untuk ambil semua row accommodation
$items = $stmt->fetchAll();
# calling function array_unique() & array_column() that assign to variable name $types untuk ambil jenis accommodation yang unik (Villa/Campsite)
$types = array_unique(array_column($items, 'accommodation_type'));
# calling function in_array() that assign to variable name $typeLabel untuk tentukan label jenis booking (villa, campsite atau kedua-dua)
$typeLabel = in_array('Villa', $types, true) && in_array('Campsite', $types, true)
    ? 'Villa & Campsite'
    : ($types[0] ?? 'Booking');

# calling function toyyibpay_category_code_for() that assign to variable name $categoryCode untuk ambil category code ToyyibPay ikut nama accommodation
$categoryCode = toyyibpay_category_code_for((string) ($items[0]['accommodation_name'] ?? ''));

# calling function format_booking_ref() that assign to variable name $bookingRef untuk bina booking ref (cth CDV123) dari id
$bookingRef = format_booking_ref($refId);
# calling function round() & booking_grand_total() that assign to variable name $amountSen untuk tukar total bayaran ke unit sen (integer)
$amountSen = (int) round(booking_grand_total($booking) * 100);

# check kalau request guna https, assign scheme yang betul ke $scheme
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
# calling function rtrim() & str_replace() & dirname() that assign to variable name $scriptDir untuk ambil folder path script semasa
$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
# assign gabungan scheme, host & folder ke variable $siteBaseUrl untuk bina url asas website
$siteBaseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . $scriptDir;
# assign url callback ToyyibPay ke variable $returnUrl untuk bagitahu ToyyibPay kena redirect balik ke mana
$returnUrl = $siteBaseUrl . '/toyyibpay_return.php';

# assign email customer atau email placeholder ke variable $billEmail sbb ToyyibPay wajibkan email
$billEmail = $booking['email'] ?: preg_replace('/[^0-9]/', '', $booking['phone']) . '@no-email.casadivevilla.local';

# calling function toyyibpay_create_bill() that assign to variable name $billCode untuk cipta bill baru kat ToyyibPay
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

# check kalau bill gagal dicipta, gagalkan proses
if (!$billCode) {
    # calling function toyyibpay_pay_fail() untuk papar error bill ToyyibPay gagal dicipta
    toyyibpay_pay_fail('We could not start the ToyyibPay payment right now. Please try again later or choose another payment method.');
}

# calling function header() & toyyibpay_bill_payment_url() untuk redirect browser terus ke page bayaran ToyyibPay
header('Location: ' . toyyibpay_bill_payment_url($billCode));
exit;
