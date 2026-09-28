<?php
# assign value '../' ke variable $base untuk set path relative balik ke root folder
$base = '../';
# assign string kosong ke $active sbb page ni takde nav item yang kena highlight
$active = '';
# assign value title page ke $pageTitle untuk papar kat tag <title> dan header
$pageTitle = 'F.A.Q — Casadive Villa';
# assign path css khas untuk page ni ke $pageCss
$pageCss = 'style/info_page.css';

# calling function require() untuk load fail view faq supaya papar html page ni
require __DIR__ . '/views/faq.view.php';
