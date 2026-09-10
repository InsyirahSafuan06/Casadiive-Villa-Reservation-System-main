<?php
/**
 * Endpoint JSON untuk AI Assistant — semak status booking.
 * Sama trust model macam mybooking.php: booking reference + phone number yang digunakan
 * masa booking jadi "password" ringkas. Tak match langsung (id salah ATAU phone salah),
 * kita bagi respons yang SAMA — supaya orang tak boleh teka wujud tak booking ID tu.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');

$ref = filter_input(INPUT_GET, 'ref', FILTER_VALIDATE_INT);
$phone = trim((string) ($_GET['phone'] ?? ''));

if ($ref === false || $ref === null || $phone === '') {
    echo json_encode(['found' => false]);
    exit;
}

echo json_encode(lookup_booking_status($pdo, $ref, $phone));
