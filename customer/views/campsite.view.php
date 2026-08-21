<?php include __DIR__ . '/../../includes/header.php'; ?>

  <!-- HERO -->
  <section class="hero" id="top" style="background-image:linear-gradient(rgba(124,106,70,.55), rgba(124,106,70,.55)), url('../assets/images/beach-benches.jpg');background-size:cover;background-position:center;">
    <h1>Campsite Packages</h1>
    <p>Experience beach camping with pool and sea view.</p>
    <a href="#packages" class="scroll-down" aria-label="Scroll down"><span></span></a>
  </section>

  <!-- PACKAGES -->
  <section class="packages" id="packages">
    <div class="container package-grid">

      <?php foreach ($campsites as $campsite):
        // sama macam villa.php — takde column khas untuk "ada pool"/"ada tent" dalam DB,
        // so kita cari perkataan tu dalam features/description je untuk decide icon
        $searchText = strtolower(($campsite['features'] ?? '') . ' ' . ($campsite['description'] ?? ''));
        $hasPool = strpos($searchText, 'pool') !== false;
        $hasTent = strpos($searchText, 'tent') !== false;
        $siteLabel = ($hasPool || $hasTent) ? 'Site' : 'Site Only';
        // kalau admin dah upload gambar untuk pakej ni, guna sebagai background kad
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

<?php include __DIR__ . '/../../includes/footer.php'; ?>
