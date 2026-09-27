<?php
/**
 * Halaman galeri.
 * Fail ini memaparkan tunjuk gambar visual bagi pengalaman vila dan khemah — gambar diurus
 * oleh manager/staff melalui dashboard mereka (lihat user/admin_dashboard.php / staff_dashboard.php).
 */
require_once __DIR__ . '/../includes/db.php';

// gambar asal laman ni (dulu hardcode terus dalam HTML) — kekal sebagai fallback SEMENTARA
// kalau jadual `gallery` belum wujud/kosong (contoh: production belum di-migrate lagi), supaya
// pelawat TAK PERNAH nampak galeri kosong. Sekali staff/manager upload gambar dari dashboard,
// baris DB akan diguna terus (lihat bawah), fallback ni tak lagi relevan.
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

$base = '../'; // page ni dalam folder customer/, naik satu tahap untuk pergi root
$active = 'gallery'; // untuk highlight menu "Gallery" kat navbar
$pageTitle = 'Gallery — Casadive Villa';
$pageCss = 'style/gallery.css';

// semua logic dah selesai kat atas ni — baris bawah papar HTML page dia.
// HTML/borang tu disimpan berasingan dalam folder views/ supaya file ni tak jadi terlalu panjang.
require __DIR__ . '/views/gallery.view.php';
