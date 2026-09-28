<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');

$guests = filter_input(INPUT_GET, 'guests', FILTER_VALIDATE_INT);
if ($guests === false || $guests === null || $guests < 1) {
    echo json_encode(['results' => [], 'count' => 0, 'error' => 'invalid_guests']);
    exit;
}

$typeRaw = trim((string) ($_GET['type'] ?? ''));
$type = in_array($typeRaw, ['Villa', 'Campsite'], true) ? $typeRaw : null;

$checkIn = trim((string) ($_GET['check_in'] ?? ''));
$checkOut = trim((string) ($_GET['check_out'] ?? ''));
$useDates = $checkIn !== '' && $checkOut !== '' && $checkOut > $checkIn;

$budgetRaw = trim((string) ($_GET['budget'] ?? ''));
$budget = ($budgetRaw !== '' && filter_var($budgetRaw, FILTER_VALIDATE_FLOAT) !== false) ? (float) $budgetRaw : null;

$facility = strtolower(trim((string) ($_GET['facility'] ?? '')));

$matches = recommend_accommodations($pdo, [
    'guests' => $guests,
    'type' => $type,
    'check_in' => $useDates ? $checkIn : null,
    'check_out' => $useDates ? $checkOut : null,
    'budget' => $budget,
]);

$results = [];
foreach ($matches as $item) {
    $acc = $item['accommodation'];
    $searchText = strtolower(($acc['features'] ?? '') . ' ' . ($acc['description'] ?? ''));
    $hasPool = strpos($searchText, 'pool') !== false;
    $hasWifi = strpos($searchText, 'wifi') !== false;
    $hasBbq = strpos($searchText, 'bbq') !== false;
    $matchesFacility = $facility !== '' && strpos($searchText, $facility) !== false;

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

if ($facility !== '') {
    usort($results, function ($a, $b) {
        return ($b['matches_facility'] ? 1 : 0) <=> ($a['matches_facility'] ? 1 : 0);
    });
}

$results = array_slice($results, 0, 3);

echo json_encode(['results' => $results, 'count' => count($results)]);
