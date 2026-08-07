<?php
/**
 * Klien mel SMTP yang ringkas.
 * Tiada kebergantungan Composer/PHPMailer — bercakap SMTP mentah melalui stream socket PHP
 * supaya projek ini kekal bebas framework, selari dengan seluruh kod projek ini.
 *
 * PERSEDIAAN DIPERLUKAN: isikan pemalar SMTP_* di bawah dengan akaun sebenar
 * sebelum sebarang emel boleh benar-benar dihantar (contohnya alamat Gmail + App Password,
 * atau butiran SMTP penyedia hosting anda). Sehingga itu, send_email() hanya
 * log amaran dan pulangkan false — ia tidak akan merosakkan halaman yang memanggilnya.
 */
declare(strict_types=1);

const SMTP_HOST = '';               // contoh: 'smtp.gmail.com'
const SMTP_PORT = 587;              // 587 = STARTTLS, 465 = SSL tersirat
const SMTP_ENCRYPTION = 'tls';      // 'tls' atau 'ssl'
const SMTP_USERNAME = '';           // contoh: 'reservations@casadivevilla.com'
const SMTP_PASSWORD = '';           // contoh: App Password Gmail, bukan kata laluan log masuk biasa
const SMTP_FROM_EMAIL = 'reservations@casadivevilla.com';
const SMTP_FROM_NAME = 'Casadive Villa';

class MailerException extends Exception
{
}

/**
 * Hantar satu emel HTML (dengan fallback teks biasa yang dijana secara automatik).
 * Pulangkan true/false dan tidak sekali-kali lontar exception — pemanggil boleh
 * hantar-dan-lupa tanpa risiko menjejaskan aliran tempahan/pembayaran/kemas kini status yang mencetuskannya.
 */
function send_email(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): bool
{
    // kalau setting SMTP belum diisi lagi, jangan cuba hantar — just log dan berhenti
    if (SMTP_HOST === '' || SMTP_USERNAME === '' || SMTP_PASSWORD === '') {
        error_log('send_email: SMTP is not configured yet (see includes/mailer.php).');
        return false;
    }

    // pastikan alamat emel tu betul format dia dulu sebelum cuba hantar
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        error_log("send_email: invalid recipient address '$toEmail'.");
        return false;
    }

    try {
        // ni yang betul-betul hantar emel — kalau tak bagi versi teks biasa, kita generate sendiri dari HTML
        smtp_dispatch($toEmail, $toName, $subject, $htmlBody, $textBody !== '' ? $textBody : strip_tags($htmlBody));
        return true;
    } catch (Throwable $e) {
        // apa-apa pun jadi error, kita tangkap sini supaya tak crash page yang panggil fungsi ni
        error_log('send_email failed: ' . $e->getMessage());
        return false;
    }
}

// ni fungsi yang "cakap" terus dengan server emel guna protokol SMTP — step by step macam
// bercakap kat kaunter pos: bagitahu siapa hantar, siapa terima, then hantar surat tu
function smtp_dispatch(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody): void
{
    // bukak sambungan (macam telefon) ke server SMTP
    $target = (SMTP_ENCRYPTION === 'ssl' ? 'ssl://' : '') . SMTP_HOST;
    $socket = @stream_socket_client($target . ':' . SMTP_PORT, $errno, $errstr, 15);
    if (!$socket) {
        throw new MailerException("Could not connect to SMTP server: $errstr ($errno)");
    }

    $localHost = gethostname() ?: 'localhost';

    try {
        smtp_expect($socket, 220); // server "angkat telefon", kita tunggu dia siap
        smtp_command($socket, 'EHLO ' . $localHost, 250); // kita perkenalkan diri kat server

        // kalau guna TLS, kita "tukar saluran" jadi selamat/encrypted dulu sebelum sambung
        if (SMTP_ENCRYPTION === 'tls') {
            smtp_command($socket, 'STARTTLS', 220);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new MailerException('STARTTLS negotiation failed.');
            }
            smtp_command($socket, 'EHLO ' . $localHost, 250); // perkenal diri sekali lagi lepas dah secure
        }

        // login guna username & password yang kita set kat atas tadi
        smtp_command($socket, 'AUTH LOGIN', 334);
        smtp_command($socket, base64_encode(SMTP_USERNAME), 334);
        smtp_command($socket, base64_encode(SMTP_PASSWORD), 235);

        smtp_command($socket, 'MAIL FROM:<' . SMTP_FROM_EMAIL . '>', 250); // bagitahu ni dari siapa
        smtp_command($socket, 'RCPT TO:<' . $toEmail . '>', 250); // bagitahu ni nak hantar kat siapa
        smtp_command($socket, 'DATA', 354); // bagitahu server "ok saya nak hantar isi emel sekarang"

        // hantar isi emel (subjek + badan) sekali gus
        fwrite($socket, smtp_build_message($toEmail, $toName, $subject, $htmlBody, $textBody) . "\r\n.\r\n");
        smtp_expect($socket, 250); // pastikan server terima emel tu dengan ok

        smtp_command($socket, 'QUIT', 221); // habis, kita "letak telefon"
    } finally {
        fclose($socket); // tutup sambungan tak kira berjaya ke tak
    }
}

function smtp_build_message(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody): string
{
    $boundary = 'casadive-' . bin2hex(random_bytes(12));

    $headers = [
        'Date: ' . date('r'),
        'From: ' . mime_encode_header(SMTP_FROM_NAME) . ' <' . SMTP_FROM_EMAIL . '>',
        'To: ' . mime_encode_header($toName) . ' <' . $toEmail . '>',
        'Subject: ' . mime_encode_header($subject),
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
    ];

    $body = "--{$boundary}\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n"
        . "Content-Transfer-Encoding: 8bit\r\n\r\n"
        . $textBody . "\r\n\r\n"
        . "--{$boundary}\r\n"
        . "Content-Type: text/html; charset=UTF-8\r\n"
        . "Content-Transfer-Encoding: 8bit\r\n\r\n"
        . $htmlBody . "\r\n\r\n"
        . "--{$boundary}--";

    $message = implode("\r\n", $headers) . "\r\n\r\n" . $body;

    // Dot-stuffing SMTP: baris yang hanya mengandungi satu '.' akan
    // dibaca oleh pelayan sebagai penanda akhir-DATA jika tidak diubah.
    return preg_replace('/^\./m', '..', $message);
}

function mime_encode_header(string $value): string
{
    if (preg_match('/[^\x20-\x7e]/', $value)) {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    return $value;
}

/** @param resource $socket */
function smtp_read_response($socket): string
{
    $data = '';
    while (($line = fgets($socket, 515)) !== false) {
        $data .= $line;
        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }

    return $data;
}

/** @param resource $socket */
function smtp_expect($socket, int $expectedCode): string
{
    $response = smtp_read_response($socket);
    $code = (int) substr($response, 0, 3);
    if ($code !== $expectedCode) {
        throw new MailerException("Unexpected SMTP response (expected {$expectedCode}): {$response}");
    }

    return $response;
}

/** @param resource $socket */
function smtp_command($socket, string $command, int $expectedCode): string
{
    fwrite($socket, $command . "\r\n");

    return smtp_expect($socket, $expectedCode);
}
