<?php
# declare strict_types=1 untuk php check jenis data dgn ketat dalam fail ni
declare(strict_types=1);
# calling function require_once() untuk load fail db.php, dapatkan sambungan $pdo
require_once __DIR__ . '/../includes/db.php';
# calling function require_once() untuk load fail api_auth.php, dapatkan function api_authenticate()
require_once __DIR__ . '/../includes/api_auth.php';
# calling function require_once() untuk load fail helpers.php, dapatkan function format_booking_ref()
require_once __DIR__ . '/../includes/helpers.php';

# calling function api_authenticate() untuk check api key valid ke tak, kalau tak valid terus stop
api_authenticate();

# calling method query() & fetchAll() dari object $pdo that assign to variable name $rows untuk ambil semua data booking join dgn customer & accommodation
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

# loop setiap row dalam $rows (guna reference &) untuk tukar jenis data & tambah field baru sebelum hantar output
foreach ($rows as &$row) {
    # calling function format_booking_ref() untuk jana kod rujukan booking dari booking_id
    $row['booking_ref'] = format_booking_ref((int) $row['booking_id']);
    # tukar baki value dalam row ni ke jenis int/float/bool yang betul supaya output json tepat
    $row['nights'] = (int) $row['nights'];
    $row['total_guest'] = (int) $row['total_guest'];
    $row['deposit_amount'] = (float) $row['deposit_amount'];
    $row['total_amount'] = (float) $row['total_amount'];
    $row['addon_bbq'] = (bool) $row['addon_bbq'];
    $row['addon_mattress'] = (bool) $row['addon_mattress'];
    $row['discount_amount'] = (float) $row['discount_amount'];
    $row['grand_total'] = (float) $row['grand_total'];
}
# calling function unset() untuk buang reference $row lepas guna dalam foreach, elak bug
unset($row);

# calling function api_send() untuk hantar $rows sebagai output json
api_send($rows);
