<?php
declare(strict_types=1);

require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/helpers.php';

const EMAIL_NOTIFIABLE_STATUSES = ['confirmed', 'checked_in', 'checked_out', 'cancelled'];

function send_status_email(PDO $pdo, int $bookingId, string $status): void
{
    # check kalau $status tak ada dalam list EMAIL_NOTIFIABLE_STATUSES, terus stop sbb tak payah hantar email
    if (!in_array($status, EMAIL_NOTIFIABLE_STATUSES, true)) {
        return;
    }

    try {
        # calling function email_fetch_booking() that assign to variable name $booking untuk ambil detail booking & customer
        $booking = email_fetch_booking($pdo, $bookingId);
        # check kalau booking tak wujud atau customer tak ada email, tak payah teruskan
        if (!$booking || !$booking['email']) {
            return;
        }

        # check kalau customer dah pilih terima notification via whatsapp, skip hantar email
        if ((int) $booking['whatsapp_optin'] === 1) {
            return;
        }

        # calling function email_build_status_message() that assign ke $type, $subject, $html untuk bina isi email ikut status
        [$type, $subject, $html] = email_build_status_message($booking, $status);
        # calling function send_email() that assign to variable name $sent untuk hantar email, true/false ikut berjaya ke tak
        $sent = send_email($booking['email'], $booking['full_name'], $subject, $html);
        # calling function email_log_notification() untuk simpan rekod yang email ni dah cuba dihantar
        email_log_notification($pdo, $bookingId, (int) $booking['customer_id'], $type, $sent);
    } catch (Throwable $e) {
        # calling function error_log() untuk simpan mesej error dalam log server bila gagal hantar
        error_log('send_status_email failed: ' . $e->getMessage());
    }
}

function send_checkin_reminder_email(PDO $pdo, int $bookingId): bool
{
    try {
        # calling function email_fetch_booking() that assign to variable name $booking untuk ambil detail booking & customer
        $booking = email_fetch_booking($pdo, $bookingId);
        # check kalau booking tak wujud atau customer tak ada email, tak payah teruskan
        if (!$booking || !$booking['email']) {
            return false;
        }

        # check kalau customer dah pilih terima notification via whatsapp, skip hantar email
        if ((int) $booking['whatsapp_optin'] === 1) {
            return false;
        }

        # assign string subject email ke $subject, guna format_booking_ref() untuk letak nombor rujukan booking
        $subject = 'Check-In Reminder — Casadive Villa (' . format_booking_ref((int) $booking['booking_id']) . ')';
        # calling function email_render_checkin_reminder() & email_fetch_door_code_text() that assign to variable name $html untuk bina isi email reminder
        $html = email_render_checkin_reminder($booking, email_fetch_door_code_text($pdo, $bookingId));

        # calling function send_email() that assign to variable name $sent untuk hantar email, true/false ikut berjaya ke tak
        $sent = send_email($booking['email'], $booking['full_name'], $subject, $html);
        # calling function email_log_notification() untuk simpan rekod yang email ni dah cuba dihantar
        email_log_notification($pdo, $bookingId, (int) $booking['customer_id'], 'check_in', $sent);

        return $sent;
    } catch (Throwable $e) {
        # calling function error_log() untuk simpan mesej error dalam log server bila gagal hantar
        error_log('send_checkin_reminder_email failed: ' . $e->getMessage());
        return false;
    }
}

function email_fetch_booking(PDO $pdo, int $bookingId): array|false
{
    # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query ambil booking & customer
    $stmt = $pdo->prepare(
        'SELECT b.booking_id, b.check_in, b.check_out, b.deposit_amount, b.total_amount,
                c.customer_id, c.full_name, c.email, c.whatsapp_optin
         FROM booking b
         JOIN customer c ON c.customer_id = b.customer_id
         WHERE b.booking_id = :id'
    );
    # calling method execute() dari object $stmt untuk jalankan query, isi placeholder :id dgn $bookingId
    $stmt->execute(['id' => $bookingId]);

    # calling method fetch() dari object $stmt untuk pulangkan 1 row hasil query
    return $stmt->fetch();
}

function email_fetch_door_code_text(PDO $pdo, int $bookingId): string
{
    # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query cari door code accommodation dalam booking ni
    $stmt = $pdo->prepare(
        "SELECT DISTINCT a.accommodation_name, a.door_code
         FROM booking_item bi
         JOIN accommodation a ON a.accommodation_id = bi.accommodation_id
         WHERE bi.booking_id = :id AND a.door_code IS NOT NULL AND a.door_code <> ''"
    );
    # calling method execute() dari object $stmt untuk jalankan query, isi placeholder :id dgn $bookingId
    $stmt->execute(['id' => $bookingId]);
    # calling method fetchAll() dari object $stmt that assign to variable name $rows untuk ambil semua row hasil query
    $rows = $stmt->fetchAll();

    # check kalau cuma 1 accommodation ada door code, terus pulangkan teks tu je
    if (count($rows) === 1) {
        return 'Door Lock Code: ' . htmlspecialchars($rows[0]['door_code']);
    }
    # check kalau lebih dari 1 accommodation ada door code, kena senaraikan semua
    if (count($rows) > 1) {
        # calling function array_map() that assign to variable name $lines untuk bina teks "nama: kod" bagi setiap row
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
        # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query insert rekod notification
        $stmt = $pdo->prepare(
            "INSERT INTO notification_status (booking_id, customer_id, sent_date, status, notification_type, channel)
             VALUES (:booking_id, :customer_id, NOW(), :status, :type, 'email')"
        );
        # calling method execute() dari object $stmt untuk simpan rekod notification dgn status sent/failed
        $stmt->execute([
            'booking_id' => $bookingId,
            'customer_id' => $customerId,
            'status' => $sent ? 'sent' : 'failed',
            'type' => $type,
        ]);
    } catch (Throwable $e) {
        # calling function error_log() untuk simpan mesej error dalam log server bila gagal simpan rekod
        error_log('email_log_notification failed: ' . $e->getMessage());
    }
}

function email_render_view(string $viewFile, array $vars = []): string
{
    # calling function extract() untuk tukar setiap key dalam $vars jadi variable biasa supaya boleh guna dalam view
    extract($vars);
    # calling function ob_start() untuk mula tangkap output supaya HTML dalam view tak terus dicetak
    ob_start();
    # calling require untuk masukkan fail view yang akan cetak HTML guna variable yang di-extract tadi
    require __DIR__ . '/views/' . $viewFile;
    # calling function ob_get_clean() untuk ambil semua output yang ditangkap tadi jadi string then hentikan buffer
    return ob_get_clean();
}

function email_build_status_message(array $booking, string $status): array
{
    # assign value $booking['booking_id'] (dah cast ke int) ke $bookingId
    $bookingId = (int) $booking['booking_id'];
    # calling function format_booking_ref() that assign to variable name $bookingRef untuk bina rujukan booking macam CDV123
    $bookingRef = format_booking_ref($bookingId);
    # calling function htmlspecialchars() that assign to variable name $name untuk elak XSS bila papar nama customer
    $name = htmlspecialchars($booking['full_name']);
    # calling function date() & strtotime() that assign to variable name $checkin untuk format tarikh check-in senang dibaca
    $checkin = htmlspecialchars(date('d F Y', strtotime($booking['check_in'])));
    # calling function date() & strtotime() that assign to variable name $checkout untuk format tarikh check-out senang dibaca
    $checkout = htmlspecialchars(date('d F Y', strtotime($booking['check_out'])));
    # calling function number_format() that assign to variable name $total untuk format jumlah bayaran dgn 2 titik perpuluhan
    $total = number_format((float) $booking['total_amount'], 2);

    # check $status ni jenis apa untuk tentukan subjek & isi email yang sesuai
    switch ($status) {
        # kalau status confirmed, bina email confirm booking
        case 'confirmed':
            $subject = "Booking Confirmed — Casadive Villa ({$bookingRef})";
            # calling function email_render_view() that assign to variable name $body untuk bina html isi email dari template view
            $body = email_render_view('email_status_confirmed.view.php', compact('name', 'checkin', 'checkout', 'total'));
            # calling function email_render_layout() untuk bungkus $body dgn layout email then pulangkan [type, subject, html]
            return ['booking_confirmation', $subject, email_render_layout('Booking Confirmed', $body)];

        # kalau status checked_in, bina email welcome
        case 'checked_in':
            $subject = "Welcome to Casadive Villa ({$bookingRef})";
            # calling function email_render_view() that assign to variable name $body untuk bina html isi email dari template view
            $body = email_render_view('email_status_checked_in.view.php', compact('name'));
            # calling function email_render_layout() untuk bungkus $body dgn layout email then pulangkan [type, subject, html]
            return ['general', $subject, email_render_layout('Welcome!', $body)];

        # kalau status checked_out, bina email thank you
        case 'checked_out':
            $subject = "Thank You for Staying with Us — Casadive Villa ({$bookingRef})";
            # calling function email_render_view() that assign to variable name $body untuk bina html isi email dari template view
            $body = email_render_view('email_status_checked_out.view.php', compact('name', 'checkin', 'checkout'));
            # calling function email_render_layout() untuk bungkus $body dgn layout email then pulangkan [type, subject, html]
            return ['check_out', $subject, email_render_layout('Thank You', $body)];

        # kalau status cancelled, bina email booking dibatalkan
        case 'cancelled':
            $subject = "Booking Cancelled — Casadive Villa ({$bookingRef})";
            # calling function email_render_view() that assign to variable name $body untuk bina html isi email dari template view
            $body = email_render_view('email_status_cancelled.view.php', compact('name', 'bookingRef', 'checkin'));
            # calling function email_render_layout() untuk bungkus $body dgn layout email then pulangkan [type, subject, html]
            return ['cancellation', $subject, email_render_layout('Booking Cancelled', $body)];

        # kalau status lain-lain yang tak spesifik, guna template default je
        default:
            # calling function email_render_view() that assign to variable name $body untuk bina html isi email dari template default
            $body = email_render_view('email_status_default.view.php', compact('name'));
            # calling function email_render_layout() untuk bungkus $body dgn layout email then pulangkan [type, subject, html]
            return ['general', "Casadive Villa — Booking {$bookingRef}", email_render_layout('Booking Update', $body)];
    }
}

function email_render_checkin_reminder(array $booking, string $doorCodeHtml): string
{
    # calling function htmlspecialchars() that assign to variable name $name untuk elak XSS bila papar nama customer
    $name = htmlspecialchars($booking['full_name']);
    # calling function date() & strtotime() that assign to variable name $checkin untuk format tarikh check-in senang dibaca
    $checkin = htmlspecialchars(date('d F Y', strtotime($booking['check_in'])));

    # calling function email_render_view() that assign to variable name $body untuk bina html isi email reminder dari template view
    $body = email_render_view('email_checkin_reminder_body.view.php', compact('name', 'checkin', 'doorCodeHtml'));

    # calling function email_render_layout() untuk bungkus $body dgn layout email then pulangkan html siap
    return email_render_layout('Check-In Reminder', $body);
}

function email_render_layout(string $title, string $bodyHtml): string
{
    # calling function email_render_view() untuk bungkus $bodyHtml dalam template layout email lengkap dgn tajuk
    return email_render_view('email_layout.view.php', ['title' => htmlspecialchars($title), 'bodyHtml' => $bodyHtml]);
}