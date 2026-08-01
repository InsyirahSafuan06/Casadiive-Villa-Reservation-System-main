<?php
require_once __DIR__ . '/../includes/db.php';

$campsites = $pdo->query(
    "SELECT * FROM accommodation WHERE accommodation_type = 'Campsite' AND status = 'available' ORDER BY accommodation_id"
)->fetchAll();

$icons = [
    'site' => '<svg viewBox="0 0 32 32"><path d="M6 14a5 5 0 0 1 10 0v2H6z"/><rect x="4" y="16" width="24" height="10" rx="2" fill="none" stroke="currentColor" stroke-width="2"/></svg>',
    'pool' => '<svg viewBox="0 0 32 32"><path d="M4 24c2.5 0 2.5-3 5-3s2.5 3 5 3 2.5-3 5-3 2.5 3 5 3 2.5-3 5-3v4H4z"/><circle cx="15" cy="10" r="4.5"/></svg>',
    'tent' => '<svg viewBox="0 0 32 32"><path d="M16 6 4 26h24z" fill="none" stroke="currentColor" stroke-width="2"/><path d="M16 6v20" stroke="currentColor" stroke-width="2"/></svg>',
];

$base = '../';
$active = 'campsite';
$pageTitle = 'Campsite Packages — Casadive Villa';
$pageCss = 'style/campsite.css';
include __DIR__ . '/../includes/header.php';
?>

  <!-- HERO -->
  <section class="hero" id="top">
    <h1>Campsite Packages</h1>
    <p>Experience beach camping with pool and sea view.</p>
    <a href="#packages" class="scroll-down" aria-label="Scroll down"><span></span></a>
  </section>

  <!-- PACKAGES -->
  <section class="packages" id="packages">
    <div class="container package-grid">

      <?php foreach ($campsites as $campsite):
        $searchText = strtolower(($campsite['features'] ?? '') . ' ' . ($campsite['description'] ?? ''));
        $hasPool = strpos($searchText, 'pool') !== false;
        $hasTent = strpos($searchText, 'tent') !== false;
        $siteLabel = ($hasPool || $hasTent) ? 'Site' : 'Site Only';
        $mediaStyle = $campsite['image'] ? ' style="background-image:url(\'' . htmlspecialchars($campsite['image']) . '\');background-size:cover;background-position:center;"' : '';
      ?>
      <article class="package-card">
        <div class="package-media"<?= $mediaStyle ?>>
          <div class="package-badges">
            <span class="badge">MAX <?= (int) $campsite['capacity'] ?> PAX</span>
          </div>
        </div>
        <div class="package-body">
          <h3 class="package-name"><?= htmlspecialchars($campsite['accommodation_name']) ?></h3>
          <div class="package-features">
            <div class="feature"><?= $icons['site'] ?><span><?= $siteLabel ?></span></div>
            <?php if ($hasPool): ?>
              <div class="feature"><?= $icons['pool'] ?><span>Pool</span></div>
            <?php endif; ?>
            <?php if ($hasTent): ?>
              <div class="feature"><?= $icons['tent'] ?><span>Rent a Small Tent</span></div>
            <?php endif; ?>
          </div>
          <hr class="package-divider">
          <div class="package-footer">
            <span class="package-price">RM <?= number_format((float) $campsite['price'], 0) ?></span>
            <div class="package-actions">
              <a href="detail.php?id=<?= (int) $campsite['accommodation_id'] ?>" class="btn view-detail">View Detail</a>
              <a href="bookingform.php?accommodation=<?= urlencode($campsite['accommodation_name']) ?>" class="btn book-now">Book now</a>
            </div>
          </div>
        </div>
      </article>
      <?php endforeach; ?>

      <?php if (!$campsites): ?>
        <p style="font-family:'Raleway',sans-serif;font-weight:600;">No campsite packages are available right now. Please check back soon.</p>
      <?php endif; ?>

    </div>
  </section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
