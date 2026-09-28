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

# calling method query() & fetchAll() dari object $pdo that assign to variable name $rows untuk ambil semua rekod payment join dgn booking
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

# loop setiap row dalam $rows (guna reference &) untuk tambah field baru & tukar jenis data sebelum hantar output
foreach ($rows as &$row) {
    # calling function format_booking_ref() untuk jana kod rujukan booking dari booking_id
    $row['booking_ref'] = format_booking_ref((int) $row['booking_id']);
    # tukar deposit_paid kepada float supaya jenis data tepat
    $row['deposit_paid'] = (float) $row['deposit_paid'];
}
# calling function unset() untuk buang reference $row lepas guna dalam foreach, elak bug
unset($row);

# calling function api_send() untuk hantar $rows sebagai output json
api_send($rows);
