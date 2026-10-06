<?php include __DIR__ . '/../../includes/header.php'; ?>

  <section class="detail-section">
    <div class="container">
      <?php # check kalau $accommodation takde (null/false) papar page not found, kalau ada papar detail ?>
      <?php if (!$accommodation): ?>

        <div class="not-found">
          <h1>Package not found</h1>
          <p>The accommodation you're looking for doesn't exist or may have been removed.</p>
          <br>
          <a href="villa.php" class="detail-book" style="display:inline-block;">Browse Packages</a>
        </div>

      <?php else:
        # check jenis accommodation, kalau Campsite balik ke campsite.php, kalau tak balik ke villa.php
        $backHref = $accommodation['accommodation_type'] === 'Campsite' ? 'campsite.php' : 'villa.php';
        # check kalau pax_label takde value, bina label default guna capacity punya angka
        $paxLabel = $accommodation['pax_label'] ?: ('Max ' . (int) $accommodation['capacity'] . ' guests');
      ?>

        <a href="<?= $backHref ?>" class="detail-back">&larr; Back to <?= htmlspecialchars($accommodation['accommodation_type']) ?> Packages</a>

        <div class="detail-card">

          <?php # check kalau accommodation ni ada image, baru papar gambar ?>
          <?php if ($accommodation['image']): ?>
            <div class="detail-media">
              <img src="<?= htmlspecialchars($accommodation['image']) ?>" alt="<?= htmlspecialchars($accommodation['accommodation_name']) ?>" loading="lazy">
            </div>
          <?php endif; ?>

          <div class="detail-head">
            <h1 class="detail-name"><?= htmlspecialchars($accommodation['accommodation_name']) ?></h1>
            <span class="detail-pax"><?= htmlspecialchars($paxLabel) ?></span>
          </div>

          <?php # check kalau description takde, guna default text, calling function nl2br() untuk tukar baris baru jadi <br> ?>
          <p class="detail-description"><?= nl2br(htmlspecialchars($accommodation['description'] ?: 'No additional description available for this package.')) ?></p>

          <hr class="detail-divider">

          <?php # check kalau $features ada isi, baru papar section room features ?>
          <?php if ($features): ?>
          <h2 class="detail-heading">Room Features</h2>
          <div class="detail-features">
            <?php # loop setiap feature dalam $features untuk papar satu-satu ?>
            <?php foreach ($features as $feature): ?>
              <div class="detail-feature">
                <svg viewBox="0 0 24 24"><path d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg>
                <span><?= htmlspecialchars($feature) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <h2 class="detail-heading">Rates (2 days, 1 night)</h2>
          <div class="rates-card">
            <div class="rates-row">
              <span class="rates-label">Weekday</span>
              <span class="rates-value">RM <?= number_format((float) $accommodation['price'], 0) ?></span>
            </div>
            <?php # check kalau ada price_weekend punya rate, baru papar row weekend ?>
            <?php if ($accommodation['price_weekend'] !== null): ?>
            <div class="rates-row">
              <span class="rates-label">Weekend</span>
              <span class="rates-value">RM <?= number_format((float) $accommodation['price_weekend'], 0) ?></span>
            </div>
            <?php endif; ?>
            <?php # check kalau ada price_holiday punya rate, baru papar row public holiday ?>
            <?php if ($accommodation['price_holiday'] !== null): ?>
            <div class="rates-row">
              <span class="rates-label">Public holiday / school holiday</span>
              <span class="rates-value">RM <?= number_format((float) $accommodation['price_holiday'], 0) ?></span>
            </div>
            <?php endif; ?>
            <?php if (($accommodation['price_seasonal'] ?? null) !== null): ?>
            <div class="rates-row">
              <span class="rates-label">Ramadan / rainy season</span>
              <span class="rates-value">RM <?= number_format((float) $accommodation['price_seasonal'], 0) ?></span>
            </div>
            <?php endif; ?>
            <?php foreach ($ratePeriods as $ratePeriod): ?>
            <div class="rates-row">
              <span class="rates-label">
                <?= htmlspecialchars($ratePeriod['label']) ?>
                <small><?= date('d M Y', strtotime($ratePeriod['start_date'])) ?> - <?= date('d M Y', strtotime($ratePeriod['end_date'])) ?></small>
              </span>
              <span class="rates-value">RM <?= number_format((float) $ratePeriod['price'], 0) ?></span>
            </div>
            <?php endforeach; ?>
          </div>

          <?php # check kalau status accommodation bukan 'available', papar mesej tak boleh book ?>
          <?php if ($accommodation['status'] !== 'available'): ?>
            <p class="detail-unavailable">This package is currently <?= htmlspecialchars($accommodation['status']) ?> and not open for booking right now. Please check back later or browse other packages.</p>
          <?php endif; ?>

          <div class="detail-actions">
            <?php # check kalau status 'available' baru papar butang Book Now ?>
            <?php if ($accommodation['status'] === 'available'): ?>
              <a href="bookingform.php?accommodation=<?= urlencode($accommodation['accommodation_name']) ?>" class="detail-book">Book Now</a>
            <?php endif; ?>
            <a href="<?= $backHref ?>" class="detail-browse">Browse More</a>
          </div>

        </div>

      <?php endif; ?>
    </div>
  </section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
