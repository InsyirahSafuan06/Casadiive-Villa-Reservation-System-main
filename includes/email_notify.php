<?php
declare(strict_types=1);

require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/helpers.php';

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

        $subject = 'Check-In Reminder — Casadive Villa (' . format_booking_ref((int) $booking['booking_id']) . ')';
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

function email_render_view(string $viewFile, array $vars = []): string
{
    extract($vars);
    ob_start();
    require __DIR__ . '/views/' . $viewFile;
    return ob_get_clean();
}

function email_build_status_message(array $booking, string $status): array
{
    $bookingId = (int) $booking['booking_id'];
    $bookingRef = format_booking_ref($bookingId);
    $name = htmlspecialchars($booking['full_name']);
    $checkin = htmlspecialchars(date('d F Y', strtotime($booking['check_in'])));
    $checkout = htmlspecialchars(date('d F Y', strtotime($booking['check_out'])));
    $total = number_format((float) $booking['total_amount'], 2);

    switch ($status) {
        case 'confirmed': 
            $subject = "Booking Confirmed — Casadive Villa ({$bookingRef})";
            $body = email_render_view('email_status_confirmed.view.php', compact('name', 'checkin', 'checkout', 'total'));
            return ['booking_confirmation', $subject, email_render_layout('Booking Confirmed', $body)];

        case 'checked_in': 
            $subject = "Welcome to Casadive Villa ({$bookingRef})";
            $body = email_render_view('email_status_checked_in.view.php', compact('name'));
            return ['general', $subject, email_render_layout('Welcome!', $body)];

        case 'checked_out': 
            $subject = "Thank You for Staying with Us — Casadive Villa ({$bookingRef})";
            $body = email_render_view('email_status_checked_out.view.php', compact('name', 'checkin', 'checkout'));
            return ['check_out', $subject, email_render_layout('Thank You', $body)];

        case 'cancelled': 
            $subject = "Booking Cancelled — Casadive Villa ({$bookingRef})";
            $body = email_render_view('email_status_cancelled.view.php', compact('name', 'bookingRef', 'checkin'));
            return ['cancellation', $subject, email_render_layout('Booking Cancelled', $body)];

        default: 
            $body = email_render_view('email_status_default.view.php', compact('name'));
            return ['general', "Casadive Villa — Booking {$bookingRef}", email_render_layout('Booking Update', $body)];
    }
}

function email_render_checkin_reminder(array $booking, string $doorCodeHtml): string
{
    $name = htmlspecialchars($booking['full_name']);
    $checkin = htmlspecialchars(date('d F Y', strtotime($booking['check_in'])));

    $body = email_render_view('email_checkin_reminder_body.view.php', compact('name', 'checkin', 'doorCodeHtml'));

    return email_render_layout('Check-In Reminder', $body);
}

function email_render_layout(string $title, string $bodyHtml): string
{
    return email_render_view('email_layout.view.php', ['title' => htmlspecialchars($title), 'bodyHtml' => $bodyHtml]);
}