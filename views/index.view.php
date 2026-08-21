<?php include __DIR__ . '/../includes/header.php'; // papar navbar + head html ?>

  <!-- HERO -->
  <section class="hero" id="home" style="background-image:linear-gradient(0deg, rgba(0,0,0,.35), rgba(0,0,0,.15)), url('assets/images/beach-villa-bright.jpg');background-size:cover;background-position:center;">
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
      <form class="booking-bar" method="get" action="customer/villa.php" id="quick-booking-form">
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
        <div class="photo-a" style="background-image:url('assets/images/beach-casa3.jpg');background-size:cover;background-position:center;"></div>
        <div class="photo-b" style="background-image:url('assets/images/bedroom-modern-fan.jpg');background-size:cover;background-position:center;"></div>
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
          <div class="stars" data-rating="<?= (int) $review['rating'] ?>" aria-label="<?= (int) $review['rating'] ?> out of 5 stars">
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <span class="star">&#9733;</span>
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

  <!-- AI ASSISTANT (scripted, rule-based — answers pulled from real accommodation data) -->
  <div class="chatbot-widget">
    <button type="button" class="chatbot-launcher" id="chatbot-launcher" aria-label="Open AI Assistant" aria-expanded="false">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16v12H7l-3 3V4z" fill="none" stroke="#fff" stroke-width="1.8" stroke-linejoin="round"/><circle cx="9" cy="10" r="1" fill="#fff"/><circle cx="12" cy="10" r="1" fill="#fff"/><circle cx="15" cy="10" r="1" fill="#fff"/></svg>
      <span>Casadive Villa Assistant</span>
    </button>

    <div class="chatbot-panel" id="chatbot-panel">
      <div class="chatbot-header">
        <span>Casadive Villa Assistant</span>
        <button type="button" class="chatbot-close" id="chatbot-close" aria-label="Close chat">&times;</button>
      </div>
      <div class="chatbot-log" id="chatbot-log" aria-live="polite"></div>
      <div class="chatbot-quickreplies" id="chatbot-quickreplies">
        <button type="button" class="chip" data-action="check_availability">Check Availability</button>
        <button type="button" class="chip" data-action="recommend">Recommend a Villa</button>
        <button type="button" class="chip" data-action="facilities">Villa Facilities</button>
        <button type="button" class="chip" data-action="booking">Booking</button>
        <button type="button" class="chip" data-action="payment">Payment</button>
        <button type="button" class="chip" data-action="checkinout">Check-in / Check-out</button>
        <button type="button" class="chip" data-action="rules">House Rules</button>
        <button type="button" class="chip" data-action="contact">Contact Staff</button>
      </div>
      <form class="chatbot-input-row" id="chatbot-form">
        <input type="text" id="chatbot-input" placeholder="Ask a question..." autocomplete="off" aria-label="Type your question">
        <button type="submit">Send</button>
      </form>
    </div>
  </div>

<script src="assets/js/index.js"></script>

<script>
  // Data sebenar dari DB (dibina di index.php) diletak sebagai global sebelum chatbot.js
  // dimuatkan — fail .js luar takleh proses <?php ?>, so jambatan kecil ni perlu kekal inline.
  var CHATBOT_DATA = <?= json_encode($chatbotData) ?>;
</script>
<script src="assets/js/chatbot.js"></script>

<?php
$showWhatsapp = true; // homepage je yang ada butang WhatsApp terapung
include __DIR__ . '/../includes/footer.php';
