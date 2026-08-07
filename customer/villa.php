<?php
/**
 * Halaman senarai pakej vila.
 * Halaman ini memaparkan semua pakej vila yang tersedia dan pautkannya ke halaman butiran tempahan.
 */
require_once __DIR__ . '/../includes/db.php';

// Hanya papar vila yang ditanda tersedia dalam panel admin.
$villas = $pdo->query(
    "SELECT * FROM accommodation WHERE accommodation_type = 'Villa' AND status = 'available' ORDER BY accommodation_id"
)->fetchAll();

// Set ikon kecil yang digunakan pada setiap kad pakej di bawah.
$icons = [
    'room' => '<svg viewBox="0 0 32 32"><path d="M4 18v8h2v-3h20v3h2v-8a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4z" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="10" cy="15" r="2.5"/></svg>',
    'pool' => '<svg viewBox="0 0 32 32"><path d="M4 24c2.5 0 2.5-3 5-3s2.5 3 5 3 2.5-3 5-3 2.5 3 5 3 2.5-3 5-3v4H4z"/><circle cx="15" cy="10" r="4.5"/></svg>',
    'wifi' => '<svg viewBox="0 0 32 32"><path d="M16 24a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM5.3 13.3c6-6 15.3-6 21.3 0l-2.7 2.7c-4.7-4.7-11.3-4.7-16 0l-2.7-2.7zM9.3 17.3c4-4 9.3-4 13.3 0l-2.7 2.7c-2.7-2.7-5.3-2.7-8 0l-2.7-2.7zM13.3 21.3c2-2 3.3-2 5.3 0l-2.7 2.7-2.7-2.7z"/></svg>',
];

$base = '../';
$active = 'villa';
$pageTitle = 'Villa Packages — Casadive Villa';
$pageCss = 'style/villa.css';
include __DIR__ . '/../includes/header.php';
?>

  <!-- HERO -->
  <section class="hero" id="top" style="background-image:linear-gradient(rgba(124,106,70,.55), rgba(124,106,70,.55)), url('../assets/images/casa2-casa3-day.jpg');background-size:cover;background-position:center;">
    <h1>Villa Packages</h1>
    <p>Enjoy a peaceful stay with private rooms, pool access and a beachfront view.</p>
    <a href="#packages" class="scroll-down" aria-label="Scroll down"><span></span></a>
  </section>

  <!-- PACKAGES -->
  <section class="packages" id="packages">
    <div class="container package-grid">

      <?php foreach ($villas as $villa):
        // Teks features/description tiada penanda "ada pool" atau "ada wifi" berasingan dalam
        // pangkalan data, jadi kita cari sahaja perkataan tersebut untuk tentukan ikon mana nak papar.
        $searchText = strtolower(($villa['features'] ?? '') . ' ' . ($villa['description'] ?? ''));
        $hasPool = strpos($searchText, 'pool') !== false;
        $hasWifi = strpos($searchText, 'wifi') !== false;
        $roomLabel = ($hasPool || $hasWifi) ? 'Room' : 'Room Only';
        $paxLabel = $villa['pax_label'] ?: ('Max ' . (int) $villa['capacity'] . ' guests');
        // Jika admin sudah muat naik gambar untuk vila ini, guna sebagai latar belakang kad.
        $mediaStyle = $villa['image'] ? ' style="background-image:url(\'' . htmlspecialchars($villa['image']) . '\');background-size:cover;background-position:center;"' : '';
      ?>
      <article class="package-card">
        <div class="package-media"<?= $mediaStyle ?>>
          <div class="package-badges">
            <span class="badge"><?= htmlspecialchars(strtoupper($paxLabel)) ?></span>
          </div>
        </div>
        <div class="package-body">
          <h3 class="package-name"><?= htmlspecialchars($villa['accommodation_name']) ?></h3>
          <div class="package-features">
            <div class="feature"><?= $icons['room'] ?><span><?= $roomLabel ?></span></div>
            <?php if ($hasPool): ?>
              <div class="feature"><?= $icons['pool'] ?><span>Pool</span></div>
            <?php endif; ?>
            <?php if ($hasWifi): ?>
              <div class="feature"><?= $icons['wifi'] ?><span>Wifi</span></div>
            <?php endif; ?>
          </div>
          <hr class="package-divider">
          <div class="package-footer">
            <span class="package-price">RM <?= number_format((float) $villa['price'], 0) ?></span>
            <div class="package-actions">
              <a href="detail.php?id=<?= (int) $villa['accommodation_id'] ?>" class="btn view-detail">View Detail</a>
              <a href="bookingform.php?accommodation=<?= urlencode($villa['accommodation_name']) ?>" class="btn book-now">Book now</a>
            </div>
          </div>
        </div>
      </article>
      <?php endforeach; ?>

      <?php if (!$villas): ?>
        <p style="font-family:'Raleway',sans-serif;font-weight:600;">No villa packages are available right now. Please check back soon.</p>
      <?php endif; ?>

    </div>
  </section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
