<?php include __DIR__ . '/../../includes/header.php'; ?>

  <section class="hero" id="top" style="background-image:linear-gradient(rgba(124,106,70,.55), rgba(124,106,70,.55)), url('../assets/images/casa2-casa3-day.jpg');background-size:cover;background-position:center;">
    <h1>Villa Packages</h1>
    <p>Enjoy a peaceful stay with private rooms, pool access and a beachfront view.</p>
    <a href="#packages" class="scroll-down" aria-label="Scroll down"><span></span></a>
  </section>

  <section class="packages" id="packages">
    <div class="container">

      <?php if ($searchActive): ?>
        <?php if ($searchError): ?>
          <p class="search-notice search-notice-error"><?= htmlspecialchars($searchError) ?></p>
        <?php else: ?>
          <p class="search-notice search-notice-ok">
            Showing <?= count($villas) ?> villa<?= count($villas) === 1 ? '' : 's' ?> available
            for <?= (int) ($_GET['guests'] ?? 0) ?> guest<?= (int) ($_GET['guests'] ?? 0) === 1 ? '' : 's' ?>
            <?php if ($searchCheckIn !== '' && $searchCheckOut !== ''): ?>
              from <?= htmlspecialchars($searchCheckIn) ?> to <?= htmlspecialchars($searchCheckOut) ?>
            <?php endif; ?>.
          </p>
        <?php endif; ?>
      <?php endif; ?>

      <?php
      $searchQuery = $searchActive
          ? '&check_in=' . urlencode($searchCheckIn) . '&check_out=' . urlencode($searchCheckOut) . '&guests=' . urlencode((string) ($_GET['guests'] ?? ''))
          : '';
      ?>

      <div class="package-grid">
      <?php foreach ($villas as $villa):
        $searchText = strtolower(($villa['features'] ?? '') . ' ' . ($villa['description'] ?? ''));
        $hasPool = strpos($searchText, 'pool') !== false;
        $hasWifi = strpos($searchText, 'wifi') !== false;
        $roomLabel = ($hasPool || $hasWifi) ? 'Room' : 'Room Only';
        $paxLabel = $villa['pax_label'] ?: ('Max ' . (int) $villa['capacity'] . ' guests');
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
              <a href="bookingform.php?accommodation=<?= urlencode($villa['accommodation_name']) . $searchQuery ?>" class="btn book-now">Book now</a>
            </div>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
      </div>

      <?php if (!$villas && !$searchActive): ?>
        <p class="search-notice">No villa packages are available right now. Please check back soon.</p>
      <?php endif; ?>

    </div>
  </section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
