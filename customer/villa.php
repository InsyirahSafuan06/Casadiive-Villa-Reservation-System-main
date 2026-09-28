<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

# calling function trim() that assign to variable name $searchCheckIn untuk buang whitespace tarikh check-in dari url
$searchCheckIn = trim((string) ($_GET['check_in'] ?? ''));
# calling function trim() that assign to variable name $searchCheckOut untuk buang whitespace tarikh check-out dari url
$searchCheckOut = trim((string) ($_GET['check_out'] ?? ''));
# calling function trim() that assign to variable name $searchGuestsRaw untuk buang whitespace bilangan tetamu dari url
$searchGuestsRaw = trim((string) ($_GET['guests'] ?? ''));
# check kalau mana-mana satu field carian diisi, that assign to variable name $searchActive
$searchActive = $searchCheckIn !== '' || $searchCheckOut !== '' || $searchGuestsRaw !== '';
$searchError = null;
$villas = [];

# calling method query() dari object $pdo & fetchColumn() that assign to variable name $maxVillaCapacity untuk ambil kapasiti maksimum villa yang available
$maxVillaCapacity = (int) $pdo->query(
    "SELECT COALESCE(MAX(capacity), 0) FROM accommodation WHERE accommodation_type = 'Villa' AND status = 'available'"
)->fetchColumn();

# check kalau user buat carian (ada isi check-in/check-out/guests)
if ($searchActive) {
    # calling function filter_var() that assign to variable name $searchGuests untuk validate bilangan tetamu ialah integer
    $searchGuests = filter_var($searchGuestsRaw, FILTER_VALIDATE_INT);

    # check kalau bilangan tetamu tak valid atau kurang dari 1
    if ($searchGuests === false || $searchGuests < 1) {
        $searchError = 'Please enter a valid number of guests.';
    } elseif ($searchGuests > $maxVillaCapacity) {
        $searchError = "Sorry, our Villa packages can't accommodate more than {$maxVillaCapacity} guests. Please reduce the number of guests, or try Campsite instead.";
    } elseif ($searchCheckIn !== '' && $searchCheckOut !== '' && $searchCheckOut <= $searchCheckIn) {
        $searchError = 'Check-out date must be after check-in date.';
    } else {
        # check kalau kedua-dua tarikh check-in & check-out diisi, that assign to variable name $useDates
        $useDates = $searchCheckIn !== '' && $searchCheckOut !== '';
        # calling function recommend_accommodations() that assign to variable name $matches untuk cari villa yang sesuai ikut kriteria carian
        $matches = recommend_accommodations($pdo, [
            'guests' => $searchGuests,
            'type' => 'Villa',
            'check_in' => $useDates ? $searchCheckIn : null,
            'check_out' => $useDates ? $searchCheckOut : null,
            'budget' => null,
        ]);
        # calling function array_column() that assign to variable name $villas untuk ambil je bahagian accommodation dari hasil carian
        $villas = array_column($matches, 'accommodation');

        # check kalau takde villa yang match dgn carian
        if (!$villas) {
            $searchError = $useDates
                ? 'No villas are available for those dates with that number of guests. Please try different dates.'
                : 'No villas match that number of guests right now.';
        }
    }
} else {
    # calling method query() dari object $pdo & fetchAll() that assign to variable name $villas untuk ambil semua villa yang available
    $villas = $pdo->query(
        "SELECT * FROM accommodation WHERE accommodation_type = 'Villa' AND status = 'available' ORDER BY accommodation_id"
    )->fetchAll();
}

$icons = [
    'room' => '<svg viewBox="0 0 32 32"><path d="M4 18v8h2v-3h20v3h2v-8a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4z" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="10" cy="15" r="2.5"/></svg>',
    'pool' => '<svg viewBox="0 0 32 32"><path d="M4 24c2.5 0 2.5-3 5-3s2.5 3 5 3 2.5-3 5-3 2.5 3 5 3 2.5-3 5-3v4H4z"/><circle cx="15" cy="10" r="4.5"/></svg>',
    'wifi' => '<svg viewBox="0 0 32 32"><path d="M16 24a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM5.3 13.3c6-6 15.3-6 21.3 0l-2.7 2.7c-4.7-4.7-11.3-4.7-16 0l-2.7-2.7zM9.3 17.3c4-4 9.3-4 13.3 0l-2.7 2.7c-2.7-2.7-5.3-2.7-8 0l-2.7-2.7zM13.3 21.3c2-2 3.3-2 5.3 0l-2.7 2.7-2.7-2.7z"/></svg>',
];

$base = '../';
$active = 'villa';
$pageTitle = 'Villa Packages — Casadive Villa';
$pageCss = 'style/villa.css';

require __DIR__ . '/views/villa.view.php';
