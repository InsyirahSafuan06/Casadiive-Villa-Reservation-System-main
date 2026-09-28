<?php
# calling function require_once() untuk load fail db.php, dapatkan sambungan $pdo
require_once __DIR__ . '/includes/db.php';

# try untuk elak page crash kalau query review gagal
try {
    # calling method query() & fetchAll() dari object $pdo that assign to variable name $reviews untuk ambil 6 review terbaru untuk papar kat homepage
    $reviews = $pdo->query(
        "SELECT r.rating, r.comment, r.image_path, r.review_date, r.display_name, c.location
         FROM review r
         LEFT JOIN booking b ON b.booking_id = r.booking_id
         LEFT JOIN customer c ON c.customer_id = b.customer_id
         ORDER BY r.review_date DESC
         LIMIT 6"
    )->fetchAll();
} catch (PDOException $e) {
    # calling function error_log() untuk simpan mesej error ke php error log
    error_log('Failed to load homepage reviews: ' . $e->getMessage());
    # assign array kosong ke $reviews supaya page tetap boleh load walaupun query gagal
    $reviews = [];
}

# calling method query() & fetchAll() dari object $pdo that assign to variable name $accommodationRows untuk ambil semua accommodation yang available
$accommodationRows = $pdo->query(
    "SELECT accommodation_id, accommodation_name, accommodation_type, price, capacity,
            pax_label, features, description
     FROM accommodation WHERE status = 'available' ORDER BY accommodation_type, accommodation_id"
)->fetchAll();

# assign array kosong ke $chatbotAccommodations untuk simpan senarai accommodation yang chatbot boleh cadangkan
$chatbotAccommodations = [];
# assign array kosong ke $chatbotPriceRanges untuk simpan harga minimum & maximum ikut jenis accommodation
$chatbotPriceRanges = [];
# loop setiap accommodation untuk bina data chatbot & kira julat harga ikut jenis
foreach ($accommodationRows as $row) {
    # calling function strtolower() that assign to variable name $searchText untuk gabung features & description jadi huruf kecil, senang nak cari keyword
    $searchText = strtolower(($row['features'] ?? '') . ' ' . ($row['description'] ?? ''));
    # calling function strpos() untuk check keyword pool/wifi/bbq wujud ke tak dalam $searchText, then tambah data ni ke $chatbotAccommodations
    $chatbotAccommodations[] = [
        'id' => (int) $row['accommodation_id'],
        'name' => $row['accommodation_name'],
        'type' => $row['accommodation_type'],
        'price' => (float) $row['price'],
        'capacity' => (int) $row['capacity'],
        'pax_label' => $row['pax_label'] ?: ('Max ' . (int) $row['capacity'] . ' guests'),
        'has_pool' => strpos($searchText, 'pool') !== false,
        'has_wifi' => strpos($searchText, 'wifi') !== false,
        'has_bbq' => strpos($searchText, 'bbq') !== false,
    ];

    # assign value $row['accommodation_type'] ke variable $type untuk senang rujuk jenis accommodation
    $type = $row['accommodation_type'];
    # assign value harga (dah tukar float) ke variable $price
    $price = (float) $row['price'];
    # calling function isset() untuk check jenis accommodation ni dah ada dalam $chatbotPriceRanges ke belum
    if (!isset($chatbotPriceRanges[$type])) {
        # assign harga semasa sebagai min & max awal untuk jenis ni
        $chatbotPriceRanges[$type] = ['min' => $price, 'max' => $price];
    } else {
        # calling function min() untuk kemaskini harga paling rendah bagi jenis ni
        $chatbotPriceRanges[$type]['min'] = min($chatbotPriceRanges[$type]['min'], $price);
        # calling function max() untuk kemaskini harga paling tinggi bagi jenis ni
        $chatbotPriceRanges[$type]['max'] = max($chatbotPriceRanges[$type]['max'], $price);
    }
}

# assign array data chatbot (accommodation, price range, address, whatsapp) ke variable $chatbotData untuk guna dalam view
$chatbotData = [
    'accommodations' => $chatbotAccommodations,
    'priceRanges' => $chatbotPriceRanges,
    'address' => 'PT 195, Kg Baru, Kampung Pulau Sayak, 08500 Kota Kuala Muda, Kedah',
    'whatsapp' => '60103851892',
];

# assign value-value setup (base url, active nav, title, css) untuk guna dalam views/index.view.php
$base = '';
$active = 'home';
$pageTitle = 'Casadive Villa — Spend your Dream Holidays with us';
$pageCss = 'style/index.css';

# calling function require() untuk load fail views/index.view.php, papar page homepage guna variable-variable atas ni
require __DIR__ . '/views/index.view.php';
