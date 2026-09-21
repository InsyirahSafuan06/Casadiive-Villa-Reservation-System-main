<?php
/**
 * API laporan: satu baris per review, untuk Power BI.
 * Guna ni untuk analisa rating & sentimen dari masa ke masa.
 *
 * PENTING (privasi): kita hantar r.display_name (nama yang guest PILIH untuk tunjuk secara
 * terbuka), BUKAN nama sebenar dari booking/customer — sama prinsip macam testimonials
 * kat homepage. Review "unverified" (booking_id NULL, dari footer widget) turut disertakan.
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/api_auth.php';

api_authenticate();

$rows = $pdo->query(
    "SELECT review_id, booking_id, rating, comment, display_name, image_path, review_date
     FROM review
     ORDER BY review_id"
)->fetchAll();

foreach ($rows as &$row) {
    $row['rating'] = (int) $row['rating'];
    $row['display_name'] = $row['display_name'] !== null && $row['display_name'] !== '' ? $row['display_name'] : 'Anonymous';
    $row['has_photo'] = $row['image_path'] !== null;
    $row['is_verified_booking'] = $row['booking_id'] !== null;
}
unset($row);

api_send($rows);
