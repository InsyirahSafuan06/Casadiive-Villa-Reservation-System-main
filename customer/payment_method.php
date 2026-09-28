<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/email_notify.php';

$methodLabels = ['toyyibpay' => 'Online Banking', 'qr' => 'QR Payment'];

# calling function filter_var() that assign to variable name $bookingId untuk ambil & validate booking_id dari POST atau GET
$bookingId = filter_var($_POST['booking_id'] ?? $_GET['booking_id'] ?? '', FILTER_VALIDATE_INT);
# ambil value method bayaran dari POST atau GET that assign to variable name $method
$method = $_POST['method'] ?? $_GET['method'] ?? '';
$booking = null;
$items = [];
$errors = [];
$paid = false;
$nights = 0;

# calling function in_array() untuk check $method valid ke tidak, kalau tak valid kosongkan balik
if (!in_array($method, ['toyyibpay', 'qr'], true)) {
    $method = '';
}

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

    # check kalau $booking wujud baru ambil detail item, status bayaran & kira nights
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

        # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query check payment yang dah paid
        $stmt = $pdo->prepare(
            "SELECT payment_id FROM payment WHERE booking_id = :id AND payment_status = 'paid' LIMIT 1"
        );
        # calling method execute() dari object $stmt untuk jalankan query check status paid
        $stmt->execute(['id' => $bookingId]);
        # calling method fetch() dari object $stmt that assign to variable name $paid untuk tahu booking ni dah paid ke belum
        $paid = (bool) $stmt->fetch();

        # calling method createFromFormat() that assign to variable name $checkIn untuk tukar date check_in string jadi object DateTime
        $checkIn = DateTime::createFromFormat('Y-m-d', (string) $booking['check_in']);
        # calling method createFromFormat() that assign to variable name $checkOut untuk tukar date check_out string jadi object DateTime
        $checkOut = DateTime::createFromFormat('Y-m-d', (string) $booking['check_out']);
        # check kalau kedua-dua date valid baru kira bilangan malam
        if ($checkIn && $checkOut) {
            # calling method diff() dari object $checkIn that assign to variable name $nights untuk kira berapa malam antara check_in & check_out
            $nights = (int) $checkIn->diff($checkOut)->days;
        }
    }
}

# check kalau booking wujud, belum paid, method dah dipilih, dan request method POST baru proses bayaran
if ($booking && !$paid && $method !== '' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    # calling function csrf_verify() untuk pastikan form ni submit dgn token csrf yang sah
    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please try again.';
    }
    # check kalau takde error dan user tak tick confirm checkbox
    if (!$errors && !($_POST['confirm'] ?? false)) {
        $errors[] = 'Please confirm the payment details before proceeding.';
    }

    # check kalau takde error dan method yang dipilih ialah toyyibpay
    if (!$errors && $method === 'toyyibpay') {
        # calling function header() untuk redirect browser ke toyyibpay_pay.php bawa sekali booking ref & phone
        header('Location: toyyibpay_pay.php?ref=' . urlencode(format_booking_ref($bookingId)) . '&phone=' . urlencode($booking['phone']));
        exit;
    }

    # check kalau takde error dan method yang dipilih ialah qr
    if (!$errors && $method === 'qr') {
        $receiptPath = null;

        # calling function empty() untuk check user ada upload file resit ke tidak, dan check upload takde error
        if (empty($_FILES['payment_proof']['name']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Please upload your payment receipt/proof of payment.';
        } else {
            # ambil value $_FILES['payment_proof'] that assign to variable name $file
            $file = $_FILES['payment_proof'];
            $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
            # calling function strtolower() & pathinfo() that assign to variable name $ext untuk ambil extension file dalam huruf kecil
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $maxSize = 5 * 1024 * 1024;

            # calling function in_array() that assign to variable name $isValid untuk check extension, size file betul ke tidak
            $isValid = in_array($ext, $allowedExt, true) && $file['size'] > 0 && $file['size'] <= $maxSize;
            # check kalau format & size dah valid, teruskan check kandungan file betul-betul PDF atau gambar
            if ($isValid) {
                if ($ext === 'pdf') {
                    # calling function function_exists() untuk check mime_content_type() available, kalau tak baca byte awal file untuk pastikan memang PDF
                    $isValid = function_exists('mime_content_type')
                        ? mime_content_type($file['tmp_name']) === 'application/pdf'
                        : @file_get_contents($file['tmp_name'], false, null, 0, 5) === '%PDF-';
                } else {
                    # calling function getimagesize() untuk pastikan file yang diupload memang gambar sah
                    $isValid = @getimagesize($file['tmp_name']) !== false;
                }
            }

            # check kalau file tak valid, kalau valid pula sediakan folder & simpan file
            if (!$isValid) {
                $errors[] = 'Please upload a valid receipt file (JPG, PNG, WEBP or PDF, max 5MB).';
            } else {
                $destDir = __DIR__ . '/../assets/uploads/payments/';
                # calling function is_dir() untuk check folder upload dah wujud ke belum
                if (!is_dir($destDir)) {
                    # calling function mkdir() untuk cipta folder upload payment kalau belum wujud
                    mkdir($destDir, 0755, true);
                }
                # calling function bin2hex() & random_bytes() that assign to variable name $filename untuk jana nama file unik
                $filename = 'payment_' . $bookingId . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

                # calling function move_uploaded_file() untuk pindah file yang diupload ke folder tetap
                if (move_uploaded_file($file['tmp_name'], $destDir . $filename)) {
                    $receiptPath = 'assets/uploads/payments/' . $filename;
                } else {
                    $errors[] = 'We could not save your uploaded receipt. Please try again.';
                }
            }
        }

        # check kalau takde error langsung baru rekod bayaran
        if (!$errors) {
            # calling function record_booking_payment() that assign to variable name $paidNow untuk simpan rekod bayaran qr dalam database
            $paidNow = record_booking_payment($pdo, $bookingId, booking_grand_total($booking), 'qr', $receiptPath);

            # check kalau rekod bayaran gagal (dah paid awal) atau berjaya
            if (!$paidNow) {
                $errors[] = 'This booking has already been paid for.';
            } else {
                # calling function send_status_email() untuk hantar email notify booking dah confirmed
                send_status_email($pdo, $bookingId, 'confirmed');
                # calling function header() untuk redirect browser ke page sucess_payment.php
                header('Location: sucess_payment.php?ref=' . $bookingId . '&phone=' . urlencode($booking['phone']));
                exit;
            }
        }
    }
}

$base = '../';
$active = '';
$pageTitle = 'Payment Confirmation — Casadive Villa';
$pageCss = 'style/payment_method.css';

require __DIR__ . '/views/payment_method.view.php';
