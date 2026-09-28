<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/email_notify.php';

$ref = isset($_GET['ref']) ? trim((string) $_GET['ref']) : '';
$phone = isset($_GET['phone']) ? trim((string) $_GET['phone']) : '';
$reviewError = null;
$cancelError = null;

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
        header('Location: ../index.php');
        exit;
    }

    $ref = (string) $cRef;
    $phone = $cPhone;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'review') {
    $rRef = filter_var($_POST['ref'] ?? '', FILTER_VALIDATE_INT);
    $rPhone = trim((string) ($_POST['phone'] ?? ''));
    $rating = filter_var($_POST['rating'] ?? '', FILTER_VALIDATE_INT);
    $comment = trim((string) ($_POST['comment'] ?? ''));

    $isAnonymous = isset($_POST['is_anonymous']);
    $displayName = $isAnonymous ? '' : trim((string) ($_POST['display_name'] ?? ''));
    $displayName = $displayName !== '' ? mb_substr($displayName, 0, 100) : null;

    $hasBookingRef = $rRef !== false && $rPhone !== '';
    $bookingIdForReview = null;
    $verifiedOk = true;

    if (!csrf_verify()) {
        $reviewError = 'Your session expired. Please try again.';
        $verifiedOk = false;
    } elseif ($rating === false || $rating < 1 || $rating > 5) {
        $reviewError = 'Please choose a rating between 1 and 5 stars.';
        $verifiedOk = false;
    } elseif ($hasBookingRef) {
        $stmt = $pdo->prepare(
            "SELECT b.booking_id FROM booking b JOIN customer c ON c.customer_id = b.customer_id
             WHERE b.booking_id = :ref AND c.phone = :phone AND b.booking_status = 'checked_out'"
        );
        $stmt->execute(['ref' => $rRef, 'phone' => $rPhone]);
        if (!$stmt->fetch()) {
            $reviewError = 'We could not verify that booking for a review.';
            $verifiedOk = false;
        } else {
            $bookingIdForReview = $rRef;
        }
    }

    if ($verifiedOk) {
        $imagePath = null;

        if (!empty($_FILES['review_image']['name']) && $_FILES['review_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['review_image'];
            $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $maxSize = 5 * 1024 * 1024;

            if (in_array($ext, $allowedExt, true) && $file['size'] > 0 && $file['size'] <= $maxSize && @getimagesize($file['tmp_name']) !== false) {
                $destDir = __DIR__ . '/../assets/uploads/reviews/';
                if (!is_dir($destDir)) {
                    mkdir($destDir, 0755, true);
                }
                $filename = 'review_' . ($bookingIdForReview ?? 'anon') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

                if (move_uploaded_file($file['tmp_name'], $destDir . $filename)) {
                    $imagePath = 'assets/uploads/reviews/' . $filename;
                }
            }
        }

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO review (booking_id, rating, comment, display_name, image_path)
                 VALUES (:booking_id, :rating, :comment, :display_name, :image_path)'
            );
            $stmt->execute([
                'booking_id' => $bookingIdForReview,
                'rating' => $rating,
                'comment' => $comment !== '' ? $comment : null,
                'display_name' => $displayName,
                'image_path' => $imagePath,
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $reviewError = 'You have already reviewed this booking.';
            } else {
                error_log('Failed to save review: ' . $e->getMessage());
                $reviewError = 'Something went wrong saving your review. Please try again later.';
            }

            if ($imagePath && is_file(__DIR__ . '/../' . $imagePath)) {
                unlink(__DIR__ . '/../' . $imagePath);
            }
        }
    }

    if (!$reviewError) {
        header('Location: ' . ($hasBookingRef ? 'mybooking.php?ref=' . $rRef . '&phone=' . urlencode($rPhone) . '&reviewed=1' : '../index.php'));
        exit;
    }

    $ref = (string) $rRef;
    $phone = $rPhone;
}

$lookupAttempted = $phone !== '';
$lookupError = null;
$booking = null;
$items = [];
$existingReview = null;
$latestPaymentStatus = null;
$amountPaid = 0.0;

if ($lookupAttempted) {
    if ($phone === '') {
        $lookupError = 'Please enter the phone number used to book.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT b.*, c.full_name, c.phone, c.plate_num
             FROM booking b
             JOIN customer c ON c.customer_id = b.customer_id
             WHERE c.phone = :phone
             ORDER BY b.booking_id DESC
             LIMIT 1'
        );
        $stmt->execute(['phone' => $phone]);
        $booking = $stmt->fetch();

        if (!$booking) {
            $lookupError = 'No booking found for that phone number. Please double-check and try again.';
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

            $stmt = $pdo->prepare('SELECT rating, comment, display_name, image_path, review_date FROM review WHERE booking_id = :id');
            $stmt->execute(['id' => $booking['booking_id']]);
            $existingReview = $stmt->fetch() ?: null;

            $stmt = $pdo->prepare('SELECT payment_status FROM payment WHERE booking_id = :id ORDER BY payment_id DESC LIMIT 1');
            $stmt->execute(['id' => $booking['booking_id']]);
            $latestPaymentStatus = $stmt->fetchColumn() ?: null;

            $stmt = $pdo->prepare("SELECT deposit_paid FROM payment WHERE booking_id = :id AND payment_status = 'paid' ORDER BY payment_id DESC LIMIT 1");
            $stmt->execute(['id' => $booking['booking_id']]);
            $amountPaid = (float) ($stmt->fetchColumn() ?: 0);
        }
    }
}

$nights = 1;
$stay = ['weekday_nights' => 1, 'weekend_nights' => 0];
if ($booking) {
    $checkIn = new DateTime($booking['check_in']);
    $checkOut = new DateTime($booking['check_out']);
    $nights = max(1, $checkOut->diff($checkIn)->days);
    $stay = compute_stay_price(1, 1, $checkIn, $checkOut);
}

$reviewSubmitted = isset($_GET['reviewed']);
$base = '../';
$active = 'mybooking';
$pageTitle = 'MyBooking — Casadive Villa';
$pageCss = 'style/mybooking.css';

require __DIR__ . '/views/mybooking.view.php';
