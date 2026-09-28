<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/email_notify.php';

$allowedBanks = ['Bank Islam', 'Maybank', 'CIMB Bank', 'Public Bank', 'RHB Bank', 'Hong Leong Bank'];

# calling function filter_var() that assign to variable name $bookingId untuk ambil & validate booking_id dari POST
$bookingId = filter_var($_POST['booking_id'] ?? '', FILTER_VALIDATE_INT);
# ambil value $_POST['method'] that assign to variable name $method
$method = $_POST['method'] ?? '';
# calling function trim() that assign to variable name $bank untuk buang whitespace nama bank yang dihantar
$bank = trim((string) ($_POST['bank'] ?? ''));
# calling function trim() that assign to variable name $phone untuk buang whitespace nombor phone yang dihantar
$phone = trim((string) ($_POST['phone'] ?? ''));
$booking = null;
$errors = [];
$paid = false;

# calling function in_array() untuk check $method valid ke tidak, kalau tak valid kosongkan balik
if (!in_array($method, ['online_banking'], true)) {
    $method = '';
}
# calling function in_array() untuk check $bank ada dalam list bank yang dibenarkan ke tidak
if (!in_array($bank, $allowedBanks, true)) {
    $bank = '';
}

# check kalau request bukan POST atau token csrf tak sah, tolak proses
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $errors[] = 'This page can only be reached from the payment confirmation form.';
} elseif (!csrf_verify()) {
    $errors[] = 'Your session expired. Please try again.';
}

# check kalau takde error dan $bookingId valid baru proceed ambil data booking
if (!$errors && $bookingId !== false) {
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

    # check kalau $booking wujud baru check status bayaran
    if ($booking) {
        # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query check payment yang dah paid
        $stmt = $pdo->prepare(
            "SELECT payment_id FROM payment WHERE booking_id = :id AND payment_status = 'paid' LIMIT 1"
        );
        # calling method execute() dari object $stmt untuk jalankan query check status paid
        $stmt->execute(['id' => $bookingId]);
        # calling method fetch() dari object $stmt that assign to variable name $paid untuk tahu booking ni dah paid ke belum
        $paid = (bool) $stmt->fetch();
    }
}

# check kalau takde error tapi booking tak jumpa, method kosong, atau confirm checkbox tak ditick
if (!$errors && !$booking) {
    $errors[] = 'We couldn\'t find that booking. Please start again from the booking form.';
} elseif (!$errors && $method === '') {
    $errors[] = 'Missing payment method. Please choose a payment method again.';
} elseif (!$errors && !($_POST['confirm'] ?? false)) {
    $errors[] = 'Please confirm the payment details before proceeding.';
}

# check kalau booking wujud, belum paid, dan takde error baru rekod bayaran
if ($booking && !$paid && !$errors) {
    try {
        # calling function record_booking_payment() that assign to variable name $paid untuk simpan rekod bayaran dalam database
        $paid = record_booking_payment($pdo, $bookingId, booking_grand_total($booking), $method);
    } catch (Throwable $e) {
        $errors[] = 'Something went wrong while recording your payment. Please try again.';
    }

    # check kalau rekod bayaran berjaya baru hantar email notification
    if ($paid) {
        # calling function send_status_email() untuk hantar email notify booking dah confirmed
        send_status_email($pdo, $bookingId, 'confirmed');
    }
}

# assign url redirect ke variable $redirectUrl ikut booking wujud ke tidak, untuk guna dalam view
$redirectUrl = $booking
    ? 'sucess_payment.php?ref=' . $bookingId . '&phone=' . urlencode($booking['phone'])
    : '';

$base = '../';
$active = '';
$pageTitle = 'Processing Payment — Casadive Villa';
$pageCss = 'style/process_payment.css';

require __DIR__ . '/views/process_payment.view.php';
