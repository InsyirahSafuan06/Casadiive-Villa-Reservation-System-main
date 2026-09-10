<?php
/**
 * Susun atur footer yang dikongsi untuk laman web Casadive Villa.
 */
declare(strict_types=1);

$base ??= ''; 
$showWhatsapp ??= false; 

// Cache-busting untuk footer CSS
$footerCssHref = 'assets/css/footer.css';
$footerCssPath = __DIR__ . '/../assets/css/footer.css';
if (is_file($footerCssPath)) {
    $footerCssHref .= '?v=' . filemtime($footerCssPath);
}
?>
  <!-- FOOTER -->
  <footer>
    <div class="container">
      <div class="footer-grid">
        
        <!-- Kolom Info Utama -->
        <div class="footer-col footer-brand">
          <div class="brand">Casadive Villa</div>
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
            <li><a href="#privacy">Privacy policy</a></li>
            <li><a href="#refund">Refund policy</a></li>
            <li><a href="#faq">F.A.Q</a></li>
            <li><a href="#about">About Us</a></li>
          </ul>
        </div>

        <!-- Kolom Social Media -->
        <div class="footer-col">
          <h4>Social media</h4>
          <ul>
            <li><a href="#facebook">Facebook</a></li>
            <li><a href="#tiktok">TikTok</a></li>
            <li><a href="#instagram">Instagram</a></li>
            <li><a href="#whatsapp">WhatsApp</a></li>
          </ul>
        </div>

        <!-- Kolom Mini Review Form -->
        <div class="footer-col newsletter">
          <h4>Write a Review</h4>
          <p>Already stayed with us? Enter your details to share your experience.</p>
          
          <form class="newsletter-form footer-review-form" method="post" action="<?= htmlspecialchars($base) ?>customer/mybooking.php" enctype="multipart/form-data" id="footer-review-form">
            <?= csrf_field() ?>
            <input type="hidden" name="form" value="review">
            
            <div class="footer-review-fields">
              <label for="footer-review-ref" style="position:absolute;left:-9999px;">Booking Reference</label>
              <input id="footer-review-ref" type="text" name="ref" placeholder="Booking Reference" required>
              
              <label for="footer-review-phone" style="position:absolute;left:-9999px;">Phone Number</label>
              <input id="footer-review-phone" type="tel" name="phone" placeholder="Phone Number" required>
            </div>
            
            <div class="footer-review-stars" role="radiogroup" aria-label="Rating">
              <?php for ($i = 5; $i >= 1; $i--): ?>
                <input type="radio" name="rating" id="footer-rating-<?= $i ?>" value="<?= $i ?>" <?= $i === 5 ? 'checked' : '' ?>>
                <label for="footer-rating-<?= $i ?>" title="<?= $i ?> stars">★</label>
              <?php endfor; ?>
            </div>
            
            <textarea name="comment" rows="2" placeholder="Tell us about your stay (optional)"></textarea>

            <div class="footer-photo-controls">
              <button type="button" class="footer-photo-btn" id="footer-upload-btn">Upload Image</button>
              <button type="button" class="footer-photo-btn" id="footer-camera-btn">Open Camera</button>
            </div>
            
            <!-- PENEGAPAN FORMAT GAMBAR DI SINI (Hanya terima PNG, JPEG, JPG dari galeri) -->
            <input type="file" id="footer-file-input" name="review_image" accept="image/png, image/jpeg, image/jpg" hidden>
            
            <input type="hidden" id="footer-image-category" name="image_category">
            <input type="hidden" id="footer-image-confidence" name="image_confidence">
            <img id="footer-image-preview" class="footer-image-preview" hidden alt="Selected room photo preview">
            <button type="button" class="footer-photo-btn footer-photo-btn-outline" id="footer-remove-btn" hidden>Remove Photo</button>
            <p id="footer-verify-status" class="footer-verify-status" hidden></p>

            <button type="submit">Submit Review</button>
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
  <script src="<?= htmlspecialchars($base) ?>assets/js/review-photo.js"></script>
  <script>
    // fail .js luar takleh proses <?php ?>, so jambatan kecil ni perlu kekal inline
    // (sama teknik macam CHATBOT_DATA dalam views/index.view.php)
    var FOOTER_REVIEW_HOME_URL = <?= json_encode($base . 'index.php') ?>;
  </script>
  <script src="<?= htmlspecialchars($base) ?>assets/js/footer-review-init.js"></script>

</body>
</html>