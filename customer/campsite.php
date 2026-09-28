<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$searchCheckIn = trim((string) ($_GET['check_in'] ?? ''));
$searchCheckOut = trim((string) ($_GET['check_out'] ?? ''));
$searchGuestsRaw = trim((string) ($_GET['guests'] ?? ''));
$searchActive = $searchCheckIn !== '' || $searchCheckOut !== '' || $searchGuestsRaw !== '';
$searchError = null;
$campsites = [];

$maxCampsiteCapacity = (int) $pdo->query(
    "SELECT COALESCE(MAX(capacity), 0) FROM accommodation WHERE accommodation_type = 'Campsite' AND status = 'available'"
)->fetchColumn();

if ($searchActive) {
    $searchGuests = filter_var($searchGuestsRaw, FILTER_VALIDATE_INT);

    if ($searchGuests === false || $searchGuests < 1) {
        $searchError = 'Please enter a valid number of guests.';
    } elseif ($searchGuests > $maxCampsiteCapacity) {
        $searchError = "Sorry, our Campsite packages can't accommodate more than {$maxCampsiteCapacity} guests. Please reduce the number of guests, or try Villa instead.";
    } elseif ($searchCheckIn !== '' && $searchCheckOut !== '' && $searchCheckOut <= $searchCheckIn) {
        $searchError = 'Check-out date must be after check-in date.';
    } else {
        $useDates = $searchCheckIn !== '' && $searchCheckOut !== '';
        $matches = recommend_accommodations($pdo, [
            'guests' => $searchGuests,
            'type' => 'Campsite',
            'check_in' => $useDates ? $searchCheckIn : null,
            'check_out' => $useDates ? $searchCheckOut : null,
            'budget' => null,
        ]);
        $campsites = array_column($matches, 'accommodation');

        if (!$campsites) {
            $searchError = $useDates
                ? 'No campsite packages are available for those dates with that number of guests. Please try different dates.'
                : 'No campsite packages match that number of guests right now.';
        }
    }
} else {
    $campsites = $pdo->query(
        "SELECT * FROM accommodation WHERE accommodation_type = 'Campsite' AND status = 'available' ORDER BY accommodation_id"
    )->fetchAll();
}

$icons = [
    'site' => '<svg viewBox="0 0 32 32"><path d="M6 14a5 5 0 0 1 10 0v2H6z"/><rect x="4" y="16" width="24" height="10" rx="2" fill="none" stroke="currentColor" stroke-width="2"/></svg>',
    'pool' => '<svg viewBox="0 0 32 32"><path d="M4 24c2.5 0 2.5-3 5-3s2.5 3 5 3 2.5-3 5-3 2.5 3 5 3 2.5-3 5-3v4H4z"/><circle cx="15" cy="10" r="4.5"/></svg>',
    'tent' => '<svg viewBox="0 0 32 32"><path d="M16 6 4 26h24z" fill="none" stroke="currentColor" stroke-width="2"/><path d="M16 6v20" stroke="currentColor" stroke-width="2"/></svg>',
];

$base = '../';
$active = 'campsite';
$pageTitle = 'Campsite Packages — Casadive Villa';
$pageCss = 'style/campsite.css';

require __DIR__ . '/views/campsite.view.php';
