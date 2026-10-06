<?php
# calling function require_once() untuk load fail db.php supaya dapat object $pdo untuk connect database
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

# calling function filter_input() that assign to variable name $id untuk ambil & validate value 'id' dari url query string
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
# assign null ke $accommodation sbb belum tentu jumpa data lagi
$accommodation = null;

# check kalau $id valid (bukan false/null/0) baru buat query cari accommodation
if ($id) {
    # calling method prepare() dari object $pdo that assign to variable name $stmt untuk sediakan query cari accommodation ikut id
    $stmt = $pdo->prepare('SELECT * FROM accommodation WHERE accommodation_id = :id');
    # calling method execute() dari object $stmt untuk jalankan query, isi placeholder :id dgn value sebenar
    $stmt->execute(['id' => $id]);
    # calling method fetch() dari object $stmt that assign to variable name $accommodation untuk ambil 1 row hasil query
    $accommodation = $stmt->fetch();
}

# assign array kosong ke $features untuk default takde ciri-ciri lagi
$features = [];
# check kalau accommodation wujud dan field features tak kosong
if ($accommodation && !empty($accommodation['features'])) {
    # calling function array_filter(), array_map() & explode() that assign to variable name $features untuk pecahkan text features ikut baris jadi array
    $features = array_filter(array_map('trim', explode("\n", $accommodation['features'])));
}
$ratePeriods = [];
if ($accommodation) {
    $ratePeriods = fetch_accommodation_rate_periods($pdo, (int) $accommodation['accommodation_id'], true);
}

# assign value '../' ke variable $base untuk set path relative balik ke root folder
$base = '../';
# check jenis accommodation Campsite ke tidak untuk tentukan nav item mana kena highlight
$active = $accommodation && $accommodation['accommodation_type'] === 'Campsite' ? 'campsite' : 'villa';
# assign nama package atau mesej 'not found' ke $pageTitle untuk papar kat tag <title>
$pageTitle = ($accommodation ? $accommodation['accommodation_name'] : 'Package not found') . ' — Casadive Villa';
# assign path css khas untuk page ni ke $pageCss
$pageCss = 'style/detail.css';

# calling function require() untuk load fail view detail supaya papar html page ni
require __DIR__ . '/views/detail.view.php';
