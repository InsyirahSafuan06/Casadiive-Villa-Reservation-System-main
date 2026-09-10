<?php
/**
 * Halaman senarai pakej vila.
 * Halaman ini memaparkan semua pakej vila yang tersedia dan pautkannya ke halaman butiran tempahan.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

// bar "Check Availability" kat homepage hantar customer ke sini bawa check_in/check_out/guests —
// kalau salah satu ada, kita tapis betul-betul ikut availability sebenar (bukan just papar semua)
$searchCheckIn = trim((string) ($_GET['check_in'] ?? ''));
$searchCheckOut = trim((string) ($_GET['check_out'] ?? ''));
$searchGuestsRaw = trim((string) ($_GET['guests'] ?? ''));
$searchActive = $searchCheckIn !== '' || $searchCheckOut !== '' || $searchGuestsRaw !== '';
$searchError = null;
$villas = [];

// had capacity SEBENAR merentasi semua pakej Villa — untuk mesej yang jelas bila tetamu terlalu ramai
$maxVillaCapacity = (int) $pdo->query(
    "SELECT COALESCE(MAX(capacity), 0) FROM accommodation WHERE accommodation_type = 'Villa' AND status = 'available'"
)->fetchColumn();

if ($searchActive) {
    $searchGuests = filter_var($searchGuestsRaw, FILTER_VALIDATE_INT);

    if ($searchGuests === false || $searchGuests < 1) {
        $searchError = 'Please enter a valid number of guests.';
    } elseif ($searchGuests > $maxVillaCapacity) {
        $searchError = "Sorry, our Villa packages can't accommodate more than {$maxVillaCapacity} guests. Please reduce the number of guests, or try Campsite instead.";
    } elseif ($searchCheckIn !== '' && $searchCheckOut !== '' && $searchCheckOut <= $searchCheckIn) {
        $searchError = 'Check-out date must be after check-in date.';
    } else {
        $useDates = $searchCheckIn !== '' && $searchCheckOut !== '';
        $matches = recommend_accommodations($pdo, [
            'guests' => $searchGuests,
            'type' => 'Villa',
            'check_in' => $useDates ? $searchCheckIn : null,
            'check_out' => $useDates ? $searchCheckOut : null,
            'budget' => null,
        ]);
        $villas = array_column($matches, 'accommodation');

        if (!$villas) {
            $searchError = $useDates
                ? 'No villas are available for those dates with that number of guests. Please try different dates.'
                : 'No villas match that number of guests right now.';
        }
    }
} else {
    // takde carian — papar semua vila yang admin dah tandakan "available" je
    $villas = $pdo->query(
        "SELECT * FROM accommodation WHERE accommodation_type = 'Villa' AND status = 'available' ORDER BY accommodation_id"
    )->fetchAll();
}

// icon kecil untuk setiap kad pakej kat bawah tu (room, pool, wifi)
$icons = [
    'room' => '<svg viewBox="0 0 32 32"><path d="M4 18v8h2v-3h20v3h2v-8a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4z" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="10" cy="15" r="2.5"/></svg>',
    'pool' => '<svg viewBox="0 0 32 32"><path d="M4 24c2.5 0 2.5-3 5-3s2.5 3 5 3 2.5-3 5-3 2.5 3 5 3 2.5-3 5-3v4H4z"/><circle cx="15" cy="10" r="4.5"/></svg>',
    'wifi' => '<svg viewBox="0 0 32 32"><path d="M16 24a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM5.3 13.3c6-6 15.3-6 21.3 0l-2.7 2.7c-4.7-4.7-11.3-4.7-16 0l-2.7-2.7zM9.3 17.3c4-4 9.3-4 13.3 0l-2.7 2.7c-2.7-2.7-5.3-2.7-8 0l-2.7-2.7zM13.3 21.3c2-2 3.3-2 5.3 0l-2.7 2.7-2.7-2.7z"/></svg>',
];

$base = '../'; // page ni dalam folder customer/, so kena naik satu tahap untuk pergi root
$active = 'villa'; // untuk highlight menu "Villa" kat navbar
$pageTitle = 'Villa Packages — Casadive Villa';
$pageCss = 'style/villa.css';

// semua logic dah selesai kat atas ni — baris bawah papar HTML page dia.
// HTML/borang tu disimpan berasingan dalam folder views/ supaya file ni tak jadi terlalu panjang.
require __DIR__ . '/views/villa.view.php';
