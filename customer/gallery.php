<?php
/**
 * Halaman galeri.
 * Fail ini memaparkan tunjuk gambar visual bagi pengalaman vila dan khemah.
 */
$base = '../'; // page ni dalam folder customer/, naik satu tahap untuk pergi root
$active = 'gallery'; // untuk highlight menu "Gallery" kat navbar
$pageTitle = 'Gallery — Casadive Villa';
$pageCss = 'style/gallery.css';

// semua logic dah selesai kat atas ni — baris bawah papar HTML page dia.
// HTML/borang tu disimpan berasingan dalam folder views/ supaya file ni tak jadi terlalu panjang.
require __DIR__ . '/views/gallery.view.php';
