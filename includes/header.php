<?php
/**
 * Susun atur header yang dikongsi untuk laman web.
 * Fail ini membina navigasi atas dan struktur halaman umum yang digunakan di seluruh projek.
 */
declare(strict_types=1);
require_once __DIR__ . '/auth.php';

/**
 * Pembolehubah yang dijangka daripada halaman yang memasukkan fail ini:
 *   $base      string  '' di root projek, '../' satu tahap ke bawah (customer/, user/)
 *   $active    string  salah satu daripada home|villa|campsite|gallery|mybooking|contact
 *   $pageTitle string  teks <title>
 *   $pageCss   string  href stylesheet, relatif kepada halaman yang memasukkannya
 */
$base ??= ''; // kalau page tak set $base, default kosong (maksudnya kita kat root folder)
$active ??= ''; // page mana yang sedang aktif, untuk highlight menu navigasi
$pageTitle ??= 'Casadive Villa'; // tajuk default kalau page tak bagi tajuk sendiri
$pageCss ??= ''; // stylesheet khas untuk page ni (kalau ada)
$loggedInUser = current_user(); // check siapa yang login sekarang (kalau ada)

// ni untuk elak browser guna CSS lama yang tersimpan (cache). kita tambah "?v=" + masa
// fail CSS last diubah, supaya bila kita edit CSS, browser terus ambil versi baru
$pageCssHref = $pageCss;
if ($pageCss !== '') {
    $pageCssPath = dirname($_SERVER['SCRIPT_FILENAME']) . '/' . $pageCss;
    if (is_file($pageCssPath)) {
        $pageCssHref .= '?v=' . filemtime($pageCssPath);
    }
}

// shared mobile-nav CSS/JS, cache-busted the same way as $pageCss above.
// loaded after $pageCss so `.nav-links.is-open` can override that page's
// own `@media (max-width:760px){ .nav-links{display:none} }` rule.
$navCssHref = $base . 'assets/css/nav-responsive.css';
$navCssPath = __DIR__ . '/../assets/css/nav-responsive.css';
if (is_file($navCssPath)) {
    $navCssHref .= '?v=' . filemtime($navCssPath);
}

$navJsHref = $base . 'assets/js/nav-toggle.js';
$navJsPath = __DIR__ . '/../assets/js/nav-toggle.js';
if (is_file($navJsPath)) {
    $navJsHref .= '?v=' . filemtime($navJsPath);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600;700&family=Mulish:wght@400;600;700;800&family=Poppins:wght@300;400;500;600;700&family=Raleway:wght@500;600;700&family=Playfair+Display:wght@700;800&family=Inter:wght@500&display=swap" rel="stylesheet">
<?php if ($pageCss): ?>
<link rel="stylesheet" href="<?= htmlspecialchars($pageCssHref) ?>">
<?php endif; ?>
<link rel="stylesheet" href="<?= htmlspecialchars($navCssHref) ?>">
</head>
<body>

  <!-- NAVBAR -->
  <header class="navbar">
    <div class="container">
      <div class="brand">Casadive Villa</div>
      <nav class="nav-links" id="navLinks" aria-label="Primary">
        <a href="<?= $base ?>index.php"<?= $active === 'home' ? ' aria-current="page"' : '' ?>>Home</a>
        <a href="<?= $base ?>customer/villa.php"<?= $active === 'villa' ? ' aria-current="page"' : '' ?>>Villa</a>
        <a href="<?= $base ?>customer/campsite.php"<?= $active === 'campsite' ? ' aria-current="page"' : '' ?>>Campsite</a>
        <a href="<?= $base ?>customer/gallery.php"<?= $active === 'gallery' ? ' aria-current="page"' : '' ?>>Gallery</a>
        <a href="<?= $base ?>customer/mybooking.php"<?= $active === 'mybooking' ? ' aria-current="page"' : '' ?>>MyBooking</a>
        <a href="<?= $base ?>customer/contact_us.php"<?= $active === 'contact' ? ' aria-current="page"' : '' ?>>Contact Us</a>
      </nav>
      <?php if ($loggedInUser): ?>
        <a href="<?= $base ?>user/<?= $loggedInUser['role'] === 'admin' ? 'admin_dashboard.php' : 'staff_dashboard.php' ?>" class="btn btn-primary">Dashboard</a>
      <?php else: ?>
        <a href="<?= $base ?>user/login.php" class="btn btn-primary">Sign In</a>
      <?php endif; ?>
      <button type="button" class="nav-toggle" id="navToggle" aria-label="Toggle menu" aria-expanded="false" aria-controls="navLinks">
        <span class="nav-toggle-bars"><span></span><span></span><span></span></span>
      </button>
    </div>
  </header>
  <script src="<?= htmlspecialchars($navJsHref) ?>" defer></script>
