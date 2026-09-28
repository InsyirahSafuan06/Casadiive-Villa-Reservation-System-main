<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');

$phone = trim((string) ($_GET['phone'] ?? ''));

if ($phone === '') {
    echo json_encode(['found' => false]);
    exit;
}

echo json_encode(lookup_bookings_by_phone($pdo, $phone));
