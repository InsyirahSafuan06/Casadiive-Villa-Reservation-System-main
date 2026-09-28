<?php
# assign value '../' ke variable $base untuk set base path yang guna dalam view
$base = '../';
$active = '';
# assign value tajuk page ke variable $pageTitle untuk papar kat browser tab
$pageTitle = 'Refund Policy — Casadive Villa';
# assign value path css ke variable $pageCss untuk load style khas page ni
$pageCss = 'style/info_page.css';

# calling function require() untuk load & papar view refund_policy
require __DIR__ . '/views/refund_policy.view.php';
