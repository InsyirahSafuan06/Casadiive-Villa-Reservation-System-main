<?php include __DIR__ . '/../../includes/header.php'; ?>

  <section class="hero" id="top" style="background-image:linear-gradient(rgba(124,106,70,.55), rgba(124,106,70,.55)), url('../assets/images/beach-benches.jpg');background-size:cover;background-position:center;">
    <h1>Campsite Packages</h1>
    <p>Experience beach camping with pool and sea view.</p>
    <a href="#packages" class="scroll-down" aria-label="Scroll down"><span></span></a>
  </section>

  <section class="packages" id="packages">
    <div class="container">

      <?php # check kalau user buat search (ada query guests/dates) ke tak ?>
      <?php if ($searchActive): ?>
        <?php # check kalau search tu ada error ke, kalau ada papar mesej error ?>
        <?php if ($searchError): ?>
          <p class="search-notice search-notice-error"><?= htmlspecialchars($searchError) ?></p>
        <?php else: ?>
          <p class="search-notice search-notice-ok">
            Showing <?= count($campsites) ?> campsite package<?= count($campsites) === 1 ? '' : 's' ?> available
            for <?= (int) ($_GET['guests'] ?? 0) ?> guest<?= (int) ($_GET['guests'] ?? 0) === 1 ? '' : 's' ?>
            <?php # check kalau check-in & check-out date ada isi baru papar tarikh dekat mesej ?>
            <?php if ($searchCheckIn !== '' && $searchCheckOut !== ''): ?>
              from <?= htmlspecialchars($searchCheckIn) ?> to <?= htmlspecialchars($searchCheckOut) ?>
            <?php endif; ?>.
          </p>
        <?php endif; ?>
      <?php endif; ?>

      <?php
      # check kalau ada search aktif, calling function urlencode() untuk bina balik query string supaya boleh sambung ke link book now
      $searchQuery = $searchActive
          ? '&check_in=' . urlencode($searchCheckIn) . '&check_out=' . urlencode($searchCheckOut) . '&guests=' . urlencode((string) ($_GET['guests'] ?? ''))
          : '';
      ?>

      <div class="package-grid">
      <?php # loop setiap campsite dalam $campsites untuk papar card satu-satu
      foreach ($campsites as $campsite):
        # calling function strtolower() that assign to variable name $searchText untuk gabung features & description jadi huruf kecil, senang nak carik keyword
        $searchText = strtolower(($campsite['features'] ?? '') . ' ' . ($campsite['description'] ?? ''));
        # calling function strpos() that assign to variable name $hasPool untuk check ada perkataan 'pool' dalam $searchText ke tak
        $hasPool = strpos($searchText, 'pool') !== false;
        # calling function strpos() that assign to variable name $hasTent untuk check ada perkataan 'tent' dalam $searchText ke tak
        $hasTent = strpos($searchText, 'tent') !== false;
        # check kalau ada pool atau tent punya feature, label site jadi 'Site', kalau tak 'Site Only'
        $siteLabel = ($hasPool || $hasTent) ? 'Site' : 'Site Only';
        # check kalau campsite ni ada image, calling function htmlspecialchars() untuk bina inline style background image, kalau takde style kosong je
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
            <?php # check $hasPool punya flag untuk papar icon & label pool ?>
            <?php if ($hasPool): ?>
              <div class="feature"><?= $icons['pool'] ?><span>Pool</span></div>
            <?php endif; ?>
            <?php # check $hasTent punya flag untuk papar icon & label tent ?>
            <?php if ($hasTent): ?>
              <div class="feature"><?= $icons['tent'] ?><span>Rent a Small Tent</span></div>
            <?php endif; ?>
          </div>
          <hr class="package-divider">
          <div class="package-footer">
            <span class="package-price">RM <?= number_format((float) $campsite['price'], 0) ?></span>
            <div class="package-actions">
              <a href="detail.php?id=<?= (int) $campsite['accommodation_id'] ?>" class="btn view-detail">View Detail</a>
              <?php # calling function urlencode() untuk bina link book now dgn nama accommodation + search query yg dah disimpan tadi ?>
              <a href="bookingform.php?accommodation=<?= urlencode($campsite['accommodation_name']) . $searchQuery ?>" class="btn book-now">Book now</a>
            </div>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
      </div>

      <?php # check kalau takde campsite langsung & bukan sbb search, papar mesej takde package ?>
      <?php if (!$campsites && !$searchActive): ?>
        <p class="search-notice">No campsite packages are available right now. Please check back soon.</p>
      <?php endif; ?>

    </div>
  </section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
