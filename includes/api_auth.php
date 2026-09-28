<?php
declare(strict_types=1);

const API_REPORTING_KEY = 'fe78b0820eb1f84c2cdb9c3df67b46978320bcd9fda3679f9ffd158c7b7f6e55';

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

function api_send(array $data): void
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}
