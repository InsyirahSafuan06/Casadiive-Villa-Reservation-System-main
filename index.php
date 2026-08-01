<?php
require_once __DIR__ . '/includes/db.php';

$reviews = $pdo->query(
    "SELECT r.rating, r.comment, r.review_date, c.full_name
     FROM review r
     JOIN booking b ON b.booking_id = r.booking_id
     JOIN customer c ON c.customer_id = b.customer_id
     ORDER BY r.review_date DESC
     LIMIT 6"
)->fetchAll();

$base = '';
$active = 'home';
$pageTitle = 'Casadive Villa — Spend your Dream Holidays with us';
$pageCss = 'style/index.css';
include __DIR__ . '/includes/header.php';
?>

  <!-- HERO -->
  <section class="hero" id="home">
    <div class="container">
      <p class="hero-eyebrow">Casadive Villa</p>
      <h1 class="hero-title">Spend your Dream Holidays with us</h1>
      <p class="hero-sub">Every moment feels like the first time in Casadive Villa</p>
      <button class="play-btn" type="button" aria-label="Watch video">
        <span class="play-circle">
          <svg viewBox="0 0 12 17" xmlns="http://www.w3.org/2000/svg"><path d="M0 0 L12 8.5 L0 17 Z" fill="#fff"/></svg>
        </span>
        <span>Watch Video</span>
      </button>
    </div>
  </section>

  <!-- QUICK BOOKING BAR -->
  <div class="booking-wrap">
    <div class="container">
      <form class="booking-bar" method="get" action="customer/bookingform.php">
        <div class="booking-field">
          <svg viewBox="0 0 20 23" xmlns="http://www.w3.org/2000/svg"><rect x="1" y="3" width="18" height="19" rx="2" fill="none" stroke="#1C1C1C" stroke-width="1.6"/><line x1="1" y1="9" x2="19" y2="9" stroke="#1C1C1C" stroke-width="1.6"/><line x1="5" y1="1" x2="5" y2="5" stroke="#1C1C1C" stroke-width="1.6"/><line x1="15" y1="1" x2="15" y2="5" stroke="#1C1C1C" stroke-width="1.6"/></svg>
          <span>
            <label class="field-label" for="qb-checkin">Check in</label>
            <input class="field-value" type="date" id="qb-checkin" name="check_in" required>
          </span>
        </div>
        <div class="booking-field">
          <svg viewBox="0 0 20 23" xmlns="http://www.w3.org/2000/svg"><rect x="1" y="3" width="18" height="19" rx="2" fill="none" stroke="#1C1C1C" stroke-width="1.6"/><line x1="1" y1="9" x2="19" y2="9" stroke="#1C1C1C" stroke-width="1.6"/><line x1="5" y1="1" x2="5" y2="5" stroke="#1C1C1C" stroke-width="1.6"/><line x1="15" y1="1" x2="15" y2="5" stroke="#1C1C1C" stroke-width="1.6"/></svg>
          <span>
            <label class="field-label" for="qb-checkout">Check out</label>
            <input class="field-value" type="date" id="qb-checkout" name="check_out" required>
          </span>
        </div>
        <div class="booking-field">
          <svg viewBox="0 0 20 23" xmlns="http://www.w3.org/2000/svg"><circle cx="10" cy="6" r="4.2" fill="none" stroke="#1C1C1C" stroke-width="1.6"/><path d="M2 22c0-5 4-8 8-8s8 3 8 8" fill="none" stroke="#1C1C1C" stroke-width="1.6"/></svg>
          <span>
            <label class="field-label" for="qb-guests">Person</label>
            <input class="field-value" type="number" id="qb-guests" name="guests" min="1" value="1" required>
          </span>
        </div>
        <div class="booking-field">
          <svg viewBox="0 0 22 23" xmlns="http://www.w3.org/2000/svg"><path d="M2 12 L11 3 L20 12 L20 21 L2 21 Z" fill="none" stroke="#1C1C1C" stroke-width="1.6"/></svg>
          <span>
            <label class="field-label" for="qb-type">Type</label>
            <select class="field-value" id="qb-type" name="type">
              <option value="Villa">Villa</option>
              <option value="Campsite">Campsite</option>
            </select>
          </span>
        </div>
        <button type="submit" class="booking-cta">Check Availability</button>
      </form>
    </div>
  </div>

  <!-- WELCOME -->
  <section class="welcome" id="villa">
    <div class="container">
      <div class="welcome-photos" aria-hidden="true">
        <div class="photo-a"></div>
        <div class="photo-b"></div>
      </div>
      <div class="welcome-copy">
        <p class="welcome-script">Welcome</p>
        <h2 class="welcome-heading">to Casadive Villa</h2>
        <p class="welcome-text">
          The perfect destination for a relaxing getaway. Take a break from your daily routine and enjoy a peaceful environment with beautiful surroundings. Our villa provides a comfortable place for you and your loved ones to rest, relax, and enjoy unforgettable moments together. Whether it is a family vacation or a short escape with friends, Casadive Villa is ready to give you a memorable experience filled with comfort and happiness.
        </p>
        <a href="customer/gallery.php" class="btn btn-primary btn-round">View More</a>
      </div>
    </div>
  </section>

  <!-- FACILITIES -->
  <section class="facilities" id="facilities">
    <div class="container">
      <h2>Our Facilities</h2>
      <p class="subtitle">Everything you need for a comfortable and memorable stay</p>
      <div class="facility-grid">
        <div class="facility-card">
          <svg class="facility-icon" viewBox="0 0 48 48"><path d="M4 34c3 0 3-4 6-4s3 4 6 4 3-4 6-4 3 4 6 4 3-4 6-4 3 4 6 4v6H4v-6z"/><circle cx="18" cy="14" r="6"/><path d="M6 26h20v6H6z" opacity=".5"/></svg>
          <span>Swimming pool</span>
        </div>
        <div class="facility-card">
          <svg class="facility-icon" viewBox="0 0 48 48"><path d="M24 36a3 3 0 1 0 0 6 3 3 0 0 0 0-6zM8 20c9-9 23-9 32 0l-4 4c-7-7-17-7-24 0l-4-4zM14 26c6-6 14-6 20 0l-4 4c-4-4-8-4-12 0l-4-4zM20 32c3-3 5-3 8 0l-4 4-4-4z"/></svg>
          <span>Wifi</span>
        </div>
        <div class="facility-card">
          <svg class="facility-icon" viewBox="0 0 48 48"><rect x="8" y="6" width="32" height="36" rx="4" fill="none" stroke="currentColor" stroke-width="3"/><path d="M18 14h8a6 6 0 0 1 0 12h-8V14zm0 12v10" fill="none" stroke="currentColor" stroke-width="3"/></svg>
          <span>Parking space</span>
        </div>
        <div class="facility-card">
          <svg class="facility-icon" viewBox="0 0 48 48"><circle cx="24" cy="24" r="16" fill="none" stroke="currentColor" stroke-width="3"/><path d="M14 24h20M24 14v20" stroke="currentColor" stroke-width="3"/></svg>
          <span>Bbq set</span>
        </div>
        <div class="facility-card">
          <svg class="facility-icon" viewBox="0 0 48 48"><path d="M6 40c6-4 10-4 16 0s10 4 16 0" fill="none" stroke="currentColor" stroke-width="3"/><path d="M24 6c4 8 4 16 0 24" fill="none" stroke="currentColor" stroke-width="3"/><circle cx="24" cy="6" r="2.5"/></svg>
          <span>Beach</span>
        </div>
      </div>
    </div>
  </section>

  <!-- TESTIMONIALS -->
  <section class="testimonials">
    <div class="container">
      <h2>What Our Guests Say</h2>

      <?php if ($reviews): ?>
      <div class="testimonial-track">
        <?php foreach ($reviews as $review): ?>
        <article class="testimonial-card">
          <div class="stars" aria-label="<?= (int) $review['rating'] ?> out of 5 stars">
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <svg viewBox="0 0 20 19" class="<?= $i <= (int) $review['rating'] ? '' : 'star-empty' ?>"><polygon points="10,0 12.5,7 20,7 14,11.5 16,19 10,14.5 4,19 6,11.5 0,7 7.5,7"/></svg>
            <?php endfor; ?>
          </div>
          <p class="testimonial-date"><?= htmlspecialchars(date('j M. Y', strtotime($review['review_date']))) ?></p>
          <p class="testimonial-quote"><?= htmlspecialchars($review['comment'] ?: 'Great stay!') ?></p>
          <div class="testimonial-person">
            <div class="avatar"></div>
            <span><?= htmlspecialchars($review['full_name']) ?></span>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <p class="no-reviews">No reviews yet — be the first to stay with us and share your experience!</p>
      <?php endif; ?>
    </div>
  </section>

<?php
$showWhatsapp = true;
include __DIR__ . '/includes/footer.php';
