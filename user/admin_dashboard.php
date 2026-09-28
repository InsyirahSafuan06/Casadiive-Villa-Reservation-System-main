<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/email_notify.php';
require_login(['manager']);

$user = current_user();
$validStatuses = ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled'];
$updated = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
    $newStatus = $_POST['booking_status'] ?? '';

    if (csrf_verify() && $bookingId && in_array($newStatus, $validStatuses, true)) {
        $current = $pdo->prepare('SELECT booking_status FROM booking WHERE booking_id = :id');
        $current->execute(['id' => $bookingId]);
        $previousStatus = $current->fetchColumn();

        $stmt = $pdo->prepare('UPDATE booking SET booking_status = :status WHERE booking_id = :id');
        $stmt->execute(['status' => $newStatus, 'id' => $bookingId]);

        if ($previousStatus !== false && $previousStatus !== $newStatus) {
            send_status_email($pdo, $bookingId, $newStatus);
        }
    }

    header('Location: admin_dashboard.php?updated=1');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'process_refund') {
    $bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
    $refundAmount = filter_var($_POST['refund_amount'] ?? '', FILTER_VALIDATE_FLOAT);
    $note = trim((string) ($_POST['note'] ?? ''));

    if (csrf_verify() && $bookingId && $refundAmount !== false && $refundAmount >= 0) {
        $stmt = $pdo->prepare(
            "SELECT b.booking_status,
                    (SELECT p.payment_status FROM payment p WHERE p.booking_id = b.booking_id ORDER BY p.payment_id DESC LIMIT 1) AS latest_payment_status
             FROM booking b WHERE b.booking_id = :id"
        );
        $stmt->execute(['id' => $bookingId]);
        $row = $stmt->fetch();

        if ($row && payment_needs_refund($row['booking_status'], $row['latest_payment_status'])) {
            $stmt = $pdo->prepare(
                'INSERT INTO payment (booking_id, deposit_paid, payment_status, receipt) VALUES (:booking_id, :amount, :status, :receipt)'
            );
            $stmt->execute([
                'booking_id' => $bookingId,
                'amount' => $refundAmount,
                'status' => 'refunded',
                'receipt' => $note !== '' ? $note : ('Refund processed by ' . $user['fullname']),
            ]);
        }
    }

    header('Location: admin_dashboard.php?refunded=1');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_review') {
    $reviewId = filter_input(INPUT_POST, 'review_id', FILTER_VALIDATE_INT);

    if (csrf_verify() && $reviewId) {
        $stmt = $pdo->prepare('SELECT image_path FROM review WHERE review_id = :id');
        $stmt->execute(['id' => $reviewId]);
        $imagePath = $stmt->fetchColumn();

        $stmt = $pdo->prepare('DELETE FROM review WHERE review_id = :id');
        $stmt->execute(['id' => $reviewId]);

        if ($imagePath) {
            $fullPath = __DIR__ . '/../' . $imagePath;
            if (is_file($fullPath)) {
                unlink($fullPath);
            }
        }
    }

    header('Location: admin_dashboard.php?reviewdeleted=1');
    exit;
}

$galleryError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_gallery_image') {
    if (!csrf_verify()) {
        $galleryError = 'Your session expired. Please try again.';
    } else {
        $result = save_gallery_upload($pdo, $_FILES['gallery_image'] ?? null, trim((string) ($_POST['caption'] ?? '')), (int) $user['user_id']);
        if ($result === true) {
            header('Location: admin_dashboard.php?galleryadded=1');
            exit;
        }
        $galleryError = $result;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_gallery_image') {
    $galleryId = filter_input(INPUT_POST, 'gallery_id', FILTER_VALIDATE_INT);

    if (csrf_verify() && $galleryId && delete_gallery_image($pdo, $galleryId)) {
        header('Location: admin_dashboard.php?gallerydeleted=1');
        exit;
    }
    $galleryError = 'We could not remove that image. Please try again.';
}

$updated = isset($_GET['updated']);
$accountCreated = isset($_GET['created']);
$accountSaved = isset($_GET['saved']);
$accountDeleted = isset($_GET['deleted']);
$accCreated = isset($_GET['acccreated']);
$accSaved = isset($_GET['accsaved']);
$accDeleted = isset($_GET['accdeleted']);
$reviewDeleted = isset($_GET['reviewdeleted']);
$galleryAdded = isset($_GET['galleryadded']);
$galleryDeleted = isset($_GET['gallerydeleted']);
$refunded = isset($_GET['refunded']);

$bookings = $pdo->query(
    "SELECT b.booking_id, c.full_name, c.phone, b.check_in, b.check_out, b.total_guest,
            b.total_amount, b.deposit_amount, b.booking_status, b.addon_bbq, b.addon_mattress, b.discount_amount,
            GROUP_CONCAT(a.accommodation_name SEPARATOR ', ') AS accommodations,
            (SELECT p.payment_status FROM payment p WHERE p.booking_id = b.booking_id ORDER BY p.payment_id DESC LIMIT 1) AS latest_payment_status,
            (SELECT p.deposit_paid FROM payment p WHERE p.booking_id = b.booking_id AND p.payment_status = 'paid' ORDER BY p.payment_id DESC LIMIT 1) AS amount_paid
     FROM booking b
     JOIN customer c ON c.customer_id = b.customer_id
     LEFT JOIN booking_item bi ON bi.booking_id = b.booking_id
     LEFT JOIN accommodation a ON a.accommodation_id = bi.accommodation_id
     GROUP BY b.booking_id
     ORDER BY b.booking_id DESC
     LIMIT 50"
)->fetchAll();

$accommodations = $pdo->query(
    'SELECT accommodation_id, accommodation_name, accommodation_type, price, capacity, status
     FROM accommodation ORDER BY accommodation_type, accommodation_id'
)->fetchAll();

try {
    $galleryImages = $pdo->query(
        'SELECT gallery_id, image_path, caption FROM gallery ORDER BY gallery_id DESC'
    )->fetchAll();
} catch (PDOException $e) {
    error_log('Failed to load gallery images: ' . $e->getMessage());
    $galleryImages = [];
}

$sentLookup = [];
foreach ($pdo->query(
    "SELECT booking_id, notification_type, MAX(sent_date) AS last_sent
     FROM notification_status
     WHERE status = 'sent' AND channel = 'whatsapp'
     GROUP BY booking_id, notification_type"
) as $row) {
    $sentLookup[$row['booking_id']][$row['notification_type']] = $row['last_sent'];
}

$users = $pdo->query(
    'SELECT user_id, username, fullname, email, role, status, created_at FROM user ORDER BY user_id'
)->fetchAll();

try {
    $reviews = $pdo->query(
        "SELECT r.review_id, r.booking_id, r.rating, r.comment, r.display_name, r.image_path, r.review_date, c.full_name
         FROM review r
         LEFT JOIN booking b ON b.booking_id = r.booking_id
         LEFT JOIN customer c ON c.customer_id = b.customer_id
         ORDER BY r.review_date DESC"
    )->fetchAll();
} catch (PDOException $e) {
    error_log('Failed to load admin dashboard reviews: ' . $e->getMessage());
    $reviews = [];
}

require __DIR__ . '/views/admin_dashboard.view.php';
