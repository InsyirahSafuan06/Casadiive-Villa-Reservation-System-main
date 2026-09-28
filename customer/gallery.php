<?php
# calling function require_once() untuk load fail db.php supaya dapat object $pdo untuk connect database
require_once __DIR__ . '/../includes/db.php';

# assign array gambar default ke $fallbackImages untuk backup kalau table gallery kosong/error
$fallbackImages = [
    ['image_path' => 'assets/images/villa-complex-day.jpg', 'caption' => 'Villa exterior view'],
    ['image_path' => 'assets/images/villa-bedroom-bunk.jpg', 'caption' => 'Villa bedroom'],
    ['image_path' => 'assets/images/villa-living-room.jpg', 'caption' => 'Villa living area'],
    ['image_path' => 'assets/images/campsite-tents-pool.jpg', 'caption' => 'Campsite by the beach'],
    ['image_path' => 'assets/images/campsite-tent-pool.jpg', 'caption' => 'Swimming pool'],
    ['image_path' => 'assets/images/beach-lounge-bench.jpg', 'caption' => 'Poolside lounge'],
    ['image_path' => 'assets/images/wooden-villa-sunset.jpg', 'caption' => 'Beachfront sunset'],
    ['image_path' => 'assets/images/casa3-balcony-view.jpg', 'caption' => 'Villa balcony view'],
    ['image_path' => 'assets/images/wooden-villa-porch-view.jpg', 'caption' => 'Beachside walkway'],
];

# calling method query() & fetchAll() dari object $pdo that assign to variable name $images untuk ambil semua gambar dari table gallery
try {
    $images = $pdo->query('SELECT image_path, caption FROM gallery ORDER BY gallery_id DESC')->fetchAll();
    # check kalau $images kosong, guna fallback image sebab table gallery takde data
    if (!$images) {
        $images = $fallbackImages;
    }
} catch (PDOException $e) {
    # calling function error_log() untuk simpan mesej error dalam log server
    error_log('Failed to load gallery images: ' . $e->getMessage());
    # assign fallback images ke $images sbb query gagal
    $images = $fallbackImages;
}

# assign value '../' ke variable $base untuk set path relative balik ke root folder
$base = '../';
# assign 'gallery' ke $active untuk highlight nav item Gallery
$active = 'gallery';
# assign value title page ke $pageTitle untuk papar kat tag <title> dan header
$pageTitle = 'Gallery — Casadive Villa';
# assign path css khas untuk page ni ke $pageCss
$pageCss = 'style/gallery.css';

# calling function require() untuk load fail view gallery supaya papar html page ni
require __DIR__ . '/views/gallery.view.php';
