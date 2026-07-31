<?php
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

      <!-- Package 1 -->
      <article class="package-card">
        <div class="package-media">
          <div class="package-badges">
            <span class="badge unit">1 Unit Only</span>
          </div>
        </div>
        <div class="package-body">
          <h3 class="package-name">Package 1</h3>
          <div class="package-features">
            <div class="feature">
              <svg viewBox="0 0 32 32"><path d="M6 14a5 5 0 0 1 10 0v2H6z"/><rect x="4" y="16" width="24" height="10" rx="2" fill="none" stroke="currentColor" stroke-width="2"/></svg>
              <span>Site Only</span>
            </div>
          </div>
          <hr class="package-divider">
          <div class="package-footer">
            <span class="package-price">RM 50</span>
            <div class="package-actions">
              <a href="accommodation_detail.php?id=5" class="btn view-detail">View Detail</a>
              <a href="bookingform.php?accommodation=Campsite%20Package%201" class="btn book-now">Book now</a>
            </div>
          </div>
        </div>
      </article>

      <!-- Package 2 -->
      <article class="package-card">
        <div class="package-media"></div>
        <div class="package-body">
          <h3 class="package-name">Package 2</h3>
          <div class="package-features">
            <div class="feature">
              <svg viewBox="0 0 32 32"><path d="M6 14a5 5 0 0 1 10 0v2H6z"/><rect x="4" y="16" width="24" height="10" rx="2" fill="none" stroke="currentColor" stroke-width="2"/></svg>
              <span>Site</span>
            </div>
            <div class="feature">
              <svg viewBox="0 0 32 32"><path d="M4 24c2.5 0 2.5-3 5-3s2.5 3 5 3 2.5-3 5-3 2.5 3 5 3 2.5-3 5-3v4H4z"/><circle cx="15" cy="10" r="4.5"/></svg>
              <span>Pool</span>
            </div>
            <div class="feature">
              <svg viewBox="0 0 32 32"><path d="M16 6 4 26h24z" fill="none" stroke="currentColor" stroke-width="2"/><path d="M16 6v20" stroke="currentColor" stroke-width="2"/></svg>
              <span>Rent a Small Tent</span>
            </div>
          </div>
          <hr class="package-divider">
          <div class="package-footer">
            <span class="package-price">RM 80</span>
            <div class="package-actions">
              <a href="accommodation_detail.php?id=6" class="btn view-detail">View Detail</a>
              <a href="bookingform.php?accommodation=Campsite%20Package%202" class="btn book-now">Book now</a>
            </div>
          </div>
        </div>
      </article>

      <!-- Package 3 -->
      <article class="package-card">
        <div class="package-media">
          <div class="package-badges">
            <span class="badge">MAX 2 PAX</span>
            <span class="badge unit">1 Unit Only</span>
          </div>
        </div>
        <div class="package-body">
          <h3 class="package-name">Package 3</h3>
          <div class="package-features">
            <div class="feature">
              <svg viewBox="0 0 32 32"><path d="M6 14a5 5 0 0 1 10 0v2H6z"/><rect x="4" y="16" width="24" height="10" rx="2" fill="none" stroke="currentColor" stroke-width="2"/></svg>
              <span>Site Only</span>
            </div>
          </div>
          <hr class="package-divider">
          <div class="package-footer">
            <span class="package-price">RM 110</span>
            <div class="package-actions">
              <a href="accommodation_detail.php?id=7" class="btn view-detail">View Detail</a>
              <a href="bookingform.php?accommodation=Campsite%20Package%203" class="btn book-now">Book now</a>
            </div>
          </div>
        </div>
      </article>

      <!-- Package 4 -->
      <article class="package-card">
        <div class="package-media">
          <div class="package-badges">
            <span class="badge">MAX 6 PAX</span>
            <span class="badge unit">1 Unit Only</span>
          </div>
        </div>
        <div class="package-body">
          <h3 class="package-name">Package 4</h3>
          <div class="package-features">
            <div class="feature">
              <svg viewBox="0 0 32 32"><path d="M6 14a5 5 0 0 1 10 0v2H6z"/><rect x="4" y="16" width="24" height="10" rx="2" fill="none" stroke="currentColor" stroke-width="2"/></svg>
              <span>Site</span>
            </div>
            <div class="feature">
              <svg viewBox="0 0 32 32"><path d="M4 24c2.5 0 2.5-3 5-3s2.5 3 5 3 2.5-3 5-3 2.5 3 5 3 2.5-3 5-3v4H4z"/><circle cx="15" cy="10" r="4.5"/></svg>
              <span>Pool</span>
            </div>
            <div class="feature">
              <svg viewBox="0 0 32 32"><path d="M16 6 4 26h24z" fill="none" stroke="currentColor" stroke-width="2"/><path d="M16 6v20" stroke="currentColor" stroke-width="2"/></svg>
              <span>Rent a Small Tent</span>
            </div>
          </div>
          <hr class="package-divider">
          <div class="package-footer">
            <span class="package-price">RM 130</span>
            <div class="package-actions">
              <a href="accommodation_detail.php?id=8" class="btn view-detail">View Detail</a>
              <a href="bookingform.php?accommodation=Campsite%20Package%204" class="btn book-now">Book now</a>
            </div>
          </div>
        </div>
      </article>

    </div>
  </section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
