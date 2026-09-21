<?php
/**
 * Pengesahan untuk API laporan (read-only) yang digunakan Power BI untuk tarik data
 * dari MySQL. Fail ni TAK ada logic bisnes — cuma checked API key + helper hantar JSON,
 * dikongsi oleh semua fail dalam folder api/.
 */
declare(strict_types=1);

// Kunci untuk Power BI (atau tool BI lain) sahkan diri dia — hantar sama ada sebagai
// header "X-Api-Key: ..." atau query string "?key=...". Power BI Desktop punya Web
// connector sokong dua-dua cara (header via "Advanced" URL options, atau terus dalam URL).
//
// PENTING: kalau key ni bocor/kena tukar, generate yang baru guna:
//   php -r "echo bin2hex(random_bytes(32));"
// dan gantikan nilai kat bawah kat SINI SAHAJA (production dan local kena guna key yang sama
// sebab Power BI simpan key production dia sendiri dalam data source settings dia).
const API_REPORTING_KEY = 'fe78b0820eb1f84c2cdb9c3df67b46978320bcd9fda3679f9ffd158c7b7f6e55';

/**
 * Sahkan API key yang dihantar — hentikan request dengan 401 JSON kalau tak sah/takde.
 * Guna hash_equals() (bukan ===) supaya elak timing attack yang boleh teka key sikit-sikit
 * dari berapa lama comparison tu ambil masa.
 */
function api_authenticate(): void
{
    $provided = $_SERVER['HTTP_X_API_KEY'] ?? ($_GET['key'] ?? '');

    if (!is_string($provided) || $provided === '' || !hash_equals(API_REPORTING_KEY, $provided)) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Invalid or missing API key. Send it as the X-Api-Key header or ?key= query parameter.']);
        exit;
    }
}

/**
 * Hantar array PHP sebagai JSON dan tamatkan request — satu titik keluar untuk semua
 * endpoint api/, supaya format response (content-type, pretty-print) konsisten.
 */
function api_send(array $data): void
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}
