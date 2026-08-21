<?php
/**
 * Halaman senarai pakej khemah.
 * Halaman ini memaparkan semua pilihan khemah yang tersedia dan pautkan setiap satu ke halaman tempahan atau butiran.
 */
require_once __DIR__ . '/../includes/db.php';

// ambil campsite yang admin dah tandakan "available" je
$campsites = $pdo->query(
    "SELECT * FROM accommodation WHERE accommodation_type = 'Campsite' AND status = 'available' ORDER BY accommodation_id"
)->fetchAll();

// icon kecil untuk setiap kad pakej kat bawah tu (site, pool, tent)
$icons = [
    'site' => '<svg viewBox="0 0 32 32"><path d="M6 14a5 5 0 0 1 10 0v2H6z"/><rect x="4" y="16" width="24" height="10" rx="2" fill="none" stroke="currentColor" stroke-width="2"/></svg>',
    'pool' => '<svg viewBox="0 0 32 32"><path d="M4 24c2.5 0 2.5-3 5-3s2.5 3 5 3 2.5-3 5-3 2.5 3 5 3 2.5-3 5-3v4H4z"/><circle cx="15" cy="10" r="4.5"/></svg>',
    'tent' => '<svg viewBox="0 0 32 32"><path d="M16 6 4 26h24z" fill="none" stroke="currentColor" stroke-width="2"/><path d="M16 6v20" stroke="currentColor" stroke-width="2"/></svg>',
];

$base = '../'; // page ni dalam folder customer/, naik satu tahap untuk pergi root
$active = 'campsite'; // untuk highlight menu "Campsite" kat navbar
$pageTitle = 'Campsite Packages — Casadive Villa';
$pageCss = 'style/campsite.css';

// semua logic dah selesai kat atas ni — baris bawah papar HTML page dia.
// HTML/borang tu disimpan berasingan dalam folder views/ supaya file ni tak jadi terlalu panjang.
require __DIR__ . '/views/campsite.view.php';
