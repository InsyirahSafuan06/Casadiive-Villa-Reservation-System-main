<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/email_notify.php';
require_once __DIR__ . '/../includes/toyyibpay.php';

# check request method GET (browser) ke tidak, that assign to variable name $isBrowserRequest
$isBrowserRequest = $_SERVER['REQUEST_METHOD'] === 'GET';
# calling function trim() that assign to variable name $billCode untuk ambil billcode dari ToyyibPay callback
$billCode = trim((string) ($_REQUEST['billcode'] ?? ''));

#fungsi untuk habiskan request ni - redirect kalau browser, atau bagi response 200 kalau callback server
function toyyibpay_return_finish(bool $isBrowserRequest, string $redirectUrl): never
{
    # check kalau request dari browser baru redirect, kalau tidak (callback ToyyibPay) hantar response 200 je
    if ($isBrowserRequest) {
        # calling function header() untuk redirect browser ke $redirectUrl
        header('Location: ' . $redirectUrl);
    } else {
        # calling function http_response_code() untuk bagitahu ToyyibPay callback dah diterima ok
        http_response_code(200);
    }
    exit;
}

# check kalau $billCode kosong, terus habiskan proses balik ke mybooking
if ($billCode === '') {
    # calling function toyyibpay_return_finish() untuk redirect/response terus sbb billcode takde
    toyyibpay_return_finish($isBrowserRequest, 'mybooking.php');
}

# calling function toyyibpay_get_latest_transaction() that assign to variable name $transaction untuk ambil transaksi terkini dari ToyyibPay
$transaction = toyyibpay_get_latest_transaction($billCode);

# calling function filter_var() that assign to variable name $bookingId untuk ambil & validate booking id dari data transaksi
$bookingId = $transaction ? filter_var($transaction['billExternalReferenceNo'] ?? '', FILTER_VALIDATE_INT) : false;

# check kalau transaksi tak jumpa atau booking id tak valid, habiskan proses
if (!$transaction || $bookingId === false) {
    # calling function toyyibpay_return_finish() untuk redirect/response terus sbb data transaksi tak valid
    toyyibpay_return_finish($isBrowserRequest, 'mybooking.php');
}

# calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query ambil booking & phone customer
$stmt = $pdo->prepare(
    'SELECT b.booking_id, c.phone AS customer_phone
     FROM booking b JOIN customer c ON c.customer_id = b.customer_id
     WHERE b.booking_id = :id'
);
# calling method execute() dari object $stmt untuk jalankan query dgn value $bookingId
$stmt->execute(['id' => $bookingId]);
# calling method fetch() dari object $stmt that assign to variable name $booking untuk ambil 1 row data booking
$booking = $stmt->fetch();

# check kalau booking tak jumpa, habiskan proses
if (!$booking) {
    # calling function toyyibpay_return_finish() untuk redirect/response terus sbb booking tak jumpa
    toyyibpay_return_finish($isBrowserRequest, 'mybooking.php');
}

# assign url redirect success ke variable $redirectUrl untuk guna kalau bayaran berjaya
$redirectUrl = 'sucess_payment.php?ref=' . $bookingId . '&phone=' . urlencode($booking['customer_phone']);

# check kalau status bayaran dari ToyyibPay ialah '1' (berjaya)
if (($transaction['billpaymentStatus'] ?? null) === '1') {
    # calling function (float) cast that assign to variable name $amountPaid untuk ambil jumlah yang dah dibayar
    $amountPaid = (float) ($transaction['billpaymentAmount'] ?? 0);
    # calling function record_booking_payment() that assign to variable name $paidNow untuk simpan rekod bayaran toyyibpay dalam database
    $paidNow = record_booking_payment($pdo, (int) $bookingId, $amountPaid, 'toyyibpay', 'ToyyibPay bill ' . $billCode);

    # check kalau rekod bayaran berjaya baru hantar email notification
    if ($paidNow) {
        # calling function send_status_email() untuk hantar email notify booking dah confirmed
        send_status_email($pdo, (int) $bookingId, 'confirmed');
    }

    # calling function toyyibpay_return_finish() untuk redirect/response terus ke page success
    toyyibpay_return_finish($isBrowserRequest, $redirectUrl);
}

# calling function toyyibpay_return_finish() untuk redirect/response terus ke mybooking sbb bayaran tak berjaya/pending
toyyibpay_return_finish(
    $isBrowserRequest,
    'mybooking.php?ref=' . urlencode(format_booking_ref((int) $bookingId)) . '&phone=' . urlencode($booking['customer_phone'])
);
