<?php
# calling function require_once() untuk load fail db.php supaya boleh guna $pdo
require_once __DIR__ . '/../includes/db.php';
# calling function require_once() untuk load fail auth.php supaya boleh guna current_user(), require_login(), csrf_verify()
require_once __DIR__ . '/../includes/auth.php';
# calling function require_once() untuk load fail helpers.php supaya boleh guna save_gallery_upload(), delete_gallery_image(), payment_needs_refund()
require_once __DIR__ . '/../includes/helpers.php';
# calling function require_once() untuk load fail email_notify.php supaya boleh guna send_status_email()
require_once __DIR__ . '/../includes/email_notify.php';
# calling function require_login() untuk pastikan hanya manager je boleh buka page ni
require_login(['manager']);

# calling function current_user() that assign to variable name $user untuk tahu siapa yang sedang login
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_task') {
    if (!csrf_verify()) {
        http_response_code(400);
        exit('Invalid security token. Please refresh and try again.');
    }

    $titleInput = $_POST['title'] ?? '';
    $title = is_string($titleInput) ? trim($titleInput) : '';
    $assigneeInput = $_POST['assigned_to'] ?? '';
    $assignedTo = filter_var(is_scalar($assigneeInput) ? (string) $assigneeInput : '', FILTER_VALIDATE_INT);

    if ($title === '' || strlen($title) > 255 || $assignedTo === false || $assignedTo < 1) {
        header('Location: admin_dashboard.php?taskerror=1');
        exit;
    }

    $staffCheck = $pdo->prepare("SELECT user_id FROM user WHERE user_id = :id AND role = 'staff' AND status = 'active'");
    $staffCheck->execute(['id' => $assignedTo]);
    if (!$staffCheck->fetchColumn()) {
        header('Location: admin_dashboard.php?taskerror=1');
        exit;
    }

    $stmt = $pdo->prepare('INSERT INTO staff_task (title, assigned_to) VALUES (:title, :assigned_to)');
    $stmt->execute(['title' => $title, 'assigned_to' => $assignedTo]);
    header('Location: admin_dashboard.php?taskcreated=1');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_task') {
    if (!csrf_verify()) {
        http_response_code(400);
        exit('Invalid security token. Please refresh and try again.');
    }

    $taskId = filter_input(INPUT_POST, 'task_id', FILTER_VALIDATE_INT);
    if (!$taskId || $taskId < 1) {
        header('Location: admin_dashboard.php?taskerror=1');
        exit;
    }

    $stmt = $pdo->prepare('DELETE FROM staff_task WHERE task_id = :id');
    $stmt->execute(['id' => $taskId]);
    if ($stmt->rowCount() !== 1) {
        header('Location: admin_dashboard.php?taskerror=1');
        exit;
    }

    header('Location: admin_dashboard.php?taskdeleted=1');
    exit;
}

# assign array status booking yang valid ke $validStatuses untuk dipakai semasa validate input
$validStatuses = ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled'];
# assign value false ke $updated untuk flag default (belum ada update)
$updated = false;

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
    header('Location: admin_dashboard.php?updated=1');
    exit;
}
# check kalau request POST dan action yang dihantar ialah 'process_refund'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'process_refund') {
    # calling function filter_input() that assign to variable name $bookingId untuk ambil & sahkan id booking dari form
    $bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
    # calling function filter_var() that assign to variable name $refundAmount untuk ambil & sahkan jumlah refund dari form
    $refundAmount = filter_var($_POST['refund_amount'] ?? '', FILTER_VALIDATE_FLOAT);
    # calling function trim() that assign to variable name $note untuk bersihkan nota refund dari form
    $note = trim((string) ($_POST['note'] ?? ''));

    # check csrf sah, id booking wujud, dan jumlah refund memang nombor positif
    if (csrf_verify() && $bookingId && $refundAmount !== false && $refundAmount >= 0) {
        # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query ambil status booking & status payment terkini
        $stmt = $pdo->prepare(
            "SELECT b.booking_status,
                    (SELECT p.payment_status FROM payment p WHERE p.booking_id = b.booking_id ORDER BY p.payment_id DESC LIMIT 1) AS latest_payment_status
             FROM booking b WHERE b.booking_id = :id"
        );
        # calling method execute() dari object $stmt untuk jalankan query, isi placeholder :id dengan $bookingId
        $stmt->execute(['id' => $bookingId]);
        # calling method fetch() dari object $stmt that assign to variable name $row untuk ambil hasil status booking & payment
        $row = $stmt->fetch();

        # check row wujud dan calling function payment_needs_refund() untuk pastikan booking ni memang layak refund
        if ($row && payment_needs_refund($row['booking_status'], $row['latest_payment_status'])) {
            # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query insert rekod refund baru
            $stmt = $pdo->prepare(
                'INSERT INTO payment (booking_id, deposit_paid, payment_status, receipt) VALUES (:booking_id, :amount, :status, :receipt)'
            );
            # calling method execute() dari object $stmt untuk simpan rekod refund ke table payment
            $stmt->execute([
                'booking_id' => $bookingId,
                'amount' => $refundAmount,
                'status' => 'refunded',
                'receipt' => $note !== '' ? $note : ('Refund processed by ' . $user['fullname']),
            ]);
        }
    }

    # calling function header() untuk redirect balik ke dashboard dengan flag refunded=1
    header('Location: admin_dashboard.php?refunded=1');
    exit;
}
# check kalau request POST dan action yang dihantar ialah 'delete_review'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_review') {
    # calling function filter_input() that assign to variable name $reviewId untuk ambil & sahkan id review dari form
    $reviewId = filter_input(INPUT_POST, 'review_id', FILTER_VALIDATE_INT);

    # check csrf token sah dan id review memang wujud
    if (csrf_verify() && $reviewId) {
        # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query ambil path gambar review
        $stmt = $pdo->prepare('SELECT image_path FROM review WHERE review_id = :id');
        # calling method execute() dari object $stmt untuk jalankan query, isi placeholder :id dengan $reviewId
        $stmt->execute(['id' => $reviewId]);
        # calling method fetchColumn() dari object $stmt that assign to variable name $imagePath untuk ambil path gambar review
        $imagePath = $stmt->fetchColumn();

        # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query padam review dari database
        $stmt = $pdo->prepare('DELETE FROM review WHERE review_id = :id');
        # calling method execute() dari object $stmt untuk padam review dari database
        $stmt->execute(['id' => $reviewId]);

        # check kalau review ni ada gambar
        if ($imagePath) {
            # assign value path penuh gambar ke $fullPath untuk cari lokasi fail sebenar
            $fullPath = __DIR__ . '/../' . $imagePath;
            # calling function is_file() untuk check fail gambar tu memang wujud
            if (is_file($fullPath)) {
                # calling function unlink() untuk padam fail gambar dari server
                unlink($fullPath);
            }
        }
    }

    # calling function header() untuk redirect balik ke dashboard dengan flag reviewdeleted=1
    header('Location: admin_dashboard.php?reviewdeleted=1');
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
            header('Location: admin_dashboard.php?galleryadded=1');
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
        header('Location: admin_dashboard.php?gallerydeleted=1');
        exit;
    }
    # assign mesej error ke $galleryError sebab gagal padam gambar gallery
    $galleryError = 'We could not remove that image. Please try again.';
}

# calling function isset() that assign to variable name $updated untuk check query string 'updated' ada ke tak (lepas redirect update status)
$updated = isset($_GET['updated']);
# calling function isset() that assign to variable name $accountCreated untuk check query string 'created' ada ke tak
$accountCreated = isset($_GET['created']);
# calling function isset() that assign to variable name $accountSaved untuk check query string 'saved' ada ke tak
$accountSaved = isset($_GET['saved']);
# calling function isset() that assign to variable name $accountDeleted untuk check query string 'deleted' ada ke tak
$accountDeleted = isset($_GET['deleted']);
# calling function isset() that assign to variable name $accCreated untuk check query string 'acccreated' ada ke tak
$accCreated = isset($_GET['acccreated']);
# calling function isset() that assign to variable name $accSaved untuk check query string 'accsaved' ada ke tak
$accSaved = isset($_GET['accsaved']);
# calling function isset() that assign to variable name $accDeleted untuk check query string 'accdeleted' ada ke tak
$accDeleted = isset($_GET['accdeleted']);
# calling function isset() that assign to variable name $reviewDeleted untuk check query string 'reviewdeleted' ada ke tak
$reviewDeleted = isset($_GET['reviewdeleted']);
# calling function isset() that assign to variable name $galleryAdded untuk check query string 'galleryadded' ada ke tak
$galleryAdded = isset($_GET['galleryadded']);
# calling function isset() that assign to variable name $galleryDeleted untuk check query string 'gallerydeleted' ada ke tak
$galleryDeleted = isset($_GET['gallerydeleted']);
# calling function isset() that assign to variable name $refunded untuk check query string 'refunded' ada ke tak
$refunded = isset($_GET['refunded']);
$taskCreated = isset($_GET['taskcreated']);
$taskDeleted = isset($_GET['taskdeleted']);
$taskError = isset($_GET['taskerror']);

# calling method query() dari object $pdo that assign to variable name $bookings untuk ambil 50 booking terkini sekali dengan detail customer & payment
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

# calling method query() dari object $pdo that assign to variable name $accommodations untuk ambil semua senarai villa/campsite
$accommodations = $pdo->query(
    'SELECT accommodation_id, accommodation_name, accommodation_type, price, capacity, status
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

# assign array kosong ke $sentLookup untuk simpan senarai notifikasi whatsapp yang dah dihantar
$sentLookup = [];
# calling method query() dari object $pdo untuk ambil rekod notifikasi terakhir tiap booking, loop guna foreach
foreach ($pdo->query(
    "SELECT booking_id, notification_type, MAX(sent_date) AS last_sent
     FROM notification_status
     WHERE status = 'sent' AND channel = 'whatsapp'
     GROUP BY booking_id, notification_type"
) as $row) {
    # assign value $row['last_sent'] ke dalam array $sentLookup ikut booking_id & notification_type
    $sentLookup[$row['booking_id']][$row['notification_type']] = $row['last_sent'];
}

# calling method query() dari object $pdo that assign to variable name $users untuk ambil semua senarai akaun staff/manager
$users = $pdo->query(
    'SELECT user_id, username, fullname, email, role, status, created_at FROM user ORDER BY user_id'
)->fetchAll();

$activeStaff = $pdo->query(
    "SELECT user_id, fullname FROM user WHERE role = 'staff' AND status = 'active' ORDER BY fullname"
)->fetchAll();
$tasks = $pdo->query(
    'SELECT t.task_id, t.title, t.status, t.created_at, u.fullname AS staff_name
     FROM staff_task t
     LEFT JOIN user u ON u.user_id = t.assigned_to
     ORDER BY t.task_id DESC'
)->fetchAll();
$taskDone = count(array_filter($tasks, static fn(array $task): bool => $task['status'] === 'done'));
$taskOpen = count($tasks) - $taskDone;
$occupancyData = load_current_occupancy($pdo, $_GET['occ_date'] ?? null);
$occupancyDate = $occupancyData['date'];
$occupancyRooms = $occupancyData['rooms'];
$occupancyBookings = $occupancyData['bookings'];

try {
    # calling method query() dari object $pdo that assign to variable name $reviews untuk ambil semua review customer
    $reviews = $pdo->query(
        "SELECT r.review_id, r.booking_id, r.rating, r.comment, r.display_name, r.image_path, r.review_date, c.full_name
         FROM review r
         LEFT JOIN booking b ON b.booking_id = r.booking_id
         LEFT JOIN customer c ON c.customer_id = b.customer_id
         ORDER BY r.review_date DESC"
    )->fetchAll();
} catch (PDOException $e) {
    # calling function error_log() untuk catat error kalau gagal ambil review
    error_log('Failed to load admin dashboard reviews: ' . $e->getMessage());
    # assign array kosong ke $reviews sebagai fallback bila query gagal
    $reviews = [];
}

# calling function require() untuk load view admin_dashboard.view.php dan papar dashboard admin
require __DIR__ . '/views/admin_dashboard.view.php';
