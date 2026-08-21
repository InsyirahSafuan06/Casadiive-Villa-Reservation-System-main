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

// status ni je yang kita rasa berbaloi hantar emel kat tetamu — 'pending' tak masuk
// sebab tu status awal booking, bukan perubahan status
const EMAIL_NOTIFIABLE_STATUSES = ['confirmed', 'checked_in', 'checked_out', 'cancelled'];

// dipanggil bila status booking berubah (contohnya admin confirm/cancel booking)
function send_status_email(PDO $pdo, int $bookingId, string $status): void
{
    // kalau status ni bukan dalam list atas, tak payah hantar emel
    if (!in_array($status, EMAIL_NOTIFIABLE_STATUSES, true)) {
        return;
    }

    try {
        $booking = email_fetch_booking($pdo, $bookingId);
        if (!$booking || !$booking['email']) {
            return; // takde booking ke takde emel pelanggan, tak boleh hantar
        }

        [$type, $subject, $html] = email_build_status_message($booking, $status); // bina isi emel ikut status
        $sent = send_email($booking['email'], $booking['full_name'], $subject, $html); // hantar emel
        email_log_notification($pdo, $bookingId, (int) $booking['customer_id'], $type, $sent); // catat dalam DB sama ada berjaya ke tak
    } catch (Throwable $e) {
        // apa-apa error pun, jangan biar sampai rosakkan flow booking — just log je
        error_log('send_status_email failed: ' . $e->getMessage());
    }
}

// dipanggil oleh cron job untuk hantar reminder check-in sehari sebelum tetamu datang
function send_checkin_reminder_email(PDO $pdo, int $bookingId): bool
{
    try {
        $booking = email_fetch_booking($pdo, $bookingId);
        if (!$booking || !$booking['email']) {
            return false;
        }

        $subject = 'Check-In Reminder — Casadive Villa (#' . (int) $booking['booking_id'] . ')';
        $html = email_render_checkin_reminder($booking, email_fetch_door_code_text($pdo, $bookingId)); // ambil kod pintu sekali

        $sent = send_email($booking['email'], $booking['full_name'], $subject, $html);
        email_log_notification($pdo, $bookingId, (int) $booking['customer_id'], 'check_in', $sent);

        return $sent;
    } catch (Throwable $e) {
        error_log('send_checkin_reminder_email failed: ' . $e->getMessage());
        return false;
    }
}

// ambil maklumat booking + pelanggan yang kita perlukan untuk isi emel
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

// ambil kod pintu untuk unit yang tetamu tempah — boleh jadi satu unit, banyak unit, atau takde kod langsung
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

    // satu unit je — tunjuk kod terus, tak payah letak nama unit
    if (count($rows) === 1) {
        return 'Door Lock Code: ' . htmlspecialchars($rows[0]['door_code']);
    }
    // lebih satu unit — senaraikan kod ikut nama unit masing-masing
    if (count($rows) > 1) {
        $lines = array_map(
            fn ($r) => htmlspecialchars($r['accommodation_name']) . ': ' . htmlspecialchars($r['door_code']),
            $rows
        );
        return 'Door Lock Code(s):<br>' . implode('<br>', $lines);
    }

    // takde kod pintu disimpan untuk unit ni — suruh tetamu tanya admin je
    return 'Please contact the admin for your door lock code.';
}

// simpan rekod dalam DB — sama ada emel berjaya dihantar atau gagal, kita catat juga
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
            'status' => $sent ? 'sent' : 'failed', // rekod status betul-betul ikut apa yang jadi
            'type' => $type,
        ]);
    } catch (Throwable $e) {
        error_log('email_log_notification failed: ' . $e->getMessage());
    }
}

// render satu view file (dalam includes/views/) dengan variables yang diberi, pulangkan output sebagai string —
// ni yang pisahkan PHP (logic) daripada HTML (template) untuk emel-emel dalam fail ni
function email_render_view(string $viewFile, array $vars = []): string
{
    extract($vars);
    ob_start();
    require __DIR__ . '/views/' . $viewFile;
    return ob_get_clean();
}

// bina subjek + isi emel ikut status booking — setiap status ada template view sendiri
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
        case 'confirmed': // emel bila admin confirm booking — tunjuk butiran check-in/out & bayaran
            $subject = "Booking Confirmed — Casadive Villa (#{$bookingId})";
            $body = email_render_view('email_status_confirmed.view.php', compact('name', 'checkin', 'checkout', 'deposit', 'total'));
            return ['booking_confirmation', $subject, email_render_layout('Booking Confirmed', $body)];

        case 'checked_in': // emel ringkas je bila tetamu dah check-in
            $subject = "Welcome to Casadive Villa (#{$bookingId})";
            $body = email_render_view('email_status_checked_in.view.php', compact('name'));
            return ['general', $subject, email_render_layout('Welcome!', $body)];

        case 'checked_out': // emel ucapan terima kasih lepas tetamu check-out
            $subject = "Thank You for Staying with Us — Casadive Villa (#{$bookingId})";
            $body = email_render_view('email_status_checked_out.view.php', compact('name', 'checkin', 'checkout'));
            return ['check_out', $subject, email_render_layout('Thank You', $body)];

        case 'cancelled': // emel bila booking dibatalkan (admin ke pelanggan yang batalkan)
            $subject = "Booking Cancelled — Casadive Villa (#{$bookingId})";
            $body = email_render_view('email_status_cancelled.view.php', compact('name', 'bookingId', 'checkin'));
            return ['cancellation', $subject, email_render_layout('Booking Cancelled', $body)];

        default: // status lain-lain (tak sepatutnya jadi sebab dah ditapis kat EMAIL_NOTIFIABLE_STATUSES) — fallback je
            $body = email_render_view('email_status_default.view.php', compact('name'));
            return ['general', "Casadive Villa — Booking #{$bookingId}", email_render_layout('Booking Update', $body)];
    }
}

// bina isi emel reminder check-in, termasuk kod pintu yang kita ambil dari fungsi lain
function email_render_checkin_reminder(array $booking, string $doorCodeHtml): string
{
    $name = htmlspecialchars($booking['full_name']);
    $checkin = htmlspecialchars(date('d F Y', strtotime($booking['check_in'])));

    $body = email_render_view('email_checkin_reminder_body.view.php', compact('name', 'checkin', 'doorCodeHtml'));

    return email_render_layout('Check-In Reminder', $body);
}

// bungkus badan emel dengan "template" HTML yang sama — logo, warna, footer standard
function email_render_layout(string $title, string $bodyHtml): string
{
    return email_render_view('email_layout.view.php', ['title' => htmlspecialchars($title), 'bodyHtml' => $bodyHtml]);
}