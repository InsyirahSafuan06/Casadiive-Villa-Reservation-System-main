<?php
/**
 * Halaman butiran penginapan.
 * Halaman ini memaparkan butiran penuh, ciri-ciri, dan kadar bagi pakej vila atau khemah yang dipilih.
 */
require_once __DIR__ . '/../includes/db.php';

// ?id=123 kat URL tu bagitahu kita pakej vila/khemah mana yang nak papar
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$accommodation = null;

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM accommodation WHERE accommodation_id = :id');
    $stmt->execute(['id' => $id]);
    $accommodation = $stmt->fetch(); // false kalau id tu takde dalam DB
}

// features disimpan sebagai satu blok teks, satu baris satu ciri (tengok manage_accommodation.php),
// so kita split ikut baris baru sini, buang baris yang kosong
$features = [];
if ($accommodation && !empty($accommodation['features'])) {
    $features = array_filter(array_map('trim', explode("\n", $accommodation['features'])));
}

$base = '../'; // page ni dalam folder customer/, naik satu tahap untuk pergi root
$active = $accommodation && $accommodation['accommodation_type'] === 'Campsite' ? 'campsite' : 'villa'; // highlight menu betul ikut jenis pakej
$pageTitle = ($accommodation ? $accommodation['accommodation_name'] : 'Package not found') . ' — Casadive Villa';
$pageCss = 'style/detail.css';

// semua logic dah selesai kat atas ni — baris bawah papar HTML page dia.
// HTML/borang tu disimpan berasingan dalam folder views/ supaya file ni tak jadi terlalu panjang.
require __DIR__ . '/views/detail.view.php';
