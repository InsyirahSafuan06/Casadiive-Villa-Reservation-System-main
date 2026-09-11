<?php
/**
 * Halaman carian MyBooking.
 * Pelanggan boleh cari tempahan mengikut nombor rujukan dan nombor telefon untuk lihat atau cetak resit.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/email_notify.php';

$ref = isset($_GET['ref']) ? trim((string) $_GET['ref']) : '';
$phone = isset($_GET['phone']) ? trim((string) $_GET['phone']) : '';
$reviewError = null;
$cancelError = null;

// tetamu boleh batalkan booking sendiri guna ref + phone yang sama macam lookup —
// cuma dibenarkan selagi booking tu belum check-in/check-out/dah cancelled
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'cancel') {
    $cRef = filter_var($_POST['ref'] ?? '', FILTER_VALIDATE_INT);
    $cPhone = trim((string) ($_POST['phone'] ?? ''));

    if (!csrf_verify()) {
        $cancelError = 'Your session expired. Please try again.';
    } elseif ($cRef === false || $cPhone === '') {
        $cancelError = 'Invalid booking reference.';
    } else {
        $stmt = $pdo->prepare(
            "SELECT b.booking_id FROM booking b JOIN customer c ON c.customer_id = b.customer_id
             WHERE b.booking_id = :ref AND c.phone = :phone AND b.booking_status IN ('pending', 'confirmed')"
        );
        $stmt->execute(['ref' => $cRef, 'phone' => $cPhone]);
        if (!$stmt->fetch()) {
            $cancelError = 'This booking can no longer be cancelled.';
        } else {
            $stmt = $pdo->prepare("UPDATE booking SET booking_status = 'cancelled' WHERE booking_id = :id");
            $stmt->execute(['id' => $cRef]);
            send_status_email($pdo, $cRef, 'cancelled');
        }
    }

    if (!$cancelError) {
        // redirect balik supaya refresh page tak submit cancel dua kali
        header('Location: mybooking.php?ref=' . $cRef . '&phone=' . urlencode($cPhone));
        exit;
    }

    $ref = (string) $cRef;
    $phone = $cPhone;
}

// tetamu cuma boleh bagi review lepas dah checked_out, dan sekali je untuk setiap booking —
// dua-dua syarat ni kita check dalam query di bawah sebelum cuba INSERT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'review') {
    $rRef = filter_var($_POST['ref'] ?? '', FILTER_VALIDATE_INT);
    $rPhone = trim((string) ($_POST['phone'] ?? ''));
    $rating = filter_var($_POST['rating'] ?? '', FILTER_VALIDATE_INT);
    $comment = trim((string) ($_POST['comment'] ?? ''));

    if (!csrf_verify()) {
        $reviewError = 'Your session expired. Please try again.';
    } elseif ($rRef === false || $rPhone === '') {
        $reviewError = 'Invalid booking reference.';
    } elseif ($rating === false || $rating < 1 || $rating > 5) {
        $reviewError = 'Please choose a rating between 1 and 5 stars.';
    } else {
        $stmt = $pdo->prepare(
            "SELECT b.booking_id FROM booking b JOIN customer c ON c.customer_id = b.customer_id
             WHERE b.booking_id = :ref AND c.phone = :phone AND b.booking_status = 'checked_out'"
        );
        $stmt->execute(['ref' => $rRef, 'phone' => $rPhone]);
        if (!$stmt->fetch()) {
            $reviewError = 'We could not verify that booking for a review.';
        } else {
            // gambar review — customer JS dah tapis kandungan (AI verification client-side)
            // sebelum submit, so field review_image ni sepatutnya cuma sampai kat sini kalau dah
            // lulus. Kat server kita cuma sahkan fail tu betul-betul gambar (bukan re-verify
            // kandungan — takde model AI kat server), sebagai lapisan keselamatan asas je.
            $imagePath = null;

            if (!empty($_FILES['review_image']['name']) && $_FILES['review_image']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['review_image'];
                $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $maxSize = 5 * 1024 * 1024; // 5MB

                if (in_array($ext, $allowedExt, true) && $file['size'] > 0 && $file['size'] <= $maxSize && @getimagesize($file['tmp_name']) !== false) {
                    $destDir = __DIR__ . '/../assets/uploads/reviews/';
                    if (!is_dir($destDir)) {
                        mkdir($destDir, 0755, true);
                    }
                    $filename = 'review_' . $rRef . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

                    if (move_uploaded_file($file['tmp_name'], $destDir . $filename)) {
                        $imagePath = 'assets/uploads/reviews/' . $filename;
                    }
                }
            }

            try {
                // table `review` ada UNIQUE constraint kat booking_id, so kalau cuba review
                // kali kedua untuk booking yang sama, insert ni akan gagal dan masuk catch bawah
                $stmt = $pdo->prepare(
                    'INSERT INTO review (booking_id, rating, comment, image_path)
                     VALUES (:booking_id, :rating, :comment, :image_path)'
                );
                $stmt->execute([
                    'booking_id' => $rRef,
                    'rating' => $rating,
                    'comment' => $comment !== '' ? $comment : null,
                    'image_path' => $imagePath,
                ]);
            } catch (Exception $e) {
                $reviewError = 'You have already reviewed this booking.';
                if ($imagePath && is_file(__DIR__ . '/../' . $imagePath)) {
                    // insert gagal (contoh: dah pernah review) — buang gambar yang dah terlanjur
                    // di-upload tu, elak fail terbiar kat cakera tanpa rekod DB
                    unlink(__DIR__ . '/../' . $imagePath);
                }
            }
        }
    }

    if (!$reviewError) {
        // redirect balik ke page ni juga supaya refresh tak submit review dua kali
        header('Location: mybooking.php?ref=' . $rRef . '&phone=' . urlencode($rPhone));
        exit;
    }

    $ref = (string) $rRef;
    $phone = $rPhone;
}

// booking dicari guna "no rujukan + no phone" yang digunakan masa booking — ni jadi macam
// "password" ringkas supaya tetamu tak boleh tengok booking orang lain just dengan teka ID
$lookupAttempted = $ref !== '' || $phone !== '';
$lookupError = null;
$booking = null;
$items = [];
$existingReview = null;
$latestPaymentStatus = null;
$amountPaid = 0.0;

if ($lookupAttempted) {
    $refId = parse_booking_ref($ref);

    if ($refId === false || $phone === '') {
        $lookupError = 'Please enter a valid booking reference and the phone number used to book.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT b.*, c.full_name, c.phone, c.plate_num
             FROM booking b
             JOIN customer c ON c.customer_id = b.customer_id
             WHERE b.booking_id = :ref AND c.phone = :phone'
        );
        $stmt->execute(['ref' => $refId, 'phone' => $phone]);
        $booking = $stmt->fetch();

        if (!$booking) {
            $lookupError = 'No booking found for that reference number and phone number. Please double-check and try again.';
        } else {
            $stmt = $pdo->prepare(
                'SELECT bi.quantity, bi.price, a.accommodation_name, a.accommodation_type,
                        a.price AS nightly_price, a.price_weekend AS nightly_price_weekend
                 FROM booking_item bi
                 JOIN accommodation a ON a.accommodation_id = bi.accommodation_id
                 WHERE bi.booking_id = :id'
            );
            $stmt->execute(['id' => $booking['booking_id']]);
            $items = $stmt->fetchAll();

            $stmt = $pdo->prepare('SELECT rating, comment, image_path, review_date FROM review WHERE booking_id = :id');
            $stmt->execute(['id' => $booking['booking_id']]);
            $existingReview = $stmt->fetch() ?: null; // ada review sedia ada ke tak untuk booking ni

            $stmt = $pdo->prepare('SELECT payment_status FROM payment WHERE booking_id = :id ORDER BY payment_id DESC LIMIT 1');
            $stmt->execute(['id' => $booking['booking_id']]);
            $latestPaymentStatus = $stmt->fetchColumn() ?: null; // untuk papar status refund kalau booking dah cancel

            $stmt = $pdo->prepare("SELECT deposit_paid FROM payment WHERE booking_id = :id AND payment_status = 'paid' ORDER BY payment_id DESC LIMIT 1");
            $stmt->execute(['id' => $booking['booking_id']]);
            $amountPaid = (float) ($stmt->fetchColumn() ?: 0); // amaun sebenar yang dah dibayar (bayaran penuh, bukan just deposit)
        }
    }
}

$nights = 1;
$stay = ['weekday_nights' => 1, 'weekend_nights' => 0];
if ($booking) {
    $checkIn = new DateTime($booking['check_in']);
    $checkOut = new DateTime($booking['check_out']);
    $nights = max(1, $checkOut->diff($checkIn)->days);
    // trick sikit ni — kita hantar 1/1 sebagai harga sebab kita bukan nak jumlah harga,
    // kita cuma nak tau berapa malam weekday vs weekend untuk bina pecahan harga kat bawah
    $stay = compute_stay_price(1, 1, $checkIn, $checkOut);
}

$base = '../'; // page ni dalam folder customer/, naik satu tahap untuk pergi root
$active = 'mybooking'; // untuk highlight menu "MyBooking" kat navbar
$pageTitle = 'MyBooking — Casadive Villa';
$pageCss = 'style/mybooking.css';

// semua logic dah selesai kat atas ni — baris bawah papar HTML page dia.
// HTML/borang tu disimpan berasingan dalam folder views/ supaya file ni tak jadi terlalu panjang.
require __DIR__ . '/views/mybooking.view.php';
