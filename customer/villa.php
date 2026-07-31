<?php
$base = '../';
$active = 'villa';
$pageTitle = 'Villa Packages — Casadive Villa';
$pageCss = 'style/villa.css';
include __DIR__ . '/../includes/header.php';
?>

  <!-- HERO -->
  <section class="hero" id="top">
    <h1>Villa Packages</h1>
    <p>Enjoy a peaceful stay with private rooms, pool access and a beachfront view.</p>
    <a href="#packages" class="scroll-down" aria-label="Scroll down"><span></span></a>
  </section>

  <!-- PACKAGES -->
  <section class="packages" id="packages">
    <div class="container package-grid">

      <!-- Casa 1 -->
      <article class="package-card">
        <div class="package-media">
          <div class="package-badges">
            <span class="badge unit">1 Unit Only</span>
          </div>
        </div>
        <div class="package-body">
          <h3 class="package-name">Casa 1</h3>
          <div class="package-features">
            <div class="feature">
              <svg viewBox="0 0 32 32"><path d="M4 18v8h2v-3h20v3h2v-8a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4z" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="10" cy="15" r="2.5"/></svg>
              <span>Room Only</span>
            </div>
          </div>
          <hr class="package-divider">
          <div class="package-footer">
            <span class="package-price">RM 359</span>
            <div class="package-actions">
              <a href="accommodation_detail.php?id=1" class="btn view-detail">View Detail</a>
              <a href="bookingform.php?accommodation=Casa%201" class="btn book-now">Book now</a>
            </div>
          </div>
        </div>
      </article>

      <!-- Casa 2 -->
      <article class="package-card">
        <div class="package-media"></div>
        <div class="package-body">
          <h3 class="package-name">Casa 2</h3>
          <div class="package-features">
            <div class="feature">
              <svg viewBox="0 0 32 32"><path d="M4 18v8h2v-3h20v3h2v-8a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4z" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="10" cy="15" r="2.5"/></svg>
              <span>Room</span>
            </div>
            <div class="feature">
              <svg viewBox="0 0 32 32"><path d="M4 24c2.5 0 2.5-3 5-3s2.5 3 5 3 2.5-3 5-3 2.5 3 5 3 2.5-3 5-3v4H4z"/><circle cx="15" cy="10" r="4.5"/></svg>
              <span>Pool</span>
            </div>
            <div class="feature">
              <svg viewBox="0 0 32 32"><path d="M16 24a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM5.3 13.3c6-6 15.3-6 21.3 0l-2.7 2.7c-4.7-4.7-11.3-4.7-16 0l-2.7-2.7zM9.3 17.3c4-4 9.3-4 13.3 0l-2.7 2.7c-2.7-2.7-5.3-2.7-8 0l-2.7-2.7zM13.3 21.3c2-2 3.3-2 5.3 0l-2.7 2.7-2.7-2.7z"/></svg>
              <span>Wifi</span>
            </div>
          </div>
          <hr class="package-divider">
          <div class="package-footer">
            <span class="package-price">RM 239</span>
            <div class="package-actions">
              <a href="accommodation_detail.php?id=2" class="btn view-detail">View Detail</a>
              <a href="bookingform.php?accommodation=Casa%202" class="btn book-now">Book now</a>
            </div>
          </div>
        </div>
      </article>

      <!-- Casa 3 -->
      <article class="package-card">
        <div class="package-media">
          <div class="package-badges">
            <span class="badge">MAX 2 PAX</span>
            <span class="badge unit">1 Unit Only</span>
          </div>
        </div>
        <div class="package-body">
          <h3 class="package-name">Casa 3</h3>
          <div class="package-features">
            <div class="feature">
              <svg viewBox="0 0 32 32"><path d="M4 18v8h2v-3h20v3h2v-8a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4z" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="10" cy="15" r="2.5"/></svg>
              <span>Room Only</span>
            </div>
          </div>
          <hr class="package-divider">
          <div class="package-footer">
            <span class="package-price">RM 339</span>
            <div class="package-actions">
              <a href="accommodation_detail.php?id=3" class="btn view-detail">View Detail</a>
              <a href="bookingform.php?accommodation=Casa%203" class="btn book-now">Book now</a>
            </div>
          </div>
        </div>
      </article>

      <!-- Casa 4 -->
      <article class="package-card">
        <div class="package-media">
          <div class="package-badges">
            <span class="badge">MAX 6 PAX</span>
            <span class="badge unit">1 Unit Only</span>
          </div>
        </div>
        <div class="package-body">
          <h3 class="package-name">Casa 4</h3>
          <div class="package-features">
            <div class="feature">
              <svg viewBox="0 0 32 32"><path d="M4 18v8h2v-3h20v3h2v-8a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4z" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="10" cy="15" r="2.5"/></svg>
              <span>Room</span>
            </div>
            <div class="feature">
              <svg viewBox="0 0 32 32"><path d="M4 24c2.5 0 2.5-3 5-3s2.5 3 5 3 2.5-3 5-3 2.5 3 5 3 2.5-3 5-3v4H4z"/><circle cx="15" cy="10" r="4.5"/></svg>
              <span>Pool</span>
            </div>
            <div class="feature">
              <svg viewBox="0 0 32 32"><path d="M16 24a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM5.3 13.3c6-6 15.3-6 21.3 0l-2.7 2.7c-4.7-4.7-11.3-4.7-16 0l-2.7-2.7zM9.3 17.3c4-4 9.3-4 13.3 0l-2.7 2.7c-2.7-2.7-5.3-2.7-8 0l-2.7-2.7zM13.3 21.3c2-2 3.3-2 5.3 0l-2.7 2.7-2.7-2.7z"/></svg>
              <span>Wifi</span>
            </div>
          </div>
          <hr class="package-divider">
          <div class="package-footer">
            <span class="package-price">RM 359</span>
            <div class="package-actions">
              <a href="accommodation_detail.php?id=4" class="btn view-detail">View Detail</a>
              <a href="bookingform.php?accommodation=Casa%204" class="btn book-now">Book now</a>
            </div>
          </div>
        </div>
      </article>

    </div>
  </section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
