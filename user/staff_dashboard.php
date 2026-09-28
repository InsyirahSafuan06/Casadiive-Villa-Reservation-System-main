<?php
# calling function require_once() untuk load fail db.php supaya boleh guna $pdo
require_once __DIR__ . '/../includes/db.php';
# calling function require_once() untuk load fail auth.php supaya boleh guna current_user(), require_login(), csrf_verify()
require_once __DIR__ . '/../includes/auth.php';
# calling function require_once() untuk load fail helpers.php supaya boleh guna save_gallery_upload(), delete_gallery_image()
require_once __DIR__ . '/../includes/helpers.php';
# calling function require_once() untuk load fail email_notify.php supaya boleh guna send_status_email()
require_once __DIR__ . '/../includes/email_notify.php';
# calling function require_login() untuk pastikan staff & manager je boleh buka page ni
require_login(['staff', 'manager']);

# calling function current_user() that assign to variable name $user untuk tahu siapa yang sedang login
$user = current_user();
# assign array status booking yang valid ke $validStatuses untuk dipakai semasa validate input
$validStatuses = ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled'];
# assign array status accommodation yang valid ke $validAccStatuses untuk dipakai semasa validate input
$validAccStatuses = ['available', 'unavailable', 'maintenance'];
# assign array status payment yang valid ke $validPaymentStatuses untuk dipakai semasa validate input
$validPaymentStatuses = ['pending', 'partial', 'paid', 'refunded', 'failed'];

# check kalau request POST dan action yang dihantar ialah 'update_status'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    # calling function filter_input() that assign to variable name $bookingId untuk ambil & sahkan id booking dari form
    $bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
    # ambil value $_POST['booking_status'] that assign to variable name $newStatus
    $newStatus = $_POST['booking_status'] ?? '';

    # check csrf token sah, id booking wujud, dan status baru memang dalam list status yang valid
    if (csrf_verify() && $bookingId && in_array($newStatus, $validStatuses, true)) {
        # calling method prepare() dari object $pdo that assign to variable name $current untuk sediakan query ambil status booking semasa
        $current = $pdo->prepare('SELECT booking_status FROM booking WHERE booking_id = :id');
        # calling method execute() dari object $current untuk jalankan query, isi placeholder :id dengan $bookingId
        $current->execute(['id' => $bookingId]);
        # calling method fetchColumn() dari object $current that assign to variable name $previousStatus untuk ambil status booking sebelum ditukar
        $previousStatus = $current->fetchColumn();

        # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query update status booking
        $stmt = $pdo->prepare('UPDATE booking SET booking_status = :status WHERE booking_id = :id');
        # calling method execute() dari object $stmt untuk jalankan query update status booking
        $stmt->execute(['status' => $newStatus, 'id' => $bookingId]);

        # check kalau status memang bertukar (bukan sama macam sebelum), baru hantar emel notifikasi
        if ($previousStatus !== false && $previousStatus !== $newStatus) {
            # calling function send_status_email() untuk hantar emel bagitahu customer status booking dah bertukar
            send_status_email($pdo, $bookingId, $newStatus);
        }
    }

    # calling function header() untuk redirect balik ke dashboard dengan flag updated=1
    header('Location: staff_dashboard.php?updated=1');
    exit;
}

# check kalau request POST dan action yang dihantar ialah 'update_acc_status'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_acc_status') {
    # calling function filter_input() that assign to variable name $accId untuk ambil & sahkan id accommodation dari form
    $accId = filter_input(INPUT_POST, 'accommodation_id', FILTER_VALIDATE_INT);
    # ambil value $_POST['acc_status'] that assign to variable name $newAccStatus
    $newAccStatus = $_POST['acc_status'] ?? '';

    # check csrf token sah, id accommodation wujud, dan status baru memang dalam list status yang valid
    if (csrf_verify() && $accId && in_array($newAccStatus, $validAccStatuses, true)) {
        # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query update status accommodation
        $stmt = $pdo->prepare('UPDATE accommodation SET status = :status WHERE accommodation_id = :id');
        # calling method execute() dari object $stmt untuk update status accommodation dalam database
        $stmt->execute(['status' => $newAccStatus, 'id' => $accId]);
    }

    # calling function header() untuk redirect balik ke dashboard dengan flag accupdated=1
    header('Location: staff_dashboard.php?accupdated=1');
    exit;
}

# check kalau request POST dan action yang dihantar ialah 'record_payment'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'record_payment') {
    # calling function filter_input() that assign to variable name $bookingId untuk ambil & sahkan id booking dari form
    $bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
    # calling function filter_var() that assign to variable name $depositPaid untuk ambil & sahkan jumlah deposit dari form
    $depositPaid = filter_var($_POST['deposit_paid'] ?? '', FILTER_VALIDATE_FLOAT);
    # ambil value $_POST['payment_status'] that assign to variable name $paymentStatus
    $paymentStatus = (string) ($_POST['payment_status'] ?? '');
    # calling function trim() that assign to variable name $receipt untuk bersihkan nombor resit dari form
    $receipt = trim((string) ($_POST['receipt'] ?? ''));

    # check csrf sah, id booking wujud, jumlah deposit positif, dan status payment memang valid
    if (csrf_verify() && $bookingId && $depositPaid !== false && $depositPaid >= 0 && in_array($paymentStatus, $validPaymentStatuses, true)) {
        # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query insert rekod payment baru
        $stmt = $pdo->prepare(
            'INSERT INTO payment (booking_id, deposit_paid, payment_status, receipt) VALUES (:booking_id, :deposit_paid, :status, :receipt)'
        );
        # calling method execute() dari object $stmt untuk simpan rekod payment ke table payment
        $stmt->execute([
            'booking_id' => $bookingId,
            'deposit_paid' => $depositPaid,
            'status' => $paymentStatus,
            'receipt' => $receipt !== '' ? $receipt : null,
        ]);
    }

    # calling function header() untuk redirect balik ke dashboard dengan flag paymentrecorded=1
    header('Location: staff_dashboard.php?paymentrecorded=1');
    exit;
}

# assign value null ke $galleryError untuk simpan mesej error upload gallery (kalau ada)
$galleryError = null;

# check kalau request POST dan action yang dihantar ialah 'add_gallery_image'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_gallery_image') {
    # calling function csrf_verify() untuk check token csrf form ni sah ke tidak
    if (!csrf_verify()) {
        # assign mesej error ke $galleryError sebab session dah expired
        $galleryError = 'Your session expired. Please try again.';
    } else {
        # calling function save_gallery_upload() that assign to variable name $result untuk cuba upload gambar gallery baru
        $result = save_gallery_upload($pdo, $_FILES['gallery_image'] ?? null, trim((string) ($_POST['caption'] ?? '')), (int) $user['user_id']);
        # check kalau upload berjaya (pulangkan true)
        if ($result === true) {
            # calling function header() untuk redirect balik ke dashboard dengan flag galleryadded=1
            header('Location: staff_dashboard.php?galleryadded=1');
            exit;
        }
        # assign mesej error dari $result ke $galleryError sebab upload gagal
        $galleryError = $result;
    }
}

# check kalau request POST dan action yang dihantar ialah 'delete_gallery_image'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_gallery_image') {
    # calling function filter_input() that assign to variable name $galleryId untuk ambil & sahkan id gambar gallery dari form
    $galleryId = filter_input(INPUT_POST, 'gallery_id', FILTER_VALIDATE_INT);

    # check csrf sah, id gallery wujud, dan calling function delete_gallery_image() berjaya padam
    if (csrf_verify() && $galleryId && delete_gallery_image($pdo, $galleryId)) {
        # calling function header() untuk redirect balik ke dashboard dengan flag gallerydeleted=1
        header('Location: staff_dashboard.php?gallerydeleted=1');
        exit;
    }
    # assign mesej error ke $galleryError sebab gagal padam gambar gallery
    $galleryError = 'We could not remove that image. Please try again.';
}

# calling function isset() that assign to variable name $updated untuk check query string 'updated' ada ke tak
$updated = isset($_GET['updated']);
# calling function isset() that assign to variable name $accUpdated untuk check query string 'accupdated' ada ke tak
$accUpdated = isset($_GET['accupdated']);
# calling function isset() that assign to variable name $paymentRecorded untuk check query string 'paymentrecorded' ada ke tak
$paymentRecorded = isset($_GET['paymentrecorded']);
# calling function isset() that assign to variable name $galleryAdded untuk check query string 'galleryadded' ada ke tak
$galleryAdded = isset($_GET['galleryadded']);
# calling function isset() that assign to variable name $galleryDeleted untuk check query string 'gallerydeleted' ada ke tak
$galleryDeleted = isset($_GET['gallerydeleted']);
# calling function filter_input() that assign to variable name $refundBookingId untuk ambil id booking dari query string 'refund_booking', null kalau takde
$refundBookingId = filter_input(INPUT_GET, 'refund_booking', FILTER_VALIDATE_INT) ?: null;

# calling method query() dari object $pdo that assign to variable name $bookings untuk ambil 50 booking terkini sekali dengan detail customer & payment
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

# calling method query() dari object $pdo that assign to variable name $accommodations untuk ambil semua senarai villa/campsite
$accommodations = $pdo->query(
    'SELECT accommodation_id, accommodation_name, accommodation_type, capacity, status
     FROM accommodation ORDER BY accommodation_type, accommodation_id'
)->fetchAll();

try {
    # calling method query() dari object $pdo that assign to variable name $galleryImages untuk ambil semua gambar gallery
    $galleryImages = $pdo->query(
        'SELECT gallery_id, image_path, caption FROM gallery ORDER BY gallery_id DESC'
    )->fetchAll();
} catch (PDOException $e) {
    # calling function error_log() untuk catat error kalau gagal ambil gambar gallery
    error_log('Failed to load gallery images: ' . $e->getMessage());
    # assign array kosong ke $galleryImages sebagai fallback bila query gagal
    $galleryImages = [];
}

# calling method query() dari object $pdo that assign to variable name $recentPayments untuk ambil 20 rekod payment terkini
$recentPayments = $pdo->query(
    "SELECT p.payment_id, p.booking_id, p.deposit_paid, p.payment_date, p.payment_status, p.receipt, c.full_name
     FROM payment p
     JOIN booking b ON b.booking_id = p.booking_id
     JOIN customer c ON c.customer_id = b.customer_id
     ORDER BY p.payment_id DESC
     LIMIT 20"
)->fetchAll();

# calling function require() untuk load view staff_dashboard.view.php dan papar dashboard staff
require __DIR__ . '/views/staff_dashboard.view.php';
