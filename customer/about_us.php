<?php
/**
 * Halaman About Us.
 * Fail ini memaparkan cerita ringkas dan maklumat am tentang Casadive Villa.
 */
$base = '../'; // page ni dalam folder customer/, naik satu tahap untuk pergi root
$active = ''; // page ni bukan salah satu menu utama navbar
$pageTitle = 'About Us — Casadive Villa';
$pageCss = 'style/info_page.css';

require __DIR__ . '/views/about_us.view.php';
