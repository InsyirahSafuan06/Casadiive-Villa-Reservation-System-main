<?php include __DIR__ . '/../../includes/header.php'; ?>

  <section class="hero" id="top" style="background-image:linear-gradient(rgba(124,106,70,.55), rgba(124,106,70,.55)), url('../assets/images/casa2-casa3-day.jpg');background-size:cover;background-position:center;">
    <h1>Villa package</h1>
    <p>Relax with pool and sea view.</p>
    <a href="#packages" class="scroll-down" aria-label="Scroll down"><span></span></a>
  </section>
  <section class="packages" id="packages">
    <div class="container">

      <?php # check kalau $searchActive true (user buat carian) untuk papar mesej hasil carian ?>
      <?php if ($searchActive): ?>
        <?php # check kalau ada $searchError untuk papar mesej error carian ?>
        <?php if ($searchError): ?>
          <p class="search-notice search-notice-error"><?= htmlspecialchars($searchError) ?></p>
        <?php else: ?>
          <p class="search-notice search-notice-ok">
            <?php # calling function count() untuk kira jumlah villa yang jumpa, check singular/plural 'villa'/'villas' ?>
            Showing <?= count($villas) ?> villa<?= count($villas) === 1 ? '' : 's' ?> available
            for <?= (int) ($_GET['guests'] ?? 0) ?> guest<?= (int) ($_GET['guests'] ?? 0) === 1 ? '' : 's' ?>
            <?php # check kalau $searchCheckIn dan $searchCheckOut tak kosong untuk papar tarikh carian ?>
            <?php if ($searchCheckIn !== '' && $searchCheckOut !== ''): ?>
              from <?= htmlspecialchars($searchCheckIn) ?> to <?= htmlspecialchars($searchCheckOut) ?>
            <?php endif; ?>.
          </p>
        <?php endif; ?>
      <?php endif; ?>

      <?php
      # check kalau $searchActive true that assign to $searchQuery untuk bina balik query string carian, guna urlencode() elak simbol rosak url
      $searchQuery = $searchActive
          ? '&check_in=' . urlencode($searchCheckIn) . '&check_out=' . urlencode($searchCheckOut) . '&guests=' . urlencode((string) ($_GET['guests'] ?? ''))
          : '';
      ?>

      <div class="package-grid">
      <?php # loop setiap $villa dalam $villas untuk papar satu card bagi setiap villa
      foreach ($villas as $villa):
        # calling function strtolower() untuk gabung features & description jadi satu text huruf kecil, senang nak search keyword
        $searchText = strtolower(($villa['features'] ?? '') . ' ' . ($villa['description'] ?? ''));
        # calling function strpos() untuk check ada perkataan 'pool' dalam $searchText ke tak
        $hasPool = strpos($searchText, 'pool') !== false;
        # calling function strpos() untuk check ada perkataan 'wifi' dalam $searchText ke tak
        $hasWifi = strpos($searchText, 'wifi') !== false;
        # check kalau ada pool atau wifi untuk tentukan label 'Room' ke 'Room Only'
        $roomLabel = ($hasPool || $hasWifi) ? 'Room' : 'Room Only';
        # check pax_label wujud, kalau tak bina label default guna capacity villa
        $paxLabel = $villa['pax_label'] ?: ('Max ' . (int) $villa['capacity'] . ' guests');
        # check kalau villa ada image untuk bina inline style background, kalau tak kosongkan style
        $mediaStyle = $villa['image'] ? ' style="background-image:url(\'' . htmlspecialchars($villa['image']) . '\');background-size:cover;background-position:center;"' : '';
        $unitFeatures = array_slice(array_filter(array_map('trim', explode("\n", $villa['features'] ?? ''))), 0, 3);
        $villaRatePeriods = $ratePeriodsByAccommodation[(int) $villa['accommodation_id']] ?? [];
        $villaRate = accommodation_rate_for_date($villa, $rateDate, $villaRatePeriods);
      ?>
      <article class="package-card">
        <div class="package-media"<?= $mediaStyle ?>>
          <div class="package-badges">
            <?php # calling function strtoupper() & htmlspecialchars() untuk papar $paxLabel dalam huruf besar ?>
            <span class="badge"><?= htmlspecialchars(strtoupper($paxLabel)) ?></span>
          </div>
        </div>
        <div class="package-body">
          <h3 class="package-name"><?= htmlspecialchars($villa['accommodation_name']) ?></h3>
          <div class="package-features">
            <div class="feature"><?= $icons['room'] ?><span><?= $roomLabel ?></span></div>
            <?php # check kalau $hasPool true untuk papar icon & label Pool ?>
            <?php if ($hasPool): ?>
              <div class="feature"><?= $icons['pool'] ?><span>Pool</span></div>
            <?php endif; ?>
            <?php # check kalau $hasWifi true untuk papar icon & label Wifi ?>
            <?php if ($hasWifi): ?>
              <div class="feature"><?= $icons['wifi'] ?><span>Wifi</span></div>
            <?php endif; ?>
          </div>
          <?php if ($unitFeatures): ?>
            <ul class="package-highlights">
              <?php foreach ($unitFeatures as $unitFeature): ?>
                <li><?= htmlspecialchars($unitFeature) ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
          <hr class="package-divider">
          <div class="package-footer">
            <div class="package-rates">
              <strong class="package-rates-heading">Rate for <?= htmlspecialchars($rateDate->format('D, d M Y')) ?></strong>
              <div class="package-rate-row">
                <span><?= htmlspecialchars($villaRate['label']) ?></span>
                <strong>RM <?= number_format($villaRate['price'], 0) ?> / night</strong>
              </div>
              </div>
              <div class="package-actions">
                <a href="detail.php?id=<?= (int) $villa['accommodation_id'] ?>&amp;rate_date=<?= urlencode($rateDate->format('Y-m-d')) . $searchQuery ?>" class="btn view-detail">View Detail</a>
              <?php # calling function urlencode() untuk selamatkan nama villa dalam url, gabung dgn $searchQuery supaya carian tak hilang ?>
                <a href="bookingform.php?accommodation=<?= urlencode($villa['accommodation_name']) . $searchQuery ?>" class="btn book-now">Book now</a>
              </div>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
      </div>

      <?php # check kalau $villas kosong dan bukan sedang search untuk papar mesej takde package ?>
      <?php if (!$villas && !$searchActive): ?>
        <p class="search-notice">No villa packages are available right now. Please check back soon.</p>
      <?php endif; ?>

    </div>
  </section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
