<?php
# calling function require_once() untuk load fail db.php supaya dapat object $pdo untuk connect database
require_once __DIR__ . '/../includes/db.php';
# calling function require_once() untuk load fail helpers.php supaya boleh guna fungsi helper macam recommend_accommodations()
require_once __DIR__ . '/../includes/helpers.php';

# calling function header() untuk bagitau browser response ni jenis json
header('Content-Type: application/json');

# calling function filter_input() that assign to variable name $guests untuk ambil & validate value 'guests' dari url query string
$guests = filter_input(INPUT_GET, 'guests', FILTER_VALIDATE_INT);
# check kalau $guests tak valid atau kurang dari 1, terus balas error tanpa proses lagi
if ($guests === false || $guests === null || $guests < 1) {
    # calling function json_encode() & echo untuk hantar response error json balik ke browser
    echo json_encode(['results' => [], 'count' => 0, 'error' => 'invalid_guests']);
    exit;
}

# ambil value $_GET['type'] then trim, assign to variable name $typeRaw untuk simpan jenis accommodation mentah
$typeRaw = trim((string) ($_GET['type'] ?? ''));
# calling function in_array() untuk check $typeRaw valid Villa/Campsite ke tidak, kalau tak valid guna null
$type = in_array($typeRaw, ['Villa', 'Campsite'], true) ? $typeRaw : null;

# ambil value $_GET['check_in'] then trim, assign to variable name $checkIn
$checkIn = trim((string) ($_GET['check_in'] ?? ''));
# ambil value $_GET['check_out'] then trim, assign to variable name $checkOut
$checkOut = trim((string) ($_GET['check_out'] ?? ''));
# check kalau kedua-dua tarikh diisi dan check_out lepas check_in, baru boleh guna filter tarikh
$useDates = $checkIn !== '' && $checkOut !== '' && $checkOut > $checkIn;

# ambil value $_GET['budget'] then trim, assign to variable name $budgetRaw
$budgetRaw = trim((string) ($_GET['budget'] ?? ''));
# check $budgetRaw tak kosong dan valid float, kalau valid convert ke float, kalau tak guna null
$budget = ($budgetRaw !== '' && filter_var($budgetRaw, FILTER_VALIDATE_FLOAT) !== false) ? (float) $budgetRaw : null;

# ambil value $_GET['facility'] then trim & lowercase, assign to variable name $facility untuk carian ciri-ciri
$facility = strtolower(trim((string) ($_GET['facility'] ?? '')));

# calling function recommend_accommodations() that assign to variable name $matches untuk dapatkan list cadangan accommodation ikut kriteria
$matches = recommend_accommodations($pdo, [
    'guests' => $guests,
    'type' => $type,
    'check_in' => $useDates ? $checkIn : null,
    'check_out' => $useDates ? $checkOut : null,
    'budget' => $budget,
]);

# assign array kosong ke $results untuk simpan hasil yang dah diproses untuk chatbot
$results = [];
# loop setiap accommodation dalam $matches untuk susun data yang nak dihantar balik
foreach ($matches as $item) {
    # ambil value accommodation dari $item, assign to variable name $acc
    $acc = $item['accommodation'];
    # calling function strtolower() untuk gabung features & description then lowercase, assign to $searchText untuk memudahkan carian
    $searchText = strtolower(($acc['features'] ?? '') . ' ' . ($acc['description'] ?? ''));
    # calling function strpos() untuk check ada perkataan 'pool' dalam $searchText
    $hasPool = strpos($searchText, 'pool') !== false;
    # calling function strpos() untuk check ada perkataan 'wifi' dalam $searchText
    $hasWifi = strpos($searchText, 'wifi') !== false;
    # calling function strpos() untuk check ada perkataan 'bbq' dalam $searchText
    $hasBbq = strpos($searchText, 'bbq') !== false;
    # check $facility diisi dan wujud dalam $searchText untuk tandakan padanan ciri-ciri
    $matchesFacility = $facility !== '' && strpos($searchText, $facility) !== false;

    # assign array data accommodation yang dah disusun ke dalam $results untuk dihantar sebagai json
    $results[] = [
        'id' => (int) $acc['accommodation_id'],
        'name' => $acc['accommodation_name'],
        'type' => $acc['accommodation_type'],
        'price' => (float) $acc['price'],
        'capacity' => (int) $acc['capacity'],
        'pax_label' => $acc['pax_label'] ?: ('Max ' . (int) $acc['capacity'] . ' guests'),
        'reasons' => $item['reasons'],
        'has_pool' => $hasPool,
        'has_wifi' => $hasWifi,
        'has_bbq' => $hasBbq,
        'matches_facility' => $matchesFacility,
    ];
}

# check kalau ada filter facility, kena susun result ikut mana yang match facility dulu
if ($facility !== '') {
    # calling function usort() untuk susun $results, letak yang matches_facility true kat depan
    usort($results, function ($a, $b) {
        return ($b['matches_facility'] ? 1 : 0) <=> ($a['matches_facility'] ? 1 : 0);
    });
}

# calling function array_slice() that assign to variable name $results untuk ambil 3 result teratas je
$results = array_slice($results, 0, 3);

# calling function json_encode() & echo untuk hantar hasil cadangan balik ke browser dalam format json
echo json_encode(['results' => $results, 'count' => count($results)]);
