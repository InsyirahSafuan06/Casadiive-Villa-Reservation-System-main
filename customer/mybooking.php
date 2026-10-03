<?php
# calling function require_once() untuk load fail db.php supaya dapat object $pdo untuk connect database
require_once __DIR__ . '/../includes/db.php';
# calling function require_once() untuk load fail auth.php supaya boleh guna fungsi csrf_verify()
require_once __DIR__ . '/../includes/auth.php';
# calling function require_once() untuk load fail helpers.php supaya boleh guna fungsi pengiraan harga
require_once __DIR__ . '/../includes/helpers.php';
# calling function require_once() untuk load fail email_notify.php supaya boleh hantar email notifikasi status
require_once __DIR__ . '/../includes/email_notify.php';

# check $_GET['ref'] wujud, ambil & trim value, assign to variable name $ref
$ref = isset($_GET['ref']) ? trim((string) $_GET['ref']) : '';
# check $_GET['phone'] wujud, ambil & trim value, assign to variable name $phone
$phone = isset($_GET['phone']) ? trim((string) $_GET['phone']) : '';
# assign null ke $reviewError sbb belum ada error review lagi
$reviewError = null;
# assign null ke $cancelError sbb belum ada error cancel lagi
$cancelError = null;

# check request POST dan form yang dihantar jenis 'cancel'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'cancel') {
    # calling function filter_var() that assign to variable name $cRef untuk validate ref booking adalah integer
    $cRef = filter_var($_POST['ref'] ?? '', FILTER_VALIDATE_INT);
    # ambil value $_POST['phone'] then trim, assign to variable name $cPhone
    $cPhone = trim((string) ($_POST['phone'] ?? ''));

    # calling function csrf_verify() untuk pastikan request cancel ni betul-betul dari page kita sendiri
    if (!csrf_verify()) {
        $cancelError = 'Your session expired. Please try again.';
    } elseif ($cRef === false || $cPhone === '') {
        # check ref booking tak valid atau phone tak diisi
        $cancelError = 'Invalid booking reference.';
    } else {
        # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query sahkan booking milik phone ni dan boleh cancel
        $stmt = $pdo->prepare(
            "SELECT b.booking_id FROM booking b JOIN customer c ON c.customer_id = b.customer_id
             WHERE b.booking_id = :ref AND c.phone = :phone AND b.booking_status IN ('pending', 'confirmed')"
        );
        # calling method execute() dari object $stmt untuk jalankan query sahkan booking
        $stmt->execute(['ref' => $cRef, 'phone' => $cPhone]);
        # calling method fetch() dari object $stmt untuk check booking wujud & boleh cancel ke tidak
        if (!$stmt->fetch()) {
            $cancelError = 'This booking can no longer be cancelled.';
        } else {
            # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query update status booking jadi cancelled
            $stmt = $pdo->prepare("UPDATE booking SET booking_status = 'cancelled' WHERE booking_id = :id");
            # calling method execute() dari object $stmt untuk jalankan update status booking
            $stmt->execute(['id' => $cRef]);
            # calling function send_status_email() untuk hantar email makluman booking dah dicancel
            send_status_email($pdo, $cRef, 'cancelled');
        }
    }

    # check takde error cancel, terus redirect ke homepage
    if (!$cancelError) {
        # calling function header() untuk redirect browser balik ke index.php
        header('Location: ../index.php');
        exit;
    }

    # assign balik value $cRef & $cPhone ke $ref & $phone supaya borang boleh papar semula data yang sama
    $ref = (string) $cRef;
    $phone = $cPhone;
}

# check request POST dan form yang dihantar jenis 'review'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'review') {
    # calling function filter_var() that assign to variable name $rRef untuk validate ref booking adalah integer
    $rRef = filter_var($_POST['ref'] ?? '', FILTER_VALIDATE_INT);
    # ambil value $_POST['phone'] then trim, assign to variable name $rPhone
    $rPhone = trim((string) ($_POST['phone'] ?? ''));
    # calling function filter_var() that assign to variable name $rating untuk validate rating adalah integer
    $rating = filter_var($_POST['rating'] ?? '', FILTER_VALIDATE_INT);
    # ambil value $_POST['comment'] then trim, assign to variable name $comment
    $comment = trim((string) ($_POST['comment'] ?? ''));

    # calling function isset() untuk check customer pilih hantar review secara anonymous ke tidak
    $isAnonymous = isset($_POST['is_anonymous']);
    # check kalau anonymous, kosongkan nama, kalau tidak ambil dari $_POST['display_name']
    $displayName = $isAnonymous ? '' : trim((string) ($_POST['display_name'] ?? ''));
    # calling function mb_substr() untuk had panjang nama papar maksimum 100 huruf, kalau kosong guna null
    $displayName = $displayName !== '' ? mb_substr($displayName, 0, 100) : null;

    # check ref booking dan phone kedua-duanya diisi, assign to variable name $hasBookingRef
    $hasBookingRef = $rRef !== false && $rPhone !== '';
    # assign null ke $bookingIdForReview sbb belum sah lagi booking mana nak dikaitkan dgn review
    $bookingIdForReview = null;
    # assign true ke $verifiedOk sbb anggap valid dulu sebelum semua check dijalankan
    $verifiedOk = true;

    # calling function csrf_verify() untuk pastikan request review ni betul-betul dari page kita sendiri
    if (!csrf_verify()) {
        $reviewError = 'Your session expired. Please try again.';
        $verifiedOk = false;
    } elseif ($rating === false || $rating < 1 || $rating > 5) {
        # check rating tak valid atau bukan dalam julat 1 hingga 5
        $reviewError = 'Please choose a rating between 1 and 5 stars.';
        $verifiedOk = false;
    } elseif ($hasBookingRef) {
        # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query sahkan booking ni memang dah checked_out
        $stmt = $pdo->prepare(
            "SELECT b.booking_id FROM booking b JOIN customer c ON c.customer_id = b.customer_id
             WHERE b.booking_id = :ref AND c.phone = :phone AND b.booking_status = 'checked_out'"
        );
        # calling method execute() dari object $stmt untuk jalankan query sahkan booking
        $stmt->execute(['ref' => $rRef, 'phone' => $rPhone]);
        # calling method fetch() dari object $stmt untuk check booking wujud & layak untuk direview
        if (!$stmt->fetch()) {
            $reviewError = 'We could not verify that booking for a review.';
            $verifiedOk = false;
        } else {
            $bookingIdForReview = $rRef;
        }
    }

    # check semua validation review lepas baru boleh proses upload gambar & simpan review
    if ($verifiedOk) {
        # assign null ke $imagePath sbb belum tentu customer upload gambar
        $imagePath = null;

        # check customer ada upload gambar review dan upload tu berjaya tanpa error
        if (!empty($_FILES['review_image']['name']) && $_FILES['review_image']['error'] === UPLOAD_ERR_OK) {
            # assign data fail upload ke $file untuk senang rujuk
            $file = $_FILES['review_image'];
            # assign array extension yang dibenarkan ke $allowedExt
            $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
            # calling function pathinfo() & strtolower() that assign to variable name $ext untuk ambil extension fail dalam huruf kecil
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            # assign had saiz maksimum 5MB ke $maxSize
            $maxSize = 5 * 1024 * 1024;

            # check extension dibenarkan, saiz fail dalam had, dan calling function getimagesize() untuk pastikan fail memang gambar sebenar
            if (in_array($ext, $allowedExt, true) && $file['size'] > 0 && $file['size'] <= $maxSize && @getimagesize($file['tmp_name']) !== false) {
                # assign path destinasi folder upload ke $destDir
                $destDir = __DIR__ . '/../assets/uploads/reviews/';
                # calling function is_dir() untuk check folder destinasi wujud ke tidak
                if (!is_dir($destDir)) {
                    # calling function mkdir() untuk cipta folder destinasi kalau belum wujud
                    mkdir($destDir, 0755, true);
                }
                # calling function bin2hex() & random_bytes() that assign to variable name $filename untuk jana nama fail unik
                $filename = 'review_' . ($bookingIdForReview ?? 'anon') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

                # calling function move_uploaded_file() untuk pindah fail dari tmp ke folder destinasi
                if (move_uploaded_file($file['tmp_name'], $destDir . $filename)) {
                    $imagePath = 'assets/uploads/reviews/' . $filename;
                }
            }
        }

        try {
            # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query insert review baru
            $stmt = $pdo->prepare(
                'INSERT INTO review (booking_id, rating, comment, display_name, image_path)
                 VALUES (:booking_id, :rating, :comment, :display_name, :image_path)'
            );
            # calling method execute() dari object $stmt untuk simpan data review ke database
            $stmt->execute([
                'booking_id' => $bookingIdForReview,
                'rating' => $rating,
                'comment' => $comment !== '' ? $comment : null,
                'display_name' => $displayName,
                'image_path' => $imagePath,
            ]);
        } catch (PDOException $e) {
            # check kod error 23000 (duplicate entry) untuk kesan customer dah review booking ni sebelum ni
            if ($e->getCode() === '23000') {
                $reviewError = 'You have already reviewed this booking.';
            } else {
                # calling function error_log() untuk simpan mesej error dalam log server
                error_log('Failed to save review: ' . $e->getMessage());
                $reviewError = 'Something went wrong saving your review. Please try again later.';
            }

            # check ada gambar yang dah upload tadi, kena padam balik sbb insert review gagal
            if ($imagePath && is_file(__DIR__ . '/../' . $imagePath)) {
                # calling function unlink() untuk padam fail gambar yang dah upload sbb review gagal disimpan
                unlink(__DIR__ . '/../' . $imagePath);
            }
        }
    }

    # check takde error review, terus redirect balik ke page mybooking atau homepage
    if (!$reviewError) {
        # calling function header() untuk redirect browser lepas review berjaya dihantar
        header('Location: ' . ($hasBookingRef ? 'mybooking.php?ref=' . $rRef . '&phone=' . urlencode($rPhone) . '&reviewed=1' : '../index.php'));
        exit;
    }

    # assign balik value $rRef & $rPhone ke $ref & $phone supaya page boleh papar semula data yang sama
    $ref = (string) $rRef;
    $phone = $rPhone;
}

# check $phone tak kosong untuk tentukan customer cuba cari booking dia
$lookupAttempted = $phone !== '';
# assign null ke $lookupError sbb belum ada error carian lagi
$lookupError = null;
# assign null ke $booking sbb belum jumpa booking lagi
$booking = null;
# assign array kosong ke $items untuk simpan item booking (accommodation yang ditempah)
$items = [];
# assign null ke $existingReview sbb belum tahu ada review sedia ada ke tidak
$existingReview = null;
# assign null ke $latestPaymentStatus sbb belum tahu status payment terkini
$latestPaymentStatus = null;
# assign 0.0 ke $amountPaid untuk default jumlah dibayar
$amountPaid = 0.0;

# check customer memang buat carian booking guna phone
if ($lookupAttempted) {
    # check phone kosong (jaga-jaga walaupun $lookupAttempted based on phone tak kosong)
    if ($phone === '') {
        $lookupError = 'Please enter the phone number used to book.';
    } else {
        # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query cari booking terkini ikut phone
        $stmt = $pdo->prepare(
            'SELECT b.*, c.full_name, c.phone, c.plate_num
             FROM booking b
             JOIN customer c ON c.customer_id = b.customer_id
             WHERE c.phone = :phone
             ORDER BY b.booking_id DESC
             LIMIT 1'
        );
        # calling method execute() dari object $stmt untuk jalankan query cari booking
        $stmt->execute(['phone' => $phone]);
        # calling method fetch() dari object $stmt that assign to variable name $booking untuk ambil 1 row booking terkini
        $booking = $stmt->fetch();

        # check takde booking jumpa untuk phone ni
        if (!$booking) {
            $lookupError = 'No booking found for that phone number. Please double-check and try again.';
        } else {
            # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query ambil item accommodation dalam booking ni
            $stmt = $pdo->prepare(
                'SELECT bi.quantity, bi.price, a.accommodation_name, a.accommodation_type,
                        a.price AS nightly_price, a.price_weekend AS nightly_price_weekend
                 FROM booking_item bi
                 JOIN accommodation a ON a.accommodation_id = bi.accommodation_id
                 WHERE bi.booking_id = :id'
            );
            # calling method execute() dari object $stmt untuk jalankan query ambil item booking
            $stmt->execute(['id' => $booking['booking_id']]);
            # calling method fetchAll() dari object $stmt that assign to variable name $items untuk ambil semua row item booking
            $items = $stmt->fetchAll();

            # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query check review sedia ada untuk booking ni
            $stmt = $pdo->prepare('SELECT rating, comment, display_name, image_path, review_date FROM review WHERE booking_id = :id');
            # calling method execute() dari object $stmt untuk jalankan query check review
            $stmt->execute(['id' => $booking['booking_id']]);
            # calling method fetch() dari object $stmt that assign to variable name $existingReview untuk ambil review kalau ada, kalau tiada guna null
            $existingReview = $stmt->fetch() ?: null;

            # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query ambil status payment terkini
            $stmt = $pdo->prepare('SELECT payment_status FROM payment WHERE booking_id = :id ORDER BY payment_id DESC LIMIT 1');
            # calling method execute() dari object $stmt untuk jalankan query ambil status payment
            $stmt->execute(['id' => $booking['booking_id']]);
            # calling method fetchColumn() dari object $stmt that assign to variable name $latestPaymentStatus untuk ambil status payment terkini
            $latestPaymentStatus = $stmt->fetchColumn() ?: null;

            # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query ambil jumlah deposit yang dah dibayar
            $stmt = $pdo->prepare("SELECT deposit_paid FROM payment WHERE booking_id = :id AND payment_status = 'paid' ORDER BY payment_id DESC LIMIT 1");
            # calling method execute() dari object $stmt untuk jalankan query ambil deposit dibayar
            $stmt->execute(['id' => $booking['booking_id']]);
            # calling method fetchColumn() dari object $stmt that assign to variable name $amountPaid untuk ambil jumlah dibayar, default 0 kalau tiada
            $amountPaid = (float) ($stmt->fetchColumn() ?: 0);
        }
    }
}

# assign default 1 malam ke $nights sbb belum tentu ada booking
$nights = 1;
# assign default struktur $stay untuk elak error kalau takde booking
# check ada booking yang dijumpai baru kira nights & harga sebenar
if ($booking) {
    # calling function new DateTime() that assign to variable name $checkIn untuk tukar tarikh check-in jadi object DateTime
    $checkIn = new DateTime($booking['check_in']);
    # calling function new DateTime() that assign to variable name $checkOut untuk tukar tarikh check-out jadi object DateTime
    $checkOut = new DateTime($booking['check_out']);
    # calling method diff() untuk kira beza hari antara check-in & check-out, assign to variable name $nights
    $nights = max(1, $checkOut->diff($checkIn)->days);
}

# calling function isset() untuk check ada parameter 'reviewed' dalam url, assign to variable name $reviewSubmitted
$reviewSubmitted = isset($_GET['reviewed']);
# assign value '../' ke variable $base untuk set path relative balik ke root folder
$base = '../';
# assign 'mybooking' ke $active untuk highlight nav item MyBooking
$active = 'mybooking';
# assign value title page ke $pageTitle untuk papar kat tag <title> dan header
$pageTitle = 'MyBooking — Casadive Villa';
# assign path css khas untuk page ni ke $pageCss
$pageCss = 'style/mybooking.css';

# calling function require() untuk load fail view mybooking supaya papar html page ni
require __DIR__ . '/views/mybooking.view.php';
