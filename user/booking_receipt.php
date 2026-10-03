<?php
# calling function require_once() untuk load fail db.php supaya boleh guna $pdo
require_once __DIR__ . '/../includes/db.php';
# calling function require_once() untuk load fail auth.php supaya boleh guna current_user() & require_login()
require_once __DIR__ . '/../includes/auth.php';
# calling function require_once() untuk load fail helpers.php supaya boleh guna compute_stay_price()
require_once __DIR__ . '/../includes/helpers.php';
# calling function require_login() untuk pastikan hanya manager & staff boleh buka page ni
require_login(['manager', 'staff']);

# calling function current_user() that assign to variable name $currentUser untuk tahu siapa yang sedang login
$currentUser = current_user();
# calling function filter_input() that assign to variable name $bookingId untuk ambil & sahkan id booking dari url
$bookingId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

# check kalau $bookingId takde/tak sah, halau balik ke dashboard ikut role
if (!$bookingId) {
    # calling function header() untuk redirect balik ke dashboard ikut role user
    header('Location: ' . ($currentUser['role'] === 'manager' ? 'admin_dashboard.php' : 'staff_dashboard.php'));
    exit;
}

# calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query ambil detail booking & customer
$stmt = $pdo->prepare(
    'SELECT b.*, c.full_name, c.phone, c.plate_num
     FROM booking b JOIN customer c ON c.customer_id = b.customer_id
     WHERE b.booking_id = :id'
);
# calling method execute() dari object $stmt untuk jalankan query, isi placeholder :id dengan $bookingId
$stmt->execute(['id' => $bookingId]);
# calling method fetch() dari object $stmt that assign to variable name $booking untuk ambil 1 row data booking
$booking = $stmt->fetch();

# check kalau booking tak wujud, halau balik ke dashboard ikut role
if (!$booking) {
    # calling function header() untuk redirect balik ke dashboard ikut role user
    header('Location: ' . ($currentUser['role'] === 'manager' ? 'admin_dashboard.php' : 'staff_dashboard.php'));
    exit;
}

# calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query ambil item accommodation dalam booking ni
$stmt = $pdo->prepare(
    'SELECT bi.quantity, bi.price, a.accommodation_name, a.accommodation_type,
            a.price AS nightly_price, a.price_weekend AS nightly_price_weekend
     FROM booking_item bi
     JOIN accommodation a ON a.accommodation_id = bi.accommodation_id
     WHERE bi.booking_id = :id'
);
# calling method execute() dari object $stmt untuk jalankan query, isi placeholder :id dengan $bookingId
$stmt->execute(['id' => $bookingId]);
# calling method fetchAll() dari object $stmt that assign to variable name $items untuk ambil semua row item booking
$items = $stmt->fetchAll();

# calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query ambil deposit yang dah paid
$stmt = $pdo->prepare("SELECT deposit_paid FROM payment WHERE booking_id = :id AND payment_status = 'paid' ORDER BY payment_id DESC LIMIT 1");
# calling method execute() dari object $stmt untuk jalankan query, isi placeholder :id dengan $bookingId
$stmt->execute(['id' => $bookingId]);
# calling method fetchColumn() dari object $stmt that assign to variable name $amountPaid untuk ambil jumlah deposit dah dibayar, default 0
$amountPaid = (float) ($stmt->fetchColumn() ?: 0);

# calling function new DateTime() that assign to variable name $checkIn untuk tukar tarikh check-in jadi objek DateTime
$checkIn = new DateTime($booking['check_in']);
# calling function new DateTime() that assign to variable name $checkOut untuk tukar tarikh check-out jadi objek DateTime
$checkOut = new DateTime($booking['check_out']);
# calling method diff() untuk kira beza hari antara check-in & check-out that assign to variable name $nights, minimum 1 malam
$nights = max(1, $checkOut->diff($checkIn)->days);

# assign value ke $backUrl untuk tentukan link balik ikut role user (manager/staff)
$backUrl = $currentUser['role'] === 'manager' ? 'admin_dashboard.php' : 'staff_dashboard.php';

# calling function require() untuk load view booking_receipt.view.php dan papar resit booking
require __DIR__ . '/views/booking_receipt.view.php';
