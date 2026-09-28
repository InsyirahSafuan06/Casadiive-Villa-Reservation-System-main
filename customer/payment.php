<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

# calling function filter_var() that assign to variable name $bookingId untuk ambil & validate booking_id dari POST atau GET
$bookingId = filter_var($_POST['booking_id'] ?? $_GET['booking_id'] ?? '', FILTER_VALIDATE_INT);
$booking = null;
$items = [];
$errors = [];
$paid = false;

# check kalau $bookingId valid (bukan false) baru proceed ambil data booking
if ($bookingId !== false) {
    # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query ambil booking & customer
    $stmt = $pdo->prepare(
        'SELECT b.*, c.full_name, c.phone
         FROM booking b JOIN customer c ON c.customer_id = b.customer_id
         WHERE b.booking_id = :id'
    );
    # calling method execute() dari object $stmt untuk jalankan query dgn value $bookingId
    $stmt->execute(['id' => $bookingId]);
    # calling method fetch() dari object $stmt that assign to variable name $booking untuk ambil 1 row data booking
    $booking = $stmt->fetch();

    # check kalau $booking wujud baru ambil detail item & status bayaran
    if ($booking) {
        # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query ambil senarai item booking
        $stmt = $pdo->prepare(
            'SELECT bi.price, a.accommodation_name, a.accommodation_type
             FROM booking_item bi
             JOIN accommodation a ON a.accommodation_id = bi.accommodation_id
             WHERE bi.booking_id = :id'
        );
        # calling method execute() dari object $stmt untuk jalankan query item ikut $bookingId
        $stmt->execute(['id' => $bookingId]);
        # calling method fetchAll() dari object $stmt that assign to variable name $items untuk ambil semua row item booking
        $items = $stmt->fetchAll();

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

# check kalau booking wujud, belum paid, dan request method POST baru proses pilihan kaedah bayaran
if ($booking && !$paid && $_SERVER['REQUEST_METHOD'] === 'POST') {
    # ambil value $_POST['method'] that assign to variable name $method
    $method = $_POST['method'] ?? '';
    # calling function in_array() untuk check $method yang dipilih valid ke tidak
    if (!in_array($method, ['toyyibpay', 'qr'], true)) {
        $errors[] = 'Please choose a payment method.';
    }

    # calling function csrf_verify() untuk pastikan form ni submit dgn token csrf yang sah
    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please try again.';
    }

    # check kalau takde error baru redirect ke page payment_method
    if (!$errors) {
        # calling function header() untuk redirect browser ke payment_method.php bawa sekali booking_id & method
        header('Location: payment_method.php?booking_id=' . $bookingId . '&method=' . urlencode($method));
        exit;
    }
}

$base = '../';
$active = '';
$pageTitle = 'Payment — Casadive Villa';
$pageCss = 'style/payment.css';

require __DIR__ . '/views/payment.view.php';
