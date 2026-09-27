<?php
/**
 * Endpoint tunggal untuk ToyyibPay "return" (browser dibawa balik sini lepas bayar) DAN
 * "callback" (ToyyibPay server hantar POST terus ke sini) — dua-dua guna logik sahkan yang
 * sama, page ni cuma layan mereka secara berbeza di penghujung (redirect vs. 200 OK senyap).
 *
 * PENTING: kita TAK sekali percaya status_id dalam query string secara membuta tuli — sesiapa
 * boleh karang URL macam tu. Kita ambil billcode dari request, pastu tanya terus API
 * getBillTransactions ToyyibPay (guna secret key kita) untuk status & booking_id (billExternalReferenceNo)
 * yang SAH — bukan dari apa yang dihantar dalam URL/POST.
 */
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
        http_response_code(200); // ToyyibPay server callback — bagitahu dah terima, tak payah retry
    }
    exit;
}

if ($billCode === '') {
    toyyibpay_return_finish($isBrowserRequest, 'mybooking.php');
}

$transaction = toyyibpay_get_latest_transaction($billCode);

// order_id/booking_id yang SAH datang dari data transaksi yang API pulangkan (billExternalReferenceNo),
// bukan dari $_REQUEST['order_id'] — elak orang tukar URL untuk confirm booking orang lain
$bookingId = $transaction ? filter_var($transaction['billExternalReferenceNo'] ?? '', FILTER_VALIDATE_INT) : false;

if (!$transaction || $bookingId === false) {
    // takde transaksi lagi (customer batal/tinggalkan sebelum bayar) atau API gagal dihubungi
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

// billpaymentStatus: '1' = berjaya, '2' = pending, '3' = tak berjaya (ikut dokumentasi ToyyibPay)
if (($transaction['billpaymentStatus'] ?? null) === '1') {
    // NOTA: billAmount yang kita HANTAR ke createBill kena dalam sen, tapi billpaymentAmount
    // yang ToyyibPay PULANGKAN balik dalam getBillTransactions dah dalam Ringgit (cth. "1.00"
    // untuk RM1.00) — bukan sen. Jangan bahagi /100 kat sini, beza dari amountSen di toyyibpay_pay.php.
    $amountPaid = (float) ($transaction['billpaymentAmount'] ?? 0);
    $paidNow = record_booking_payment($pdo, (int) $bookingId, $amountPaid, 'toyyibpay', 'ToyyibPay bill ' . $billCode);

    if ($paidNow) {
        send_status_email($pdo, (int) $bookingId, 'confirmed');
    }

    toyyibpay_return_finish($isBrowserRequest, $redirectUrl);
}

// belum berjaya (pending/gagal/dibatalkan) — hantar balik ke MyBooking supaya customer boleh
// cuba bayar semula, bukan ke sucess_payment (booking tu belum confirmed lagi)
toyyibpay_return_finish(
    $isBrowserRequest,
    'mybooking.php?ref=' . urlencode(format_booking_ref((int) $bookingId)) . '&phone=' . urlencode($booking['customer_phone'])
);
