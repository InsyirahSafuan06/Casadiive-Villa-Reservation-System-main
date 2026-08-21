<?php
/**
 * Halaman hubungi kami.
 * Fail ini memaparkan butiran hubungan syarikat dan pautan media sosial untuk pelawat.
 */
$base = '../'; // page ni dalam folder customer/, naik satu tahap untuk pergi root
$active = 'contact'; // untuk highlight menu "Contact Us" kat navbar
$pageTitle = 'Contact Us — Casadive Villa';
$pageCss = 'style/contact_us.css';

// semua logic dah selesai kat atas ni — baris bawah papar HTML page dia.
// HTML/borang tu disimpan berasingan dalam folder views/ supaya file ni tak jadi terlalu panjang.
require __DIR__ . '/views/contact_us.view.php';
