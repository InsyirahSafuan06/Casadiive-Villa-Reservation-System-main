<?php
# calling function require_once() untuk load fail db.php supaya dapat object $pdo untuk connect database
require_once __DIR__ . '/../includes/db.php';
# calling function require_once() untuk load fail helpers.php supaya boleh guna fungsi helper macam recommend_accommodations()
require_once __DIR__ . '/../includes/helpers.php';

# ambil value $_GET['check_in'] then trim, assign to variable name $searchCheckIn
$searchCheckIn = trim((string) ($_GET['check_in'] ?? ''));
# ambil value $_GET['check_out'] then trim, assign to variable name $searchCheckOut
$searchCheckOut = trim((string) ($_GET['check_out'] ?? ''));
# ambil value $_GET['guests'] then trim, assign to variable name $searchGuestsRaw
$searchGuestsRaw = trim((string) ($_GET['guests'] ?? ''));
# check ada mana-mana field carian yang diisi untuk tentukan customer buat search ke tidak
$searchActive = $searchCheckIn !== '' || $searchCheckOut !== '' || $searchGuestsRaw !== '';
# assign null ke $searchError sbb belum ada error lagi
$searchError = null;
# assign array kosong ke $campsites untuk default takde hasil lagi
$campsites = [];

# calling method query() & fetchColumn() dari object $pdo that assign to variable name $maxCampsiteCapacity untuk dapatkan kapasiti maksimum campsite
$maxCampsiteCapacity = (int) $pdo->query(
    "SELECT COALESCE(MAX(capacity), 0) FROM accommodation WHERE accommodation_type = 'Campsite' AND status = 'available'"
)->fetchColumn();

# check kalau customer buat search, jalankan validation dan carian ikut kriteria
if ($searchActive) {
    # calling function filter_var() that assign to variable name $searchGuests untuk validate bilangan tetamu adalah integer
    $searchGuests = filter_var($searchGuestsRaw, FILTER_VALIDATE_INT);

    # check kalau bilangan tetamu tak valid atau kurang dari 1
    if ($searchGuests === false || $searchGuests < 1) {
        $searchError = 'Please enter a valid number of guests.';
    } elseif ($searchGuests > $maxCampsiteCapacity) {
        # check bilangan tetamu lebih dari kapasiti maksimum campsite yang ada
        $searchError = "Sorry, our Campsite packages can't accommodate more than {$maxCampsiteCapacity} guests. Please reduce the number of guests, or try Villa instead.";
    } elseif ($searchCheckIn !== '' && $searchCheckOut !== '' && $searchCheckOut <= $searchCheckIn) {
        # check tarikh check-out kena lepas tarikh check-in
        $searchError = 'Check-out date must be after check-in date.';
    } else {
        # check kedua-dua tarikh diisi, assign to variable name $useDates untuk tentukan filter guna tarikh ke tidak
        $useDates = $searchCheckIn !== '' && $searchCheckOut !== '';
        # calling function recommend_accommodations() that assign to variable name $matches untuk cari campsite yang sesuai ikut kriteria
        $matches = recommend_accommodations($pdo, [
            'guests' => $searchGuests,
            'type' => 'Campsite',
            'check_in' => $useDates ? $searchCheckIn : null,
            'check_out' => $useDates ? $searchCheckOut : null,
            'budget' => null,
        ]);
        # calling function array_column() that assign to variable name $campsites untuk ambil je bahagian 'accommodation' dari setiap match
        $campsites = array_column($matches, 'accommodation');

        # check kalau takde campsite yang jumpa, set mesej error ikut ada guna tarikh ke tidak
        if (!$campsites) {
            $searchError = $useDates
                ? 'No campsite packages are available for those dates with that number of guests. Please try different dates.'
                : 'No campsite packages match that number of guests right now.';
        }
    }
} else {
    # calling method query() & fetchAll() dari object $pdo that assign to variable name $campsites untuk ambil semua campsite yang available
    $campsites = $pdo->query(
        "SELECT * FROM accommodation WHERE accommodation_type = 'Campsite' AND status = 'available' ORDER BY accommodation_id"
    )->fetchAll();
}

$ratePeriodsByAccommodation = [];
foreach (fetch_accommodation_rate_periods($pdo, upcomingOnly: true) as $ratePeriod) {
    $ratePeriodsByAccommodation[(int) $ratePeriod['accommodation_id']][] = $ratePeriod;
}
$rateDate = resolve_accommodation_rate_date($searchCheckIn !== '' ? $searchCheckIn : null);

# assign array svg icon ke $icons untuk paparan ikon kat page campsite
$icons = [
    'site' => '<svg viewBox="0 0 32 32"><path d="M6 14a5 5 0 0 1 10 0v2H6z"/><rect x="4" y="16" width="24" height="10" rx="2" fill="none" stroke="currentColor" stroke-width="2"/></svg>',
    'pool' => '<svg viewBox="0 0 32 32"><path d="M4 24c2.5 0 2.5-3 5-3s2.5 3 5 3 2.5-3 5-3 2.5 3 5 3 2.5-3 5-3v4H4z"/><circle cx="15" cy="10" r="4.5"/></svg>',
    'tent' => '<svg viewBox="0 0 32 32"><path d="M16 6 4 26h24z" fill="none" stroke="currentColor" stroke-width="2"/><path d="M16 6v20" stroke="currentColor" stroke-width="2"/></svg>',
];

# assign value '../' ke variable $base untuk set path relative balik ke root folder
$base = '../';
# assign 'campsite' ke $active untuk highlight nav item Campsite
$active = 'campsite';
# assign value title page ke $pageTitle untuk papar kat tag <title> dan header
$pageTitle = 'Campsite Packages — Casadive Villa';
# assign path css khas untuk page ni ke $pageCss
$pageCss = 'style/campsite.css';

# calling function require() untuk load fail view campsite supaya papar html page ni
require __DIR__ . '/views/campsite.view.php';
