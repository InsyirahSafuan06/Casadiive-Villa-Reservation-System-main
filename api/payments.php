<?php
/**
 * API laporan: satu baris per rekod bayaran, untuk Power BI.
 * Guna ni untuk analisa cashflow — jumlah dibayar, kaedah bayaran, dan status ikut masa.
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../includes/helpers.php';

api_authenticate();

$rows = $pdo->query(
    "SELECT p.payment_id,
            p.booking_id,
            p.deposit_paid,
            p.payment_method,
            p.payment_date,
            p.payment_status,
            b.booking_status,
            b.check_in,
            b.check_out
     FROM payment p
     JOIN booking b ON b.booking_id = p.booking_id
     ORDER BY p.payment_id"
)->fetchAll();

foreach ($rows as &$row) {
    $row['booking_ref'] = format_booking_ref((int) $row['booking_id']);
    $row['deposit_paid'] = (float) $row['deposit_paid'];
}
unset($row);

api_send($rows);
