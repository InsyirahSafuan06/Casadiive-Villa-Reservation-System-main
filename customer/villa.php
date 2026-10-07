<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

# Trim whitespace from the check-in date in the URL and assign it to $searchCheckIn.
$searchCheckIn = trim((string) ($_GET['check_in'] ?? ''));
# Trim whitespace from the check-out date in the URL and assign it to $searchCheckOut.
$searchCheckOut = trim((string) ($_GET['check_out'] ?? ''));
# Trim whitespace from the guest count in the URL and assign it to $searchGuestsRaw.
$searchGuestsRaw = trim((string) ($_GET['guests'] ?? ''));
# Check whether any search field is filled and assign the result to $searchActive.
$searchActive = $searchCheckIn !== '' || $searchCheckOut !== '' || $searchGuestsRaw !== '';
$searchError = null;
$villas = [];

# Query the maximum capacity of an available villa and assign it to $maxVillaCapacity.
$maxVillaCapacity = (int) $pdo->query(
    "SELECT COALESCE(MAX(capacity), 0) FROM accommodation WHERE accommodation_type = 'Villa' AND status = 'available'"
)->fetchColumn();

# Check whether the user submitted a search (check-in, check-out, or guest count).
if ($searchActive) {
    # Validate that the guest count is an integer and assign it to $searchGuests.
    $searchGuests = filter_var($searchGuestsRaw, FILTER_VALIDATE_INT);

    # Check whether the guest count is invalid or less than 1.
    if ($searchGuests === false || $searchGuests < 1) {
        $searchError = 'Please enter a valid number of guests.';
    } elseif ($searchGuests > $maxVillaCapacity) {
        $searchError = "Sorry, our Villa packages can't accommodate more than {$maxVillaCapacity} guests. Please reduce the number of guests, or try Campsite instead.";
    } elseif ($searchCheckIn !== '' && $searchCheckOut !== '' && $searchCheckOut <= $searchCheckIn) {
        $searchError = 'Check-out date must be after check-in date.';
    } else {
        # Check whether both check-in and check-out dates are provided.
        $useDates = $searchCheckIn !== '' && $searchCheckOut !== '';
        # Find villas that match the search criteria and assign them to $matches.
        $matches = recommend_accommodations($pdo, [
            'guests' => $searchGuests,
            'type' => 'Villa',
            'check_in' => $useDates ? $searchCheckIn : null,
            'check_out' => $useDates ? $searchCheckOut : null,
            'budget' => null,
        ]);
        # Extract the accommodation entries from the search results.
        $villas = array_column($matches, 'accommodation');

        # Check whether any villas match the search.
        if (!$villas) {
            $searchError = $useDates
                ? 'No villas are available for those dates with that number of guests. Please try different dates.'
                : 'No villas match that number of guests right now.';
        }
    }
} else {
    # Fetch all available villas.
    $villas = $pdo->query(
        "SELECT * FROM accommodation WHERE accommodation_type = 'Villa' AND status = 'available' ORDER BY accommodation_id"
    )->fetchAll();
}

$ratePeriodsByAccommodation = [];
$ratePeriodRows = fetch_accommodation_rate_periods($pdo, upcomingOnly: true);
foreach ($ratePeriodRows as $ratePeriod) {
    $ratePeriodsByAccommodation[(int) $ratePeriod['accommodation_id']][] = $ratePeriod;
}
$rateDate = resolve_accommodation_rate_date($searchCheckIn !== '' ? $searchCheckIn : null);

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
