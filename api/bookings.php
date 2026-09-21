<?php
/**
 * API laporan: satu baris per booking, untuk Power BI.
 * Guna ni untuk analisa jumlah tempahan, hasil (revenue), dan taburan status ikut tarikh.
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../includes/helpers.php';

api_authenticate();

$rows = $pdo->query(
    "SELECT b.booking_id,
            b.booking_date,
            b.check_in,
            b.check_out,
            DATEDIFF(b.check_out, b.check_in) AS nights,
            b.total_guest,
            b.booking_status,
            b.deposit_amount,
            b.total_amount,
            b.addon_bbq,
            b.addon_mattress,
            b.discount_amount,
            (b.total_amount + b.deposit_amount) AS grand_total,
            c.full_name AS customer_name,
            GROUP_CONCAT(a.accommodation_name SEPARATOR ', ') AS accommodations,
            GROUP_CONCAT(DISTINCT a.accommodation_type SEPARATOR ', ') AS accommodation_types
     FROM booking b
     JOIN customer c ON c.customer_id = b.customer_id
     LEFT JOIN booking_item bi ON bi.booking_id = b.booking_id
     LEFT JOIN accommodation a ON a.accommodation_id = bi.accommodation_id
     GROUP BY b.booking_id
     ORDER BY b.booking_id"
)->fetchAll();

foreach ($rows as &$row) {
    $row['booking_ref'] = format_booking_ref((int) $row['booking_id']);
    $row['nights'] = (int) $row['nights'];
    $row['total_guest'] = (int) $row['total_guest'];
    $row['deposit_amount'] = (float) $row['deposit_amount'];
    $row['total_amount'] = (float) $row['total_amount'];
    $row['addon_bbq'] = (bool) $row['addon_bbq'];
    $row['addon_mattress'] = (bool) $row['addon_mattress'];
    $row['discount_amount'] = (float) $row['discount_amount'];
    $row['grand_total'] = (float) $row['grand_total'];
}
unset($row);

api_send($rows);
