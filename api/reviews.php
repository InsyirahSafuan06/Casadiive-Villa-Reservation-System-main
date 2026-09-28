<?php
# declare strict_types=1 untuk php check jenis data dgn ketat dalam fail ni
declare(strict_types=1);
# calling function require_once() untuk load fail db.php, dapatkan sambungan $pdo
require_once __DIR__ . '/../includes/db.php';
# calling function require_once() untuk load fail api_auth.php, dapatkan function api_authenticate()
require_once __DIR__ . '/../includes/api_auth.php';

# calling function api_authenticate() untuk check api key valid ke tak, kalau tak valid terus stop
api_authenticate();

# calling method query() & fetchAll() dari object $pdo that assign to variable name $rows untuk ambil semua rekod review
$rows = $pdo->query(
    "SELECT review_id, booking_id, rating, comment, display_name, image_path, review_date
     FROM review
     ORDER BY review_id"
)->fetchAll();

# loop setiap row dalam $rows (guna reference &) untuk tukar jenis data & tambah field baru sebelum hantar output
foreach ($rows as &$row) {
    # tukar rating kepada int supaya jenis data tepat
    $row['rating'] = (int) $row['rating'];
    # check kalau display_name kosong/takde, kalau ya guna 'Anonymous' sebagai default
    $row['display_name'] = $row['display_name'] !== null && $row['display_name'] !== '' ? $row['display_name'] : 'Anonymous';
    # check kalau image_path ada value, kalau ada bermakna review ni ada gambar
    $row['has_photo'] = $row['image_path'] !== null;
    # check kalau booking_id ada value, kalau ada bermakna review ni dari booking yang sah
    $row['is_verified_booking'] = $row['booking_id'] !== null;
}
# calling function unset() untuk buang reference $row lepas guna dalam foreach, elak bug
unset($row);

# calling function api_send() untuk hantar $rows sebagai output json
api_send($rows);
