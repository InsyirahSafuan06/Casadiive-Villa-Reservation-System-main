<?php include __DIR__ . '/../includes/header.php'; ?>

  <section class="hero" id="home" style="background-image:linear-gradient(0deg, rgba(0,0,0,.35), rgba(0,0,0,.15)), url('assets/images/beach-villa-bright.jpg');background-size:cover;background-position:center;">
    <div class="container">
      <p class="hero-eyebrow">Casadive Villa</p>
      <h1 class="hero-title">Spend your Dream Holidays with us</h1>
      <p class="hero-sub">Every moment feels like the first time in Casadive Villa</p>
      <button class="play-btn" type="button" id="hero-video-btn" aria-label="Watch video">
        <span class="play-circle">
          <svg viewBox="0 0 12 17" xmlns="http://www.w3.org/2000/svg"><path d="M0 0 L12 8.5 L0 17 Z" fill="#fff"/></svg>
        </span>
        <span>Watch Video</span>
      </button>
    </div>
  </section>

  <div class="video-lightbox" id="hero-video-lightbox">
    <div class="video-lightbox-inner">
      <button type="button" class="video-lightbox-close" id="hero-video-close" aria-label="Close video">&times;</button>
      <video id="hero-video-player" controls playsinline>
        <source src="assets/videos/hero-tour.mp4" type="video/mp4">
        Your browser does not support the video tag.
      </video>
    </div>
  </div>
  <script>
    (function () {
      var btn = document.getElementById('hero-video-btn');
      var lightbox = document.getElementById('hero-video-lightbox');
      var closeBtn = document.getElementById('hero-video-close');
      var video = document.getElementById('hero-video-player');
      if (!btn || !lightbox || !video) return;

      function openLightbox() {
        lightbox.classList.add('is-open');
        video.play();
      }
      function closeLightbox() {
        lightbox.classList.remove('is-open');
        video.pause();
        video.currentTime = 0;
      }

      btn.addEventListener('click', openLightbox);
      closeBtn.addEventListener('click', closeLightbox);
      lightbox.addEventListener('click', function (e) {
        if (e.target === lightbox) closeLightbox();
      });
    })();
  </script>

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

  <section class="testimonials">
    <div class="container">
      <div class="testimonials-head">
        <h2>What Our<br>Guests Say</h2>
        <svg class="testimonials-quote-mark" width="72" height="54" viewBox="0 0 72 54" fill="none" aria-hidden="true">
          <path d="M0 54V32.4C0 14.4 10.8 2.4 28.8 0L31.2 8.4C20.4 10.8 14.4 18 14.4 27.6H28.8V54H0Z" fill="currentColor"/>
          <path d="M39.6 54V32.4C39.6 14.4 50.4 2.4 68.4 0L70.8 8.4C60 10.8 54 18 54 27.6H68.4V54H39.6Z" fill="currentColor"/>
        </svg>
      </div>

      <?php if ($reviews): ?>
      <div class="testimonial-track">
        <?php foreach ($reviews as $review):
          $displayName = $review['display_name'] !== null && $review['display_name'] !== '' ? $review['display_name'] : 'Anonymous';
        ?>
        <article class="testimonial-card">
          <div class="testimonial-photo-frame">
            <?php if ($review['image_path']): ?>
              <img class="testimonial-photo" src="<?= htmlspecialchars($review['image_path']) ?>" alt="Room photo shared by <?= htmlspecialchars($displayName) ?>">
            <?php else: ?>
              <div class="testimonial-photo-placeholder" aria-hidden="true">&#8220;</div>
            <?php endif; ?>
          </div>

          <p class="testimonial-quote">&#8220;<?= htmlspecialchars($review['comment'] ?: 'Great stay!') ?>&#8221;</p>

          <div class="testimonial-footer">
            <div class="testimonial-person-info">
              <span class="testimonial-person-name"><?= htmlspecialchars($displayName) ?></span>
              <?php if ($review['location']): ?>
                <span class="testimonial-person-location"><?= htmlspecialchars($review['location']) ?></span>
              <?php endif; ?>
            </div>
            <div class="stars" data-rating="<?= (int) $review['rating'] ?>" aria-label="<?= (int) $review['rating'] ?> out of 5 stars">
              <?php for ($i = 1; $i <= 5; $i++): ?>
                <span class="star">&#9733;</span>
              <?php endfor; ?>
            </div>
          </div>
          <p class="testimonial-date"><?= htmlspecialchars(date('j M. Y', strtotime($review['review_date']))) ?></p>
        </article>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <p class="no-reviews">No reviews yet — be the first to stay with us and share your experience!</p>
      <?php endif; ?>
    </div>
  </section>

  <div class="photo-lightbox" id="photo-lightbox">
    <button type="button" class="photo-lightbox-close" id="photo-lightbox-close" aria-label="Close">&times;</button>
    <img id="photo-lightbox-img" src="" alt="Room photo, enlarged">
    <button type="button" class="photo-lightbox-back" id="photo-lightbox-back">Back to Homepage</button>
  </div>

  <div class="chatbot-widget">
    <button type="button" class="chatbot-launcher" id="chatbot-launcher" aria-label="Open AI Assistant" aria-expanded="false">
      <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="4" width="14" height="10" rx="3" fill="#fff"/><path d="M5 14 L5 17.5 L8.5 14 Z" fill="#fff"/><rect x="8" y="9" width="14" height="9" rx="3" fill="#fff"/><path d="M18 18 L18 21.5 L14 18 Z" fill="#fff"/></svg>
    </button>

    <div class="chatbot-panel" id="chatbot-panel">
      <div class="chatbot-header">
        <span class="chatbot-header-avatar" aria-hidden="true">
          <svg viewBox="0 0 24 24"><path d="M12 2 13.8 8.2 20 10 13.8 11.8 12 18 10.2 11.8 4 10 10.2 8.2 12 2Z" fill="currentColor"/><path d="M19 14 19.9 17.1 23 18 19.9 18.9 19 22 18.1 18.9 15 18 18.1 17.1 19 14Z" fill="currentColor"/></svg>
        </span>
        <span class="chatbot-header-text">
          <strong>Casadive Villa Assistant</strong>
          <small>Ask me anything about your stay</small>
        </span>
        <button type="button" class="chatbot-close" id="chatbot-close" aria-label="Close chat">&times;</button>
      </div>
      <div class="chatbot-body" id="chatbot-body">
      <div class="chatbot-log" id="chatbot-log" aria-live="polite"></div>
      <div class="chatbot-listening" id="chatbot-listening" hidden>
        <span class="chatbot-listening-orb"></span>
        <span class="chatbot-listening-label">Listening&hellip;</span>
        <button type="button" class="chatbot-listening-stop" id="chatbot-listening-stop" aria-label="Stop listening">&times;</button>
      </div>
      <div class="chatbot-quickreplies" id="chatbot-quickreplies">
        <button type="button" class="chip" data-action="check_availability">
          <span class="chip-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10" cy="10" r="6" fill="none" stroke="#fff" stroke-width="2"/><line x1="14.5" y1="14.5" x2="20" y2="20" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg></span>
          <span class="chip-label">Check Availability</span>
        </button>
        <button type="button" class="chip" data-action="recommend">
          <span class="chip-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 11 12 4 20 11" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M6 10v10h12V10" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
          <span class="chip-label">Recommend a Villa</span>
        </button>
        <button type="button" class="chip" data-action="facilities">
          <span class="chip-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5" fill="none" stroke="#fff" stroke-width="2"/><rect x="14" y="3" width="7" height="7" rx="1.5" fill="none" stroke="#fff" stroke-width="2"/><rect x="3" y="14" width="7" height="7" rx="1.5" fill="none" stroke="#fff" stroke-width="2"/><rect x="14" y="14" width="7" height="7" rx="1.5" fill="none" stroke="#fff" stroke-width="2"/></svg></span>
          <span class="chip-label">Villa Facilities</span>
        </button>
        <button type="button" class="chip" data-action="booking">
          <span class="chip-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2" fill="none" stroke="#fff" stroke-width="2"/><line x1="3" y1="10" x2="21" y2="10" stroke="#fff" stroke-width="2"/><line x1="8" y1="3" x2="8" y2="7" stroke="#fff" stroke-width="2" stroke-linecap="round"/><line x1="16" y1="3" x2="16" y2="7" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg></span>
          <span class="chip-label">Booking</span>
        </button>
        <button type="button" class="chip" data-action="payment">
          <span class="chip-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="6" width="20" height="12" rx="2" fill="none" stroke="#fff" stroke-width="2"/><line x1="2" y1="10" x2="22" y2="10" stroke="#fff" stroke-width="2"/></svg></span>
          <span class="chip-label">Payment</span>
        </button>
        <button type="button" class="chip" data-action="checkinout">
          <span class="chip-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3" fill="none" stroke="#fff" stroke-width="2"/><path d="M6 20c0-5 3-7 6-7s6 2 6 7" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg></span>
          <span class="chip-label">Check-in / Check-out</span>
        </button>
        <button type="button" class="chip" data-action="rules">
          <span class="chip-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="3" width="14" height="18" rx="1.5" fill="none" stroke="#fff" stroke-width="2"/><line x1="8" y1="8" x2="16" y2="8" stroke="#fff" stroke-width="2" stroke-linecap="round"/><line x1="8" y1="12" x2="16" y2="12" stroke="#fff" stroke-width="2" stroke-linecap="round"/><line x1="8" y1="16" x2="13" y2="16" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg></span>
          <span class="chip-label">House Rules</span>
        </button>
        <button type="button" class="chip" data-action="contact">
          <span class="chip-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3" fill="none" stroke="#fff" stroke-width="2"/><path d="M6 20c0-5 3-7 6-7s6 2 6 7" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg></span>
          <span class="chip-label">Contact Staff</span>
        </button>
      </div>
      </div>
      <form class="chatbot-input-row" id="chatbot-form">
        <div class="chatbot-input-wrap">
          <svg class="chatbot-input-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16v12H8l-4 4V4Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <input type="text" id="chatbot-input" placeholder="Ask a question..." autocomplete="off" aria-label="Type your question">
        </div>
        <button type="button" class="chatbot-mic-lang" id="chatbot-mic-lang" aria-label="Voice input language" title="Voice input language" hidden>EN<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9 12 15 18 9" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
        <button type="button" class="chatbot-mic-btn" id="chatbot-mic" aria-label="Speak your question" hidden>
          <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M12 14a3 3 0 0 0 3-3V6a3 3 0 0 0-6 0v5a3 3 0 0 0 3 3Z" fill="currentColor"/><path d="M19 11a1 1 0 1 0-2 0 5 5 0 0 1-10 0 1 1 0 1 0-2 0 7 7 0 0 0 6 6.93V20H9a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2h-2v-2.07A7 7 0 0 0 19 11Z" fill="currentColor"/></svg>
        </button>
        <button type="submit" class="chatbot-send-btn"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 11 21 3 13 21 11 13 3 11Z" fill="currentColor"/></svg><span>Send</span></button>
      </form>
    </div>
  </div>

<?php
$indexJsPath = dirname(__DIR__) . '/assets/js/index.js';
$indexJsVer = is_file($indexJsPath) ? '?v=' . filemtime($indexJsPath) : '';
$chatbotJsPath = dirname(__DIR__) . '/assets/js/chatbot.js';
$chatbotJsVer = is_file($chatbotJsPath) ? '?v=' . filemtime($chatbotJsPath) : '';
?>
<script src="assets/js/index.js<?= $indexJsVer ?>"></script>

<script>
  var CHATBOT_DATA = <?= json_encode($chatbotData) ?>;
</script>
<script src="assets/js/chatbot.js<?= $chatbotJsVer ?>"></script>

<?php
$showWhatsapp = true;
include __DIR__ . '/../includes/footer.php';
