<?php
declare(strict_types=1);

const API_REPORTING_KEY = 'fe78b0820eb1f84c2cdb9c3df67b46978320bcd9fda3679f9ffd158c7b7f6e55';

function api_authenticate(): void
{
    #tempat key yang dihantar oleh pemanggil API (dari URL/header)
    # ambil value $_SERVER['HTTP_X_API_KEY'] ke $_GET['key'] that assign to variable name $provided untuk tahu key mana yang dihantar caller
    $provided = $_SERVER['HTTP_X_API_KEY'] ?? ($_GET['key'] ?? '');

    # check kalau $provided bukan string, kosong, atau tak sama dgn API_REPORTING_KEY - kalau salah satu true, tolak request
    if (!is_string($provided) || $provided === '' || !hash_equals(API_REPORTING_KEY, $provided)) {
        # calling function http_response_code() untuk set response code 401 (unauthorized)
        http_response_code(401);
        # calling function header() untuk bagitahu browser/client response ni jenis json
        header('Content-Type: application/json; charset=utf-8');
        # calling function json_encode() untuk hantar balik mesej error dalam format json
        echo json_encode(['error' => 'Invalid or missing API key. Send it as the X-Api-Key header or ?key= query parameter.']);
        exit;
    }
}

function api_send(array $data): void
{
    # calling function header() untuk bagitahu browser/client response ni jenis json
    header('Content-Type: application/json; charset=utf-8');
    # calling function json_encode() untuk tukar array $data jadi json then terus print keluar
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}