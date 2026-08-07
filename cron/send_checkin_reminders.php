<?php
/**
 * Tugas cron peringatan check-in.
 * Hantar emel kepada setiap tempahan confirmed yang tarikh check-in nya esok — inilah
 * bahagian yang menjadikan peringatan check-in benar-benar automatik, bukannya bergantung
 * pada admin klik "Send" dalam dashboard. PHP tiada daemon sendiri, jadi skrip ini
 * perlu dijalankan sekali sehari oleh penjadual luaran:
 *
 *   Windows Task Scheduler — pencetus harian, tindakan:
 *     Program: C:\xampp\php\php.exe
 *     Arguments: "C:\xampp\htdocs\Casadiive-Villa-Reservation-System-main\cron\send_checkin_reminders.php"
 *
 *   cron (hosting Linux/macOS):
 *     0 8 * * * php /path/to/cron/send_checkin_reminders.php >> /path/to/cron/reminders.log 2>&1
 *
 * CLI sahaja: menolak untuk berjalan melalui HTTP supaya pelawat web sembarangan tidak boleh
 * cetuskan penghantaran emel secara pukal dengan meminta URL ini secara terus.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script can only be run from the command line.\n");
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/email_notify.php';

$tomorrow = date('Y-m-d', strtotime('+1 day'));

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
$bookings->execute(['tomorrow' => $tomorrow]);
$bookingIds = $bookings->fetchAll(PDO::FETCH_COLUMN);

echo "Check-in reminders for {$tomorrow}: " . count($bookingIds) . " booking(s) to email.\n";

$sent = 0;
foreach ($bookingIds as $bookingId) {
    if (send_checkin_reminder_email($pdo, (int) $bookingId)) {
        $sent++;
        echo "  booking #{$bookingId}: sent\n";
    } else {
        echo "  booking #{$bookingId}: FAILED (see PHP error log)\n";
    }
}

echo "Done. {$sent}/" . count($bookingIds) . " sent.\n";
