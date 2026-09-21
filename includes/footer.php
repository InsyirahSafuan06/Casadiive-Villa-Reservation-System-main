<?php
/**
 * Susun atur footer yang dikongsi untuk laman web Casadive Villa.
 */
declare(strict_types=1);

$base ??= ''; 
$showWhatsapp ??= false; 

// Cache-busting untuk footer CSS + JS (review-photo.js/footer-review-init.js), supaya browser
// customer tak terus guna versi lama yang dah di-cache lepas fail-fail ni diubah
$footerCssHref = 'assets/css/footer.css';
$footerCssPath = __DIR__ . '/../assets/css/footer.css';
if (is_file($footerCssPath)) {
    $footerCssHref .= '?v=' . filemtime($footerCssPath);
}

$reviewPhotoJsHref = 'assets/js/review-photo.js';
$reviewPhotoJsPath = __DIR__ . '/../assets/js/review-photo.js';
if (is_file($reviewPhotoJsPath)) {
    $reviewPhotoJsHref .= '?v=' . filemtime($reviewPhotoJsPath);
}

$footerReviewInitJsHref = 'assets/js/footer-review-init.js';
$footerReviewInitJsPath = __DIR__ . '/../assets/js/footer-review-init.js';
if (is_file($footerReviewInitJsPath)) {
    $footerReviewInitJsHref .= '?v=' . filemtime($footerReviewInitJsPath);
}
?>
  <!-- FOOTER -->
  <footer>
    <div class="container">
      <div class="footer-grid">
        
        <!-- Kolom Info Utama -->
        <div class="footer-col footer-brand">
          <img src="<?= htmlspecialchars($base) ?>assets/images/logo-white.png" alt="Casadive Villa" class="footer-logo">
          <p>Casadive villa offers a relaxing beachfront stay with modern villas and campsite. Your perfect gateway awaits.</p>
        </div>

        <!-- Kolom Quick Links -->
        <div class="footer-col">
          <h4>Quick links</h4>
          <ul>
            <li><a href="<?= htmlspecialchars($base) ?>index.php">Home</a></li>
            <li><a href="<?= htmlspecialchars($base) ?>customer/villa.php">Villa</a></li>
            <li><a href="<?= htmlspecialchars($base) ?>customer/campsite.php">Campsite</a></li>
            <li><a href="<?= htmlspecialchars($base) ?>customer/gallery.php">Gallery</a></li>
            <li><a href="<?= htmlspecialchars($base) ?>customer/mybooking.php">MyBooking</a></li>
            <li><a href="<?= htmlspecialchars($base) ?>customer/contact_us.php">Contact Us</a></li>
          </ul>
        </div>

        <!-- Kolom Company -->
        <div class="footer-col">
          <h4>Company</h4>
          <ul>
            <li><a href="<?= htmlspecialchars($base) ?>customer/privacy_policy.php">Privacy policy</a></li>
            <li><a href="<?= htmlspecialchars($base) ?>customer/refund_policy.php">Refund policy</a></li>
            <li><a href="<?= htmlspecialchars($base) ?>customer/faq.php">F.A.Q</a></li>
            <li><a href="<?= htmlspecialchars($base) ?>customer/about_us.php">About Us</a></li>
          </ul>
        </div>

        <!-- Kolom Social Media -->
        <div class="footer-col">
          <h4>Social media</h4>
          <ul>
            <li><a href="https://www.facebook.com/Casadive%20Villa" target="_blank" rel="noopener">Facebook</a></li>
            <li><a href="https://www.tiktok.com/@casadive.villa" target="_blank" rel="noopener">TikTok</a></li>
            <li><a href="https://www.instagram.com/casadivevilla" target="_blank" rel="noopener">Instagram</a></li>
            <li><a href="https://wa.me/60103851892" target="_blank" rel="noopener">WhatsApp</a></li>
          </ul>
        </div>

        <!-- Kolom Mini Review Form -->
        <div class="footer-col newsletter">
          <h4 class="footer-review-heading">
            <span class="footer-review-heading-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
            </span>
            Write a Review
          </h4>
          <p class="footer-review-sub"><strong>Enjoyed</strong> your stay? Share your experience with future guests.</p>

          <form class="newsletter-form footer-review-form" method="post" action="<?= htmlspecialchars($base) ?>customer/mybooking.php" enctype="multipart/form-data" id="footer-review-form">
            <?= csrf_field() ?>
            <input type="hidden" name="form" value="review">

            <div class="footer-review-stars" role="radiogroup" aria-label="Rating">
              <?php for ($i = 5; $i >= 1; $i--): ?>
                <input type="radio" name="rating" id="footer-rating-<?= $i ?>" value="<?= $i ?>" <?= $i === 5 ? 'checked' : '' ?>>
                <label for="footer-rating-<?= $i ?>" title="<?= $i ?> stars">★</label>
              <?php endfor; ?>
            </div>

            <div class="footer-review-identity">
              <div class="footer-input-wrap">
                <span class="footer-input-icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                </span>
                <label for="footer-review-name" style="position:absolute;left:-9999px;">Name to show on your review</label>
                <input id="footer-review-name" type="text" name="display_name" maxlength="100" placeholder="Name to show on your review (optional)">
              </div>
              <label class="footer-review-anon-check">
                <input type="checkbox" id="footer-review-anonymous" name="is_anonymous" value="1">
                Post anonymously
              </label>
            </div>

            <div class="footer-input-wrap footer-input-wrap-textarea">
              <span class="footer-input-icon footer-input-icon-top" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
              </span>
              <textarea name="comment" rows="2" placeholder="Tell us about your stay (optional)"></textarea>
            </div>

            <div class="footer-photo-controls">
              <button type="button" class="footer-photo-btn" id="footer-upload-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                Upload Image
              </button>
              <button type="button" class="footer-photo-btn" id="footer-camera-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                Open Camera
              </button>
            </div>

            <!-- PENEGAPAN FORMAT GAMBAR DI SINI (Hanya terima PNG, JPEG, JPG dari galeri) -->
            <input type="file" id="footer-file-input" name="review_image" accept="image/png, image/jpeg, image/jpg" hidden>

            <img id="footer-image-preview" class="footer-image-preview" hidden alt="Selected room photo preview">
            <button type="button" class="footer-photo-btn footer-photo-btn-outline" id="footer-remove-btn" hidden>Remove Photo</button>
            <p id="footer-verify-status" class="footer-verify-status" hidden></p>

            <button type="submit" class="footer-review-submit-btn">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
              Submit Review
            </button>
          </form>
        </div>
      </div>

      <!-- CAMERA MODAL WIDGET -->
      <div class="footer-camera-modal" id="footer-camera-modal">
        <div class="footer-camera-modal-inner">
          <video id="footer-camera-video" autoplay playsinline muted></video>
          <canvas id="footer-camera-canvas" hidden></canvas>
          <div class="footer-camera-modal-actions">
            <button type="button" class="footer-photo-btn" id="footer-camera-capture-btn">Capture</button>
            <button type="button" class="footer-photo-btn footer-photo-btn-outline" id="footer-camera-cancel-btn">Cancel</button>
          </div>
        </div>
      </div>

      <!-- PREVIEW LIGHTBOX (klik preview untuk besarkan, dari upload ATAU camera capture) -->
      <div class="photo-lightbox" id="footer-lightbox">
        <button type="button" class="photo-lightbox-close" id="footer-lightbox-close" aria-label="Close">&times;</button>
        <img id="footer-lightbox-img" src="" alt="Room photo, enlarged">
        <button type="button" class="photo-lightbox-back" id="footer-lightbox-back">Back to Homepage</button>
      </div>

      <hr class="footer-divider">
      <p class="footer-bottom">&copy; <?= date('Y') ?> Casadive Villa. All rights reserved.</p>
    </div>
  </footer>

  <!-- WHATSAPP FLOATING CARD -->
  <?php if ($showWhatsapp): ?>
  <a class="whatsapp-card" href="https://wa.me/60103851892" aria-label="Chat with us on WhatsApp">
    <svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
      <circle cx="16" cy="16" r="16" fill="#25D366"/>
      <path d="M16 7a9 9 0 0 0-7.8 13.5L7 25l4.6-1.2A9 9 0 1 0 16 7z" fill="#fff"/>
      <path d="M16 8.6a7.4 7.4 0 0 0-6.4 11.1l.2.4-.8 2.9 3-.8.4.2A7.4 7.4 0 1 0 16 8.6z" fill="#25D366"/>
      <path d="M12.9 11.7c-.2-.5-.4-.5-.6-.5h-.5c-.2 0-.5.1-.7.4-.2.3-1 1-.9 2.4.1 1.4 1 2.7 1.1 2.9.1.2 2 3.2 5 4.3 2.5 1 2.9.8 3.5.7.6-.1 1.7-.7 2-1.3.2-.7.2-1.2.1-1.3-.1-.1-.3-.2-.5-.4-.3-.1-1.7-.8-1.9-.9-.3-.1-.5-.1-.6.1-.2.3-.7.9-.9 1.1-.1.2-.3.2-.6.1-.3-.1-1.2-.5-2.3-1.4-.9-.8-1.4-1.7-1.6-2-.2-.3 0-.5.1-.6l.4-.5c.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5-.1-.2-.5-1.5-.8-2.1z" fill="#fff"/>
    </svg>
  </a>
  <?php endif; ?>

  <!-- ASSETS & SCRIPT INJECTIONS -->
  <link rel="stylesheet" href="<?= htmlspecialchars($base . $footerCssHref) ?>">
  <script src="<?= htmlspecialchars($base . $reviewPhotoJsHref) ?>"></script>
  <script>
    // fail .js luar takleh proses <?php ?>, so jambatan kecil ni perlu kekal inline
    // (sama teknik macam CHATBOT_DATA dalam views/index.view.php)
    var FOOTER_REVIEW_HOME_URL = <?= json_encode($base . 'index.php') ?>;
  </script>
  <script src="<?= htmlspecialchars($base . $footerReviewInitJsHref) ?>"></script>

</body>
</html>