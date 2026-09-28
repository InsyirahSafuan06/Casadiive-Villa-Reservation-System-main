<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/email_notify.php';
require_login(['staff', 'manager']);

$user = current_user();
$validStatuses = ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled'];
$validAccStatuses = ['available', 'unavailable', 'maintenance'];
$validPaymentStatuses = ['pending', 'partial', 'paid', 'refunded', 'failed'];

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

    header('Location: staff_dashboard.php?updated=1');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_acc_status') {
    $accId = filter_input(INPUT_POST, 'accommodation_id', FILTER_VALIDATE_INT);
    $newAccStatus = $_POST['acc_status'] ?? '';

    if (csrf_verify() && $accId && in_array($newAccStatus, $validAccStatuses, true)) {
        $stmt = $pdo->prepare('UPDATE accommodation SET status = :status WHERE accommodation_id = :id');
        $stmt->execute(['status' => $newAccStatus, 'id' => $accId]);
    }

    header('Location: staff_dashboard.php?accupdated=1');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'record_payment') {
    $bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
    $depositPaid = filter_var($_POST['deposit_paid'] ?? '', FILTER_VALIDATE_FLOAT);
    $paymentStatus = (string) ($_POST['payment_status'] ?? '');
    $receipt = trim((string) ($_POST['receipt'] ?? ''));

    if (csrf_verify() && $bookingId && $depositPaid !== false && $depositPaid >= 0 && in_array($paymentStatus, $validPaymentStatuses, true)) {
        $stmt = $pdo->prepare(
            'INSERT INTO payment (booking_id, deposit_paid, payment_status, receipt) VALUES (:booking_id, :deposit_paid, :status, :receipt)'
        );
        $stmt->execute([
            'booking_id' => $bookingId,
            'deposit_paid' => $depositPaid,
            'status' => $paymentStatus,
            'receipt' => $receipt !== '' ? $receipt : null,
        ]);
    }

    header('Location: staff_dashboard.php?paymentrecorded=1');
    exit;
}

$galleryError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_gallery_image') {
    if (!csrf_verify()) {
        $galleryError = 'Your session expired. Please try again.';
    } else {
        $result = save_gallery_upload($pdo, $_FILES['gallery_image'] ?? null, trim((string) ($_POST['caption'] ?? '')), (int) $user['user_id']);
        if ($result === true) {
            header('Location: staff_dashboard.php?galleryadded=1');
            exit;
        }
        $galleryError = $result;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_gallery_image') {
    $galleryId = filter_input(INPUT_POST, 'gallery_id', FILTER_VALIDATE_INT);

    if (csrf_verify() && $galleryId && delete_gallery_image($pdo, $galleryId)) {
        header('Location: staff_dashboard.php?gallerydeleted=1');
        exit;
    }
    $galleryError = 'We could not remove that image. Please try again.';
}

$updated = isset($_GET['updated']);
$accUpdated = isset($_GET['accupdated']);
$paymentRecorded = isset($_GET['paymentrecorded']);
$galleryAdded = isset($_GET['galleryadded']);
$galleryDeleted = isset($_GET['gallerydeleted']);
$refundBookingId = filter_input(INPUT_GET, 'refund_booking', FILTER_VALIDATE_INT) ?: null;

$bookings = $pdo->query(
    "SELECT b.booking_id, c.full_name, c.phone, c.plate_num, b.check_in, b.check_out, b.total_guest,
            b.total_amount, b.deposit_amount, b.booking_status, b.addon_bbq, b.addon_mattress, b.discount_amount,
            GROUP_CONCAT(a.accommodation_name SEPARATOR ', ') AS accommodations,
            (SELECT p.payment_status FROM payment p WHERE p.booking_id = b.booking_id ORDER BY p.payment_id DESC LIMIT 1) AS latest_payment_status
     FROM booking b
     JOIN customer c ON c.customer_id = b.customer_id
     LEFT JOIN booking_item bi ON bi.booking_id = b.booking_id
     LEFT JOIN accommodation a ON a.accommodation_id = bi.accommodation_id
     GROUP BY b.booking_id
     ORDER BY b.booking_id DESC
     LIMIT 50"
)->fetchAll();

$accommodations = $pdo->query(
    'SELECT accommodation_id, accommodation_name, accommodation_type, capacity, status
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

$recentPayments = $pdo->query(
    "SELECT p.payment_id, p.booking_id, p.deposit_paid, p.payment_date, p.payment_status, p.receipt, c.full_name
     FROM payment p
     JOIN booking b ON b.booking_id = p.booking_id
     JOIN customer c ON c.customer_id = b.customer_id
     ORDER BY p.payment_id DESC
     LIMIT 20"
)->fetchAll();

require __DIR__ . '/views/staff_dashboard.view.php';
