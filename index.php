<?php
/**
 * Halaman utama sistem tempahan Casadive Villa.
 * Halaman ini memaparkan laman pendaratan utama, borang tempahan pantas, kemudahan, dan ulasan tetamu terkini.
 */
require_once __DIR__ . '/includes/db.php'; // sambung ke database dulu, dapat $pdo

// ambil 6 ulasan terbaru je untuk letak kat homepage, susun dari yang paling baru
// r.display_name ialah nama yang guest PILIH untuk tunjuk secara terbuka — bukan nama sebenar
// dalam booking dia (c.full_name). Kosong/anonymous bermaksud kita papar "Anonymous" je.
//
// LEFT JOIN (bukan JOIN) sebab footer review widget takde ref/phone langsung — review dari
// situ tersimpan dengan booking_id NULL (unverified), so takde row booking/customer untuk
// dipadan. c.location jadi NULL untuk review macam tu, dah dihandle guna if() kat view.
//
// Dibalut try/catch supaya kalau database belum kena migrate (contoh: lupa jalankan
// database/add_review_display_name.sql lepas deploy), homepage still load — cuma
// testimonials section jadi kosong — dan bukan seluruh page fatal error.
try {
    $reviews = $pdo->query(
        "SELECT r.rating, r.comment, r.image_path, r.review_date, r.display_name, c.location
         FROM review r
         LEFT JOIN booking b ON b.booking_id = r.booking_id
         LEFT JOIN customer c ON c.customer_id = b.customer_id
         ORDER BY r.review_date DESC
         LIMIT 6"
    )->fetchAll();
} catch (PDOException $e) {
    error_log('Failed to load homepage reviews: ' . $e->getMessage());
    $reviews = [];
}

// data sebenar dari accommodation table untuk AI Assistant guna terus (tanpa round-trip) —
// senarai + julat harga untuk jawab "Villa Facilities"/harga. Untuk cadangan ikut tarikh/budget/
// bilangan tetamu (data yang berubah ikut input customer), chatbot panggil
// customer/chatbot_recommend.php yang guna recommend_accommodations() (includes/helpers.php).
$accommodationRows = $pdo->query(
    "SELECT accommodation_id, accommodation_name, accommodation_type, price, capacity,
            pax_label, features, description
     FROM accommodation WHERE status = 'available' ORDER BY accommodation_type, accommodation_id"
)->fetchAll();

$chatbotAccommodations = [];
$chatbotPriceRanges = [];
foreach ($accommodationRows as $row) {
    // takde column khas "ada pool/wifi/bbq" dalam DB, so kita cari perkataan tu dalam
    // features/description — teknik sama macam villa.php guna untuk icon kemudahan
    $searchText = strtolower(($row['features'] ?? '') . ' ' . ($row['description'] ?? ''));
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

    $type = $row['accommodation_type'];
    $price = (float) $row['price'];
    if (!isset($chatbotPriceRanges[$type])) {
        $chatbotPriceRanges[$type] = ['min' => $price, 'max' => $price];
    } else {
        $chatbotPriceRanges[$type]['min'] = min($chatbotPriceRanges[$type]['min'], $price);
        $chatbotPriceRanges[$type]['max'] = max($chatbotPriceRanges[$type]['max'], $price);
    }
}

$chatbotData = [
    'accommodations' => $chatbotAccommodations,
    'priceRanges' => $chatbotPriceRanges,
    // alamat sebenar, sama seperti dipaparkan di contact_us.php — bukan direka
    'address' => 'PT 195, Kg Baru, Kampung Pulau Sayak, 08500 Kota Kuala Muda, Kedah',
    'whatsapp' => '60103851892',
];

$base = ''; // kita kat root folder, so path takyah naik satu tahap
$active = 'home'; // untuk highlight menu "Home" kat navbar
$pageTitle = 'Casadive Villa — Spend your Dream Holidays with us';
$pageCss = 'style/index.css'; // CSS khas untuk page ni

// semua logic dah selesai kat atas ni — baris bawah papar HTML page dia.
// HTML/borang tu disimpan berasingan dalam folder views/ supaya file ni tak jadi terlalu panjang.
require __DIR__ . '/views/index.view.php';
