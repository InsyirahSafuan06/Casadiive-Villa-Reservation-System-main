<?php
declare(strict_types=1);

const SMTP_HOST = 'smtp.gmail.com';
const SMTP_PORT = 587;
const SMTP_ENCRYPTION = 'tls';
const SMTP_USERNAME = 'your.gmail@gmail.com';
const SMTP_PASSWORD = 'your-16-digit-app-password';
const SMTP_FROM_EMAIL = 'your.gmail@gmail.com';
const SMTP_FROM_NAME = 'Casadive Villa';

class MailerException extends Exception
{
}

function send_email(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): bool
{
    # check kalau setting SMTP host/username/password mana-mana kosong, tak boleh hantar email
    if (SMTP_HOST === '' || SMTP_USERNAME === '' || SMTP_PASSWORD === '') {
        # calling function error_log() untuk simpan mesej dalam log yang SMTP belum disetup
        error_log('send_email: SMTP is not configured yet (see includes/mailer.php).');
        return false;
    }

    # calling function filter_var() untuk check $toEmail format email yang sah ke tidak
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        # calling function error_log() untuk simpan mesej email penerima tak sah
        error_log("send_email: invalid recipient address '$toEmail'.");
        return false;
    }

    try {
        # calling function smtp_dispatch() untuk hantar email betul-betul guna sambungan SMTP
        smtp_dispatch($toEmail, $toName, $subject, $htmlBody, $textBody !== '' ? $textBody : strip_tags($htmlBody));
        return true;
    } catch (Throwable $e) {
        # calling function error_log() untuk simpan mesej error bila hantar email gagal
        error_log('send_email failed: ' . $e->getMessage());
        return false;
    }
}

function smtp_dispatch(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody): void
{
    # assign value host SMTP (guna ssl:// kalau perlu) ke $target
    $target = (SMTP_ENCRYPTION === 'ssl' ? 'ssl://' : '') . SMTP_HOST;
    # calling function stream_socket_client() that assign to variable name $socket untuk buka sambungan network ke server SMTP
    $socket = @stream_socket_client($target . ':' . SMTP_PORT, $errno, $errstr, 15);
    # check kalau sambungan gagal (socket kosong/false), terus throw error
    if (!$socket) {
        throw new MailerException("Could not connect to SMTP server: $errstr ($errno)");
    }

    # calling function gethostname() that assign to variable name $localHost untuk dapatkan nama host mesin ni, fallback 'localhost'
    $localHost = gethostname() ?: 'localhost';

    try {
        # calling function smtp_expect() untuk tunggu & check server bagi respons 220 (bersedia)
        smtp_expect($socket, 220);
        # calling function smtp_command() untuk hantar EHLO kenalkan diri kita ke server, expect respons 250
        smtp_command($socket, 'EHLO ' . $localHost, 250);

        # check kalau setting encryption ni tls, kena upgrade sambungan guna STARTTLS
        if (SMTP_ENCRYPTION === 'tls') {
            # calling function smtp_command() untuk mula proses STARTTLS, expect respons 220
            smtp_command($socket, 'STARTTLS', 220);
            # calling function stream_socket_enable_crypto() untuk aktifkan enkripsi TLS pada socket, check kalau gagal terus throw error
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new MailerException('STARTTLS negotiation failed.');
            }
            # calling function smtp_command() untuk hantar semula EHLO lepas encryption diaktifkan
            smtp_command($socket, 'EHLO ' . $localHost, 250);
        }

        # calling function smtp_command() untuk mula proses login AUTH, expect respons 334
        smtp_command($socket, 'AUTH LOGIN', 334);
        # calling function base64_encode() & smtp_command() untuk hantar username yang dah di-encode, expect respons 334
        smtp_command($socket, base64_encode(SMTP_USERNAME), 334);
        # calling function base64_encode() & smtp_command() untuk hantar password yang dah di-encode, expect respons 235 (login berjaya)
        smtp_command($socket, base64_encode(SMTP_PASSWORD), 235);

        # calling function smtp_command() untuk bagitahu server siapa penghantar email, expect respons 250
        smtp_command($socket, 'MAIL FROM:<' . SMTP_FROM_EMAIL . '>', 250);
        # calling function smtp_command() untuk bagitahu server siapa penerima email, expect respons 250
        smtp_command($socket, 'RCPT TO:<' . $toEmail . '>', 250);
        # calling function smtp_command() untuk bagitahu server nak mula hantar isi email, expect respons 354
        smtp_command($socket, 'DATA', 354);

        # calling function fwrite() untuk hantar isi mesej email penuh ke socket, diakhiri titik untuk tanda habis
        fwrite($socket, smtp_build_message($toEmail, $toName, $subject, $htmlBody, $textBody) . "\r\n.\r\n");
        # calling function smtp_expect() untuk tunggu & check server terima isi email tu (respons 250)
        smtp_expect($socket, 250);

        # calling function smtp_command() untuk hantar QUIT tamatkan sesi SMTP, expect respons 221
        smtp_command($socket, 'QUIT', 221);
    } finally {
        # calling function fclose() untuk tutup sambungan socket, sama ada berjaya ke tidak hantar email
        fclose($socket);
    }
}

function smtp_build_message(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody): string
{
    # calling function bin2hex() & random_bytes() that assign to variable name $boundary untuk jana pemisah unik antara bahagian text & html
    $boundary = 'casadive-' . bin2hex(random_bytes(12));

    # assign array header email ke $headers, letak from, to, subject, dan jenis content
    $headers = [
        'Date: ' . date('r'),
        'From: ' . mime_encode_header(SMTP_FROM_NAME) . ' <' . SMTP_FROM_EMAIL . '>',
        'To: ' . mime_encode_header($toName) . ' <' . $toEmail . '>',
        'Subject: ' . mime_encode_header($subject),
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
    ];

    # assign value bahagian text & html mesej (dipisah guna $boundary) ke $body
    $body = "--{$boundary}\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n"
        . "Content-Transfer-Encoding: 8bit\r\n\r\n"
        . $textBody . "\r\n\r\n"
        . "--{$boundary}\r\n"
        . "Content-Type: text/html; charset=UTF-8\r\n"
        . "Content-Transfer-Encoding: 8bit\r\n\r\n"
        . $htmlBody . "\r\n\r\n"
        . "--{$boundary}--";

    # calling function implode() that assign to variable name $message untuk cantum $headers dgn $body jadi satu mesej penuh
    $message = implode("\r\n", $headers) . "\r\n\r\n" . $body;

    # calling function preg_replace() untuk escape baris yang mula dgn titik supaya SMTP tak salah anggap tu penamat mesej
    return preg_replace('/^\./m', '..', $message);
}

function mime_encode_header(string $value): string
{
    # calling function preg_match() untuk check $value ada character bukan ascii biasa (bukan english plain)
    if (preg_match('/[^\x20-\x7e]/', $value)) {
        # calling function base64_encode() untuk encode $value ikut format MIME supaya email client boleh papar betul
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    return $value;
}

function smtp_read_response($socket): string
{
    # assign string kosong ke $data untuk tampung baris respons dari server sikit demi sikit
    $data = '';
    # calling function fgets() untuk baca respons server baris demi baris sampai habis
    while (($line = fgets($socket, 515)) !== false) {
        $data .= $line;
        # check kalau character ke-4 baris tu ruang kosong, tandanya ni baris terakhir respons
        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }

    return $data;
}

function smtp_expect($socket, int $expectedCode): string
{
    # calling function smtp_read_response() that assign to variable name $response untuk baca respons penuh dari server
    $response = smtp_read_response($socket);
    # calling function substr() that assign to variable name $code untuk ambil 3 digit kod status dari respons
    $code = (int) substr($response, 0, 3);
    # check kalau kod respons server tak sama dgn yang dijangka, throw error
    if ($code !== $expectedCode) {
        throw new MailerException("Unexpected SMTP response (expected {$expectedCode}): {$response}");
    }

    return $response;
}

function smtp_command($socket, string $command, int $expectedCode): string
{
    # calling function fwrite() untuk hantar $command ke server SMTP
    fwrite($socket, $command . "\r\n");

    # calling function smtp_expect() untuk tunggu & check respons server sama dgn yang dijangka
    return smtp_expect($socket, $expectedCode);
}
