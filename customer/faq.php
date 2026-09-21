<?php
/**
 * Halaman F.A.Q.
 * Fail ini memaparkan soalan lazim tentang tempahan, bayaran, dan penginapan.
 */
$base = '../'; // page ni dalam folder customer/, naik satu tahap untuk pergi root
$active = ''; // page ni bukan salah satu menu utama navbar
$pageTitle = 'F.A.Q — Casadive Villa';
$pageCss = 'style/info_page.css';

require __DIR__ . '/views/faq.view.php';
