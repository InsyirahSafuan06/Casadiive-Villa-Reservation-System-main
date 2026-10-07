<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$methodLabels = ['qr' => 'QR / DuitNow'];

# calling function parse_booking_ref() that assign to variable name $bookingId untuk tukar booking ref (cth CDV123) jadi id integer
$bookingId = parse_booking_ref((string) ($_GET['ref'] ?? $_GET['booking_id'] ?? ''));
# calling function trim() that assign to variable name $phone untuk buang whitespace nombor phone dari url
$phone = trim((string) ($_GET['phone'] ?? ''));
$booking = null;
$items = [];
$payment = null;

# check kalau $bookingId valid dan $phone tak kosong baru proceed ambil data booking
if ($bookingId !== false && $phone !== '') {
    # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query ambil booking ikut id & phone
    $stmt = $pdo->prepare(
        'SELECT b.*, c.full_name, c.phone
         FROM booking b JOIN customer c ON c.customer_id = b.customer_id
         WHERE b.booking_id = :id AND c.phone = :phone'
    );
    # calling method execute() dari object $stmt untuk jalankan query dgn value $bookingId & $phone
    $stmt->execute(['id' => $bookingId, 'phone' => $phone]);
    # calling method fetch() dari object $stmt that assign to variable name $booking untuk ambil 1 row data booking
    $booking = $stmt->fetch();

    # check kalau $booking wujud baru ambil detail item & rekod bayaran
    if ($booking) {
        # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query ambil senarai item booking
        $stmt = $pdo->prepare(
            'SELECT bi.price, a.accommodation_name
             FROM booking_item bi
             JOIN accommodation a ON a.accommodation_id = bi.accommodation_id
             WHERE bi.booking_id = :id'
        );
        # calling method execute() dari object $stmt untuk jalankan query item ikut $bookingId
        $stmt->execute(['id' => $bookingId]);
        # calling method fetchAll() dari object $stmt that assign to variable name $items untuk ambil semua row item booking
        $items = $stmt->fetchAll();

        # Load the latest payment so the page can distinguish pending, rejected, and approved receipts.
        $stmt = $pdo->prepare(
            'SELECT payment_method, deposit_paid, payment_status FROM payment
             WHERE booking_id = :id ORDER BY payment_id DESC LIMIT 1'
        );
        $stmt->execute(['id' => $bookingId]);
        $payment = $stmt->fetch();
    }
}

$base = '../';
$active = '';
$paymentPageStatus = $payment['payment_status'] ?? null;
$pageTitle = match (true) {
    !$booking => 'Booking Not Found — Casadive Villa',
    $paymentPageStatus === 'paid' => 'Payment Successful — Casadive Villa',
    $paymentPageStatus === 'pending' => 'Receipt Under Review — Casadive Villa',
    $paymentPageStatus === 'failed' => 'Payment Failed — Casadive Villa',
    default => 'Payment Status — Casadive Villa',
};
$pageCss = 'style/sucess_payment.css';

require __DIR__ . '/views/sucess_payment.view.php';
