<?php
require_once __DIR__ . '/includes/db.php'; 

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

$accommodationRows = $pdo->query(
    "SELECT accommodation_id, accommodation_name, accommodation_type, price, capacity,
            pax_label, features, description
     FROM accommodation WHERE status = 'available' ORDER BY accommodation_type, accommodation_id"
)->fetchAll();

$chatbotAccommodations = [];
$chatbotPriceRanges = [];
foreach ($accommodationRows as $row) {
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
    'address' => 'PT 195, Kg Baru, Kampung Pulau Sayak, 08500 Kota Kuala Muda, Kedah',
    'whatsapp' => '60103851892',
];

$base = ''; 
$active = 'home'; 
$pageTitle = 'Casadive Villa — Spend your Dream Holidays with us';
$pageCss = 'style/index.css'; 

require __DIR__ . '/views/index.view.php';
