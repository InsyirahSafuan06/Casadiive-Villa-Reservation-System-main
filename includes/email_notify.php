<?php
/**
 * Notifikasi emel status tempahan.
 * Membina mesej untuk sesuatu perubahan booking_status, menghantarnya melalui
 * includes/mailer.php, dan mencatatkan percubaan tersebut ke notification_status
 * (channel = 'email'). Setiap titik masuk di sini menelan ralatnya sendiri —
 * pelayan mel yang rosak/belum dikonfigur tidak boleh sekali-kali merosakkan aliran
 * tempahan, pembayaran, atau kemas kini status yang mencetuskannya.
 */
declare(strict_types=1);

require_once __DIR__ . '/mailer.php';

/** Status yang berbaloi untuk emel kepada tetamu; 'pending' ialah status awal, bukan kemas kini. */
const EMAIL_NOTIFIABLE_STATUSES = ['confirmed', 'checked_in', 'checked_out', 'cancelled'];

function send_status_email(PDO $pdo, int $bookingId, string $status): void
{
    if (!in_array($status, EMAIL_NOTIFIABLE_STATUSES, true)) {
        return;
    }

    try {
        $booking = email_fetch_booking($pdo, $bookingId);
        if (!$booking || !$booking['email']) {
            return;
        }

        [$type, $subject, $html] = email_build_status_message($booking, $status);
        $sent = send_email($booking['email'], $booking['full_name'], $subject, $html);
        email_log_notification($pdo, $bookingId, (int) $booking['customer_id'], $type, $sent);
    } catch (Throwable $e) {
        error_log('send_status_email failed: ' . $e->getMessage());
    }
}

function send_checkin_reminder_email(PDO $pdo, int $bookingId): bool
{
    try {
        $booking = email_fetch_booking($pdo, $bookingId);
        if (!$booking || !$booking['email']) {
            return false;
        }

        $subject = 'Check-In Reminder — Casadive Villa (#' . (int) $booking['booking_id'] . ')';
        $html = email_render_checkin_reminder($booking, email_fetch_door_code_text($pdo, $bookingId));

        $sent = send_email($booking['email'], $booking['full_name'], $subject, $html);
        email_log_notification($pdo, $bookingId, (int) $booking['customer_id'], 'check_in', $sent);

        return $sent;
    } catch (Throwable $e) {
        error_log('send_checkin_reminder_email failed: ' . $e->getMessage());
        return false;
    }
}

function email_fetch_booking(PDO $pdo, int $bookingId): array|false
{
    $stmt = $pdo->prepare(
        'SELECT b.booking_id, b.check_in, b.check_out, b.deposit_amount, b.total_amount,
                c.customer_id, c.full_name, c.email
         FROM booking b
         JOIN customer c ON c.customer_id = b.customer_id
         WHERE b.booking_id = :id'
    );
    $stmt->execute(['id' => $bookingId]);

    return $stmt->fetch();
}

function email_fetch_door_code_text(PDO $pdo, int $bookingId): string
{
    $stmt = $pdo->prepare(
        "SELECT DISTINCT a.accommodation_name, a.door_code
         FROM booking_item bi
         JOIN accommodation a ON a.accommodation_id = bi.accommodation_id
         WHERE bi.booking_id = :id AND a.door_code IS NOT NULL AND a.door_code <> ''"
    );
    $stmt->execute(['id' => $bookingId]);
    $rows = $stmt->fetchAll();

    if (count($rows) === 1) {
        return 'Door Lock Code: ' . htmlspecialchars($rows[0]['door_code']);
    }
    if (count($rows) > 1) {
        $lines = array_map(
            fn ($r) => htmlspecialchars($r['accommodation_name']) . ': ' . htmlspecialchars($r['door_code']),
            $rows
        );
        return 'Door Lock Code(s):<br>' . implode('<br>', $lines);
    }

    return 'Please contact the admin for your door lock code.';
}

function email_log_notification(PDO $pdo, int $bookingId, int $customerId, string $type, bool $sent): void
{
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO notification_status (booking_id, customer_id, sent_date, status, notification_type, channel)
             VALUES (:booking_id, :customer_id, NOW(), :status, :type, 'email')"
        );
        $stmt->execute([
            'booking_id' => $bookingId,
            'customer_id' => $customerId,
            'status' => $sent ? 'sent' : 'failed',
            'type' => $type,
        ]);
    } catch (Throwable $e) {
        error_log('email_log_notification failed: ' . $e->getMessage());
    }
}

/** @return array{0:string,1:string,2:string} [notification_type, subjek, badan html] */
function email_build_status_message(array $booking, string $status): array
{
    $bookingId = (int) $booking['booking_id'];
    $name = htmlspecialchars($booking['full_name']);
    $checkin = htmlspecialchars(date('d F Y', strtotime($booking['check_in'])));
    $checkout = htmlspecialchars(date('d F Y', strtotime($booking['check_out'])));
    $deposit = number_format((float) $booking['deposit_amount'], 2);
    $total = number_format((float) $booking['total_amount'], 2);

    switch ($status) {
        case 'confirmed':
            $subject = "Booking Confirmed — Casadive Villa (#{$bookingId})";
            $body = "
                <p>Hi {$name},</p>
                <p>Your booking has been <strong>confirmed</strong>! Here are your stay details:</p>
                <table style=\"width:100%;border-collapse:collapse;margin:16px 0;\">
                  <tr><td style=\"padding:6px 0;color:#844525;\">Check-in</td><td style=\"padding:6px 0;text-align:right;\"><strong>{$checkin}</strong></td></tr>
                  <tr><td style=\"padding:6px 0;color:#844525;\">Check-out</td><td style=\"padding:6px 0;text-align:right;\"><strong>{$checkout}</strong></td></tr>
                  <tr><td style=\"padding:6px 0;color:#844525;\">Deposit Paid</td><td style=\"padding:6px 0;text-align:right;\">RM {$deposit}</td></tr>
                  <tr><td style=\"padding:6px 0;color:#844525;\">Total Amount</td><td style=\"padding:6px 0;text-align:right;\">RM {$total}</td></tr>
                </table>
                <p>Thank you for choosing Casadive Villa. We look forward to welcoming you!</p>
            ";
            return ['booking_confirmation', $subject, email_render_layout('Booking Confirmed', $body)];

        case 'checked_in':
            $subject = "Welcome to Casadive Villa (#{$bookingId})";
            $body = "
                <p>Hi {$name},</p>
                <p>You're all checked in — welcome to Casadive Villa! If you need anything during your stay, feel free to contact us.</p>
                <p>Enjoy your stay!</p>
            ";
            return ['general', $subject, email_render_layout('Welcome!', $body)];

        case 'checked_out':
            $subject = "Thank You for Staying with Us — Casadive Villa (#{$bookingId})";
            $body = "
                <p>Hi {$name},</p>
                <p>Thank you for staying with Casadive Villa.</p>
                <table style=\"width:100%;border-collapse:collapse;margin:16px 0;\">
                  <tr><td style=\"padding:6px 0;color:#844525;\">Check-in</td><td style=\"padding:6px 0;text-align:right;\">{$checkin}</td></tr>
                  <tr><td style=\"padding:6px 0;color:#844525;\">Check-out</td><td style=\"padding:6px 0;text-align:right;\">{$checkout}</td></tr>
                </table>
                <p>We hope you enjoyed your vacation. Have a safe journey, and we hope to see you again soon!</p>
            ";
            return ['check_out', $subject, email_render_layout('Thank You', $body)];

        case 'cancelled':
            $subject = "Booking Cancelled — Casadive Villa (#{$bookingId})";
            $body = "
                <p>Hi {$name},</p>
                <p>Your booking <strong>#{$bookingId}</strong> (check-in {$checkin}) has been <strong>cancelled</strong>.</p>
                <p>If this wasn't expected or you have any questions, please contact us.</p>
            ";
            return ['cancellation', $subject, email_render_layout('Booking Cancelled', $body)];

        default:
            return ['general', "Casadive Villa — Booking #{$bookingId}", email_render_layout('Booking Update', "<p>Hi {$name},</p>")];
    }
}

function email_render_checkin_reminder(array $booking, string $doorCodeHtml): string
{
    $name = htmlspecialchars($booking['full_name']);
    $checkin = htmlspecialchars(date('d F Y', strtotime($booking['check_in'])));

    $body = "
        <p>Hi {$name},</p>
        <p>This is a friendly reminder that your check-in date is coming up:</p>
        <table style=\"width:100%;border-collapse:collapse;margin:16px 0;\">
          <tr><td style=\"padding:6px 0;color:#844525;\">Check-in Date</td><td style=\"padding:6px 0;text-align:right;\"><strong>{$checkin}</strong></td></tr>
          <tr><td style=\"padding:6px 0;color:#844525;\">Check-in Time</td><td style=\"padding:6px 0;text-align:right;\">After 3.00 PM</td></tr>
          <tr><td style=\"padding:6px 0;color:#844525;\">Check-out Time</td><td style=\"padding:6px 0;text-align:right;\">Before 12.00 PM</td></tr>
        </table>
        <p>{$doorCodeHtml}<br>Please use the code above to unlock the main door upon your arrival.</p>
        <p>If you have any questions or need assistance, feel free to contact us. We hope you have a safe journey and enjoy your stay at Casadive Villa. See you soon!</p>
    ";

    return email_render_layout('Check-In Reminder', $body);
}

function email_render_layout(string $title, string $bodyHtml): string
{
    $title = htmlspecialchars($title);

    return <<<HTML
<!doctype html>
<html>
<body style="margin:0;padding:24px;background:#F9F5EF;font-family:Arial,Helvetica,sans-serif;color:#000;">
  <div style="max-width:520px;margin:0 auto;background:#ffffff;border-radius:10px;padding:32px;border:1px solid rgba(106,58,25,.15);">
    <p style="font-size:22px;color:#FE810A;margin:0 0 20px;font-weight:bold;">Casadive Villa</p>
    <h1 style="font-size:20px;color:#6A3A19;margin:0 0 16px;">{$title}</h1>
    <div style="font-size:15px;line-height:1.6;">{$bodyHtml}</div>
    <hr style="border:none;border-top:1px solid #eee;margin:24px 0;">
    <p style="font-size:12px;color:#999;margin:0;">Casadive Villa &middot; This is an automated message, please do not reply directly to this email.</p>
  </div>
</body>
</html>
HTML;
}
