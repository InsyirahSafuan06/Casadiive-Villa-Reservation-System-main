<?php
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
