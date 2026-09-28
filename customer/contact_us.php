<?php
# assign value '../' ke variable $base untuk set path relative balik ke root folder
$base = '../';
# assign 'contact' ke $active untuk highlight nav item Contact Us
$active = 'contact';
# assign value title page ke $pageTitle untuk papar kat tag <title> dan header
$pageTitle = 'Contact Us — Casadive Villa';
# assign path css khas untuk page ni ke $pageCss
$pageCss = 'style/contact_us.css';

# calling function require() untuk load fail view contact_us supaya papar html page ni
require __DIR__ . '/views/contact_us.view.php';
