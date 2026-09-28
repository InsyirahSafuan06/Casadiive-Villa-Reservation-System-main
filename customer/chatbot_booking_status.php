<?php
# calling function require_once() untuk load fail db.php supaya dapat object $pdo untuk connect database
require_once __DIR__ . '/../includes/db.php';
# calling function require_once() untuk load fail helpers.php supaya boleh guna fungsi helper macam lookup_bookings_by_phone()
require_once __DIR__ . '/../includes/helpers.php';

# calling function header() untuk bagitau browser response ni jenis json
header('Content-Type: application/json');

# ambil value $_GET['phone'] then trim, assign to variable name $phone untuk simpan nombor phone yang customer hantar
$phone = trim((string) ($_GET['phone'] ?? ''));

# check kalau $phone kosong, terus balas found:false tanpa query database
if ($phone === '') {
    # calling function json_encode() & echo untuk hantar response json balik ke browser
    echo json_encode(['found' => false]);
    exit;
}

# calling function lookup_bookings_by_phone() untuk cari booking ikut phone, then json_encode & echo untuk hantar hasil balik ke browser
echo json_encode(lookup_bookings_by_phone($pdo, $phone));
