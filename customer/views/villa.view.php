<?php include __DIR__ . '/../../includes/header.php'; ?>

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
        // takde column khas "ada pool"/"ada wifi" dalam DB, so kita just cari perkataan tu
        // dalam features/description untuk decide icon mana nak tunjuk
        $searchText = strtolower(($villa['features'] ?? '') . ' ' . ($villa['description'] ?? ''));
        $hasPool = strpos($searchText, 'pool') !== false;
        $hasWifi = strpos($searchText, 'wifi') !== false;
        $roomLabel = ($hasPool || $hasWifi) ? 'Room' : 'Room Only';
        $paxLabel = $villa['pax_label'] ?: ('Max ' . (int) $villa['capacity'] . ' guests'); // guna label custom kalau ada, kalau tak generate sendiri
        // kalau admin dah upload gambar untuk vila ni, guna sebagai background kad
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

<?php include __DIR__ . '/../../includes/footer.php'; ?>
