<?php
require_once __DIR__ . '/../includes/db.php';

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

try {
    $images = $pdo->query('SELECT image_path, caption FROM gallery ORDER BY gallery_id DESC')->fetchAll();
    if (!$images) {
        $images = $fallbackImages;
    }
} catch (PDOException $e) {
    error_log('Failed to load gallery images: ' . $e->getMessage());
    $images = $fallbackImages;
}

$base = '../';
$active = 'gallery';
$pageTitle = 'Gallery — Casadive Villa';
$pageCss = 'style/gallery.css';

require __DIR__ . '/views/gallery.view.php';
