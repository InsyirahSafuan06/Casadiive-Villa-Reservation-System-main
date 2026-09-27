<?php
/**
 * Halaman pengesahan pembayaran.
 * Paparkan tempahan dan kaedah pembayaran yang dipilih pada payment.php untuk semakan
 * sebelum deposit benar-benar dicaj dalam process_payment.php.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/email_notify.php';

$methodLabels = ['toyyibpay' => 'Online Banking', 'qr' => 'QR Payment'];

$bookingId = filter_var($_POST['booking_id'] ?? $_GET['booking_id'] ?? '', FILTER_VALIDATE_INT);
$method = $_POST['method'] ?? $_GET['method'] ?? '';
$booking = null;
$items = [];
$errors = [];
$paid = false;
$nights = 0;

// value ni datang dari URL yang payment.php bina, tapi kita check balik sini —
// jangan sekali percaya value tu betul just sebab dia sampai melalui redirect "Location:"
if (!in_array($method, ['toyyibpay', 'qr'], true)) {
    $method = '';
}

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
            'SELECT bi.price, a.accommodation_name
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

        $checkIn = DateTime::createFromFormat('Y-m-d', (string) $booking['check_in']);
        $checkOut = DateTime::createFromFormat('Y-m-d', (string) $booking['check_out']);
        if ($checkIn && $checkOut) {
            $nights = (int) $checkIn->diff($checkOut)->days; // untuk papar "X night(s)" kat summary
        }
    }
}

// pelanggan kena tick "I confirm" dulu sebelum kita teruskan cas deposit —
// page ni sendiri tak sentuh database, cuma redirect ke process_payment.php
if ($booking && !$paid && $method !== '' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please try again.';
    }
    if (!$errors && !($_POST['confirm'] ?? false)) {
        $errors[] = 'Please confirm the payment details before proceeding.';
    }

    if (!$errors && $method === 'toyyibpay') {
        // ToyyibPay sendiri yang layan page bayaran & pengesahan — kita cuma cipta bil
        // sebenar dan redirect ke sana
        header('Location: toyyibpay_pay.php?ref=' . urlencode(format_booking_ref($bookingId)) . '&phone=' . urlencode($booking['phone']));
        exit;
    }

    if (!$errors && $method === 'qr') {
        // bayaran QR ni manual (customer scan & bayar sendiri di luar sistem) — kita cuma
        // simpan bukti bayaran yang dia upload, terus tanda booking 'confirmed'/'paid'
        $receiptPath = null;

        if (empty($_FILES['payment_proof']['name']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Please upload your payment receipt/proof of payment.';
        } else {
            $file = $_FILES['payment_proof'];
            $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $maxSize = 5 * 1024 * 1024; // 5MB

            $isValid = in_array($ext, $allowedExt, true) && $file['size'] > 0 && $file['size'] <= $maxSize;
            if ($isValid) {
                // sahkan kandungan fail sebenar padan dengan sangkaan (bukan cuma percaya extension) —
                // PDF disahkan guna MIME type kalau extension fileinfo ada, kalau tak (sesetengah
                // hosting disable dia) baca 5 byte pertama fail terus (semua PDF sah mula dengan "%PDF-")
                if ($ext === 'pdf') {
                    $isValid = function_exists('mime_content_type')
                        ? mime_content_type($file['tmp_name']) === 'application/pdf'
                        : @file_get_contents($file['tmp_name'], false, null, 0, 5) === '%PDF-';
                } else {
                    $isValid = @getimagesize($file['tmp_name']) !== false;
                }
            }

            if (!$isValid) {
                $errors[] = 'Please upload a valid receipt file (JPG, PNG, WEBP or PDF, max 5MB).';
            } else {
                $destDir = __DIR__ . '/../assets/uploads/payments/';
                if (!is_dir($destDir)) {
                    mkdir($destDir, 0755, true);
                }
                $filename = 'payment_' . $bookingId . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

                if (move_uploaded_file($file['tmp_name'], $destDir . $filename)) {
                    $receiptPath = 'assets/uploads/payments/' . $filename;
                } else {
                    $errors[] = 'We could not save your uploaded receipt. Please try again.';
                }
            }
        }

        if (!$errors) {
            $paidNow = record_booking_payment($pdo, $bookingId, booking_grand_total($booking), 'qr', $receiptPath);

            if (!$paidNow) {
                $errors[] = 'This booking has already been paid for.';
            } else {
                send_status_email($pdo, $bookingId, 'confirmed');
                header('Location: sucess_payment.php?ref=' . $bookingId . '&phone=' . urlencode($booking['phone']));
                exit;
            }
        }
    }
}

$base = '../'; // page ni dalam folder customer/, naik satu tahap untuk pergi root
$active = ''; // takde menu navbar yang perlu di-highlight untuk page ni
$pageTitle = 'Payment Confirmation — Casadive Villa';
$pageCss = 'style/payment_method.css';

// semua logic dah selesai kat atas ni — baris bawah papar HTML page dia.
// HTML/borang tu disimpan berasingan dalam folder views/ supaya file ni tak jadi terlalu panjang.
require __DIR__ . '/views/payment_method.view.php';
