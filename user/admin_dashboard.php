<?php
/**
 * Halaman dashboard manager.
 * Fail ini memberikan pentadbir ringkasan tempahan, akaun, dan pengurusan penginapan.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/email_notify.php';
require_login(['manager']); // page ni cuma untuk manager, staff biasa tak boleh masuk

$user = current_user();
$validStatuses = ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled'];
$updated = false;

// admin tukar status booking dari dropdown kat table bawah — proses kat sini
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
    $newStatus = $_POST['booking_status'] ?? '';

    if (csrf_verify() && $bookingId && in_array($newStatus, $validStatuses, true)) {
        $current = $pdo->prepare('SELECT booking_status FROM booking WHERE booking_id = :id');
        $current->execute(['id' => $bookingId]);
        $previousStatus = $current->fetchColumn();

        $stmt = $pdo->prepare('UPDATE booking SET booking_status = :status WHERE booking_id = :id');
        $stmt->execute(['status' => $newStatus, 'id' => $bookingId]);

        // hantar emel notification cuma kalau status betul-betul berubah
        if ($previousStatus !== false && $previousStatus !== $newStatus) {
            send_status_email($pdo, $bookingId, $newStatus);
        }
    }

    // redirect balik supaya refresh page tak submit form dua kali
    header('Location: admin_dashboard.php?updated=1');
    exit;
}
// manager je yang boleh padam review (page ni dah require_login(['manager']) kat atas, so takde
// laluan lain customer/staff boleh sampai sini) — buang gambar dari cakera sekali kalau ada,
// elak fail terbiar tanpa rekod DB
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

// flag-flag ni untuk papar mesej "berjaya" lepas redirect dari page lain (contoh: lepas save account)
$updated = isset($_GET['updated']);
$accountCreated = isset($_GET['created']);
$accountSaved = isset($_GET['saved']);
$accountDeleted = isset($_GET['deleted']);
$accCreated = isset($_GET['acccreated']);
$accSaved = isset($_GET['accsaved']);
$accDeleted = isset($_GET['accdeleted']);
$reviewDeleted = isset($_GET['reviewdeleted']);

// nombor ringkas untuk tunjuk kat jubin statistik atas dashboard
// "Revenue" cuma kira booking yang betul-betul confirm/checked-in/checked-out (bukan pending/cancelled)
$stats = [
    'total_bookings' => (int) $pdo->query('SELECT COUNT(*) FROM booking')->fetchColumn(),
    'pending_bookings' => (int) $pdo->query("SELECT COUNT(*) FROM booking WHERE booking_status = 'pending'")->fetchColumn(),
    'revenue' => (float) $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM booking WHERE booking_status IN ('confirmed','checked_in','checked_out')")->fetchColumn(),
    'total_users' => (int) $pdo->query('SELECT COUNT(*) FROM user')->fetchColumn(),
];

// satu baris untuk setiap booking, nama penginapan + status bayaran terkini kita gabung sekali
// guna GROUP_CONCAT/subquery, supaya table kat bawah takyah query lagi untuk setiap baris
$bookings = $pdo->query(
    "SELECT b.booking_id, c.full_name, c.phone, b.check_in, b.check_out, b.total_guest,
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
    'SELECT accommodation_id, accommodation_name, accommodation_type, price, capacity, status
     FROM accommodation ORDER BY accommodation_type, accommodation_id'
)->fetchAll();

// notification terkini yang berjaya dihantar untuk setiap booking + jenis mesej, supaya
// column Notification kat bawah boleh papar "Sent" ganti butang, kalau mesej tu dah dihantar
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

// dibalut try/catch supaya kalau database belum kena migrate (contoh: lupa jalankan
// database/add_review_display_name.sql lepas deploy), dashboard still load dengan section
// Guest Reviews kosong je — dan bukan seluruh dashboard (bookings/accommodations/staff) fatal error.
try {
    // LEFT JOIN sebab footer review widget takde ref/phone — review dari situ tersimpan
    // dengan booking_id NULL (unverified/tak boleh disahkan), so takde row booking/customer
    // untuk dipadan. c.full_name jadi NULL untuk review macam tu, dihandle kat view.
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

// semua logic dah selesai kat atas ni — baris bawah papar HTML page dia.
// HTML/borang tu disimpan berasingan dalam folder views/ supaya file ni tak jadi terlalu panjang.
require __DIR__ . '/views/admin_dashboard.view.php';
