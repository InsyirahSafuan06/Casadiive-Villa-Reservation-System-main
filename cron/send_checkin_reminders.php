<?php
# declare strict_types=1 untuk php check jenis data dgn ketat dalam fail ni
declare(strict_types=1);

# check kalau script ni dijalankan bukan dari command line (cli), kalau ya block akses terus
if (PHP_SAPI !== 'cli') {
    # calling function http_response_code() untuk set response code 403 (forbidden)
    http_response_code(403);
    # calling function exit() untuk stop script dgn mesej error
    exit("This script can only be run from the command line.\n");
}

# calling function require_once() untuk load fail db.php, dapatkan sambungan $pdo
require_once __DIR__ . '/../includes/db.php';
# calling function require_once() untuk load fail email_notify.php, dapatkan function send_checkin_reminder_email()
require_once __DIR__ . '/../includes/email_notify.php';

# calling function date() & strtotime() that assign to variable name $tomorrow untuk dapatkan tarikh esok dlm format Y-m-d
$tomorrow = date('Y-m-d', strtotime('+1 day'));

# calling method prepare() dari object $pdo that assign to variable name $bookings untuk sediakan query cari booking check-in esok yang belum dapat email reminder
$bookings = $pdo->prepare(
    "SELECT b.booking_id
     FROM booking b
     JOIN customer c ON c.customer_id = b.customer_id
     WHERE b.booking_status = 'confirmed'
       AND b.check_in = :tomorrow
       AND c.email IS NOT NULL AND c.email <> ''
       AND NOT EXISTS (
           SELECT 1 FROM notification_status n
           WHERE n.booking_id = b.booking_id
             AND n.notification_type = 'check_in'
             AND n.channel = 'email'
             AND n.status = 'sent'
             AND DATE(n.sent_date) = CURDATE()
       )"
);
# calling method execute() dari object $bookings untuk jalankan query, isi placeholder :tomorrow dgn value $tomorrow
$bookings->execute(['tomorrow' => $tomorrow]);
# calling method fetchAll() dari object $bookings that assign to variable name $bookingIds untuk ambil semua booking_id je dalam bentuk array
$bookingIds = $bookings->fetchAll(PDO::FETCH_COLUMN);

# calling function count() untuk papar jumlah booking yang kena hantar email
echo "Check-in reminders for {$tomorrow}: " . count($bookingIds) . " booking(s) to email.\n";

# assign value 0 ke variable $sent untuk kira jumlah email yang berjaya dihantar
$sent = 0;
# loop setiap booking id dalam $bookingIds untuk hantar email reminder check-in satu-satu
foreach ($bookingIds as $bookingId) {
    # calling function send_checkin_reminder_email() untuk hantar email reminder, check kalau berjaya
    if (send_checkin_reminder_email($pdo, (int) $bookingId)) {
        # tambah 1 pada $sent sebab email berjaya dihantar
        $sent++;
        echo "  booking #{$bookingId}: sent\n";
    } else {
        # papar status gagal (sebab dia boleh check dalam php error log)
        echo "  booking #{$bookingId}: FAILED (see PHP error log)\n";
    }
}

# calling function count() untuk papar ringkasan jumlah email yang berjaya dihantar
echo "Done. {$sent}/" . count($bookingIds) . " sent.\n";
