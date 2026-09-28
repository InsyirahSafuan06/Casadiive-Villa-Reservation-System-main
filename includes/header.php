<?php
declare(strict_types=1);
# calling require_once untuk masukkan fail auth.php supaya boleh guna fungsi login macam current_user()
require_once __DIR__ . '/auth.php';

# assign value kosong ke $base kalau belum wujud, untuk elak error undefined variable
$base ??= '';
# assign value kosong ke $active kalau belum wujud, untuk tentukan nav link mana yang aktif
$active ??= '';
# assign value default tajuk page ke $pageTitle kalau belum wujud
$pageTitle ??= 'Casadive Villa';
# assign value kosong ke $pageCss kalau belum wujud
$pageCss ??= '';
# calling function current_user() that assign to variable name $loggedInUser untuk tahu siapa yang sedang login (kalau ada)
$loggedInUser = current_user();

$pageCssHref = $pageCss;
# check kalau page ni ada css sendiri, kena tentukan path fail & tambah cache busting
if ($pageCss !== '') {
    $pageCssPath = dirname($_SERVER['SCRIPT_FILENAME']) . '/' . $pageCss;
    # check kalau fail css tu wujud, tambah version string supaya browser cache dikemaskini bila fail berubah
    if (is_file($pageCssPath)) {
        # calling function filemtime() untuk ambil masa fail terakhir diubah, tambah kat href sbb cache busting
        $pageCssHref .= '?v=' . filemtime($pageCssPath);
    }
}

$navCssHref = $base . 'assets/css/nav-responsive.css';
$navCssPath = __DIR__ . '/../assets/css/nav-responsive.css';
# check kalau fail css nav wujud, tambah version string supaya browser cache dikemaskini bila fail berubah
if (is_file($navCssPath)) {
    # calling function filemtime() untuk ambil masa fail terakhir diubah, tambah kat href sbb cache busting
    $navCssHref .= '?v=' . filemtime($navCssPath);
}

$navJsHref = $base . 'assets/js/nav-toggle.js';
$navJsPath = __DIR__ . '/../assets/js/nav-toggle.js';
# check kalau fail js nav wujud, tambah version string supaya browser cache dikemaskini bila fail berubah
if (is_file($navJsPath)) {
    # calling function filemtime() untuk ambil masa fail terakhir diubah, tambah kat href sbb cache busting
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

  <header class="navbar">
    <div class="container">
      <a href="<?= $base ?>index.php" class="nav-logo-link">
        <img src="<?= $base ?>assets/images/logo.png" alt="Casadive Villa" class="nav-logo">
      </a>
      <nav class="nav-links" id="navLinks" aria-label="Primary">
        <button type="button" class="nav-close" id="navClose" aria-label="Close menu">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
        <a href="<?= $base ?>index.php"<?= $active === 'home' ? ' aria-current="page"' : '' ?>>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
          Home
        </a>
        <a href="<?= $base ?>customer/villa.php"<?= $active === 'villa' ? ' aria-current="page"' : '' ?>>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 21V10l8-6 8 6v11"></path><rect x="9.5" y="14" width="5" height="7"></rect></svg>
          Villa
        </a>
        <a href="<?= $base ?>customer/campsite.php"<?= $active === 'campsite' ? ' aria-current="page"' : '' ?>>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 3 20h18L12 3z"></path><path d="M8.5 20 12 11l3.5 9"></path></svg>
          Campsite
        </a>
        <a href="<?= $base ?>customer/gallery.php"<?= $active === 'gallery' ? ' aria-current="page"' : '' ?>>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
          Gallery
        </a>
        <a href="<?= $base ?>customer/mybooking.php"<?= $active === 'mybooking' ? ' aria-current="page"' : '' ?>>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
          MyBooking
        </a>
        <a href="<?= $base ?>customer/contact_us.php"<?= $active === 'contact' ? ' aria-current="page"' : '' ?>>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
          Contact Us
        </a>
      </nav>
      <?php # check kalau ada user yang login, papar link Dashboard, kalau tak papar link Sign In ?>
      <?php if ($loggedInUser): ?>
        <a href="<?= $base ?>user/<?= $loggedInUser['role'] === 'manager' ? 'admin_dashboard.php' : 'staff_dashboard.php' ?>" class="btn btn-primary">Dashboard</a>
      <?php else: ?>
        <a href="<?= $base ?>user/login.php" class="btn btn-primary">Sign In</a>
      <?php endif; ?>
      <button type="button" class="nav-toggle" id="navToggle" aria-label="Toggle menu" aria-expanded="false" aria-controls="navLinks">
        <span class="nav-toggle-bars"><span></span><span></span><span></span></span>
      </button>
    </div>
  </header>
  <div class="nav-backdrop" id="navBackdrop" hidden></div>
  <script src="<?= htmlspecialchars($navJsHref) ?>" defer></script>
