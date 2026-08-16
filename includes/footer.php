<?php
/**
 * Susun atur footer yang dikongsi untuk laman web.
 * Fail ini memaparkan pautan footer dan maklumat hubungan yang digunakan pada halaman awam.
 */
declare(strict_types=1);

/**
 * Pembolehubah yang dijangka daripada halaman yang memasukkan fail ini:
 *   $base          string  '' di root projek, '../' satu tahap ke bawah
 *   $showWhatsapp  bool    papar kad terapung WhatsApp (index.php sahaja)
 */
$base ??= ''; // default kosong kalau page tak set (bermaksud kita kat root folder)
$showWhatsapp ??= false; // default takyah papar butang WhatsApp terapung tu
?>
  <!-- FOOTER -->
  <footer>
    <div class="container">
      <div class="footer-grid">
        <div class="footer-col footer-brand">
          <div class="brand">Casadive Villa</div>
          <p>Casadive villa offers a relaxing beachfront stay with modern villas and campsite. Your perfect gateway awaits.</p>
        </div>

        <div class="footer-col">
          <h4>Quick links</h4>
          <ul>
            <li><a href="<?= $base ?>index.php">Home</a></li>
            <li><a href="<?= $base ?>customer/villa.php">Villa</a></li>
            <li><a href="<?= $base ?>customer/campsite.php">Campsite</a></li>
            <li><a href="<?= $base ?>customer/gallery.php">Gallery</a></li>
            <li><a href="<?= $base ?>customer/mybooking.php">MyBooking</a></li>
            <li><a href="<?= $base ?>customer/contact_us.php">Contact Us</a></li>
          </ul>
        </div>

        <div class="footer-col">
          <h4>Company</h4>
          <ul>
            <li><a href="#privacy">Privacy policy</a></li>
            <li><a href="#refund">Refund policy</a></li>
            <li><a href="#faq">F.A.Q</a></li>
            <li><a href="#about">About Us</a></li>
          </ul>
        </div>

        <div class="footer-col">
          <h4>Social media</h4>
          <ul>
            <li><a href="#facebook">Facebook</a></li>
            <li><a href="#tiktok">TikTok</a></li>
            <li><a href="#instagram">Instagram</a></li>
            <li><a href="#whatsapp">WhatsApp</a></li>
          </ul>
        </div>

        <div class="footer-col newsletter">
          <h4>Write a Review</h4>
          <p>Already stayed with us? Enter your booking reference and phone number to share your experience.</p>
          <!-- Reuses the same review handler as the "Leave a Review" form on MyBooking —
               only bookings marked checked_out can be reviewed, one review per booking.
               review_image/image_category/image_confidence are read by the SAME PHP handler
               (customer/mybooking.php) that already handles them for the full review form. -->
          <form class="newsletter-form footer-review-form" method="post" action="<?= $base ?>customer/mybooking.php" enctype="multipart/form-data" id="footer-review-form">
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
                <label for="footer-rating-<?= $i ?>" title="<?= $i ?> stars">&#9733;</label>
              <?php endfor; ?>
            </div>
            <textarea name="comment" rows="2" placeholder="Tell us about your stay (optional)"></textarea>

            <div class="footer-photo-controls">
              <button type="button" class="footer-photo-btn" id="footer-upload-btn">Upload Image</button>
              <button type="button" class="footer-photo-btn" id="footer-camera-btn">Open Camera</button>
            </div>
            <input type="file" id="footer-file-input" name="review_image" accept="image/*" hidden>
            <input type="hidden" id="footer-image-category" name="image_category">
            <input type="hidden" id="footer-image-confidence" name="image_confidence">
            <img id="footer-image-preview" class="footer-image-preview" hidden alt="Selected room photo preview">
            <p id="footer-verify-status" class="footer-verify-status" hidden></p>

            <button type="submit">Submit Review</button>
          </form>
        </div>
      </div>

      <!-- CAMERA MODAL (footer review widget — own IDs/classes, independent of MyBooking's
           full review form, since both can appear on the same page). -->
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

      <style>
        .footer-photo-controls{ display:flex; gap:8px; flex-wrap:wrap; margin-top:2px; }
        .footer-photo-btn{
          background: transparent;
          color:#fff;
          border: 1px solid rgba(255,255,255,.5);
          border-radius: 2.5px;
          padding: 8px 14px;
          font-family:'Raleway', sans-serif;
          font-weight:600;
          font-size: 12px;
          cursor:pointer;
        }
        .footer-photo-btn:hover{ background: rgba(255,255,255,.15); }
        .footer-photo-btn-outline{ border-color:#999; color:#ccc; }
        .footer-image-preview{ max-width: 140px; max-height: 140px; object-fit:cover; border-radius:8px; display:block; }
        .footer-verify-status{
          font-family:'Raleway', sans-serif;
          font-weight:600;
          font-size: 12px;
          padding: 8px 12px;
          border-radius: 6px;
          display:inline-block;
        }
        .footer-verify-status.processing{ background:#fff3cd; color:#8a6400; }
        .footer-verify-status.success{ background:#d9f2df; color:#1e6b34; }
        .footer-verify-status.reject{ background:#fdecea; color:#9a3226; }
        .footer-verify-status.uncertain{ background:#fde3c7; color:#8a4f00; }
        .footer-camera-modal{
          position: fixed; inset: 0; background: rgba(0,0,0,.7); z-index: 200;
          align-items:center; justify-content:center; padding: 20px;
          display:none;
        }
        /* same [hidden]-vs-CSS-display gotcha as .chatbot-panel — toggle this class instead */
        .footer-camera-modal.is-open{ display:flex; }
        .footer-camera-modal-inner{ background:#fff; border-radius: 14px; padding: 20px; max-width: 480px; width:100%; }
        .footer-camera-modal video{ width:100%; border-radius: 10px; background:#000; display:block; margin-bottom: 16px; }
        .footer-camera-modal-actions{ display:flex; gap: 10px; justify-content:center; }
      </style>

      <script>
      (function () {
        var form = document.getElementById('footer-review-form');
        if (!form) return;

        // Sama teknik macam review form penuh kat MyBooking — TensorFlow.js + COCO-SSD, jalan
        // dalam browser, percuma, dimuatkan cuma bila customer klik Upload/Camera. "bed"
        // dikesan = terima; "person" dominan = tolak (selfie); kenderaan/makanan = tolak;
        // selain itu = "uncertain", bukan tolak/terima yang mengarut sebab model ni tak ada
        // kelas khusus untuk bilik air/pool/logo/tangkapan skrin dll.
        var uploadBtn = document.getElementById('footer-upload-btn');
        var cameraBtn = document.getElementById('footer-camera-btn');
        var fileInput = document.getElementById('footer-file-input');
        var preview = document.getElementById('footer-image-preview');
        var statusEl = document.getElementById('footer-verify-status');
        var categoryField = document.getElementById('footer-image-category');
        var confidenceField = document.getElementById('footer-image-confidence');

        var cameraModal = document.getElementById('footer-camera-modal');
        var cameraVideo = document.getElementById('footer-camera-video');
        var cameraCanvas = document.getElementById('footer-camera-canvas');
        var captureBtn = document.getElementById('footer-camera-capture-btn');
        var cancelBtn = document.getElementById('footer-camera-cancel-btn');

        var CONFIDENCE_THRESHOLD = 0.5;
        var cocoModel = null;
        var modelLoading = null;
        var cameraStream = null;

        var VEHICLE_CLASSES = ['car', 'motorcycle', 'bus', 'truck', 'bicycle', 'train', 'airplane', 'boat'];
        var FOOD_CLASSES = ['banana', 'apple', 'sandwich', 'orange', 'broccoli', 'carrot', 'hot dog', 'pizza', 'donut', 'cake', 'bowl'];

        function setStatus(text, kind) {
          statusEl.hidden = false;
          statusEl.textContent = text;
          statusEl.className = 'footer-verify-status ' + (kind || '');
        }

        function loadScript(src) {
          return new Promise(function (resolve, reject) {
            var s = document.createElement('script');
            s.src = src;
            s.onload = resolve;
            s.onerror = reject;
            document.head.appendChild(s);
          });
        }

        function loadModel() {
          if (cocoModel) return Promise.resolve(cocoModel);
          if (modelLoading) return modelLoading;

          setStatus('Loading AI model…', 'processing');
          modelLoading = loadScript('https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4/dist/tf.min.js')
            .then(function () { return loadScript('https://cdn.jsdelivr.net/npm/@tensorflow-models/coco-ssd@2/dist/coco-ssd.min.js'); })
            .then(function () { return window.cocoSsd.load(); })
            .then(function (model) { cocoModel = model; return model; });
          return modelLoading;
        }

        function classify(imgEl) {
          return loadModel().then(function (model) {
            setStatus('AI is checking your image…', 'processing');
            return model.detect(imgEl);
          }).then(function (predictions) {
            var best = null;
            predictions.forEach(function (p) {
              if (p.score >= CONFIDENCE_THRESHOLD && (!best || p.score > best.score)) best = p;
            });

            if (!best) {
              return { is_valid: false, category: 'uncertain', confidence: predictions[0] ? predictions[0].score : 0 };
            }
            if (best.class === 'bed') {
              return { is_valid: true, category: 'bedroom', confidence: best.score };
            }
            if (best.class === 'person') {
              return { is_valid: false, category: 'selfie', confidence: best.score };
            }
            if (VEHICLE_CLASSES.indexOf(best.class) !== -1) {
              return { is_valid: false, category: 'vehicle', confidence: best.score };
            }
            if (FOOD_CLASSES.indexOf(best.class) !== -1) {
              return { is_valid: false, category: 'food', confidence: best.score };
            }
            return { is_valid: false, category: 'uncertain', confidence: best.score };
          });
        }

        function assignFile(file) {
          var dt = new DataTransfer();
          dt.items.add(file);
          fileInput.files = dt.files;
        }

        function clearFile() {
          fileInput.value = '';
          categoryField.value = '';
          confidenceField.value = '';
        }

        function handleFile(file) {
          clearFile();
          var url = URL.createObjectURL(file);
          var img = new Image();
          img.onload = function () {
            preview.src = url;
            preview.hidden = false;
            classify(img).then(function (result) {
              if (result.is_valid) {
                setStatus('✓ Image verified. Your room photo is suitable for this review.', 'success');
                categoryField.value = result.category;
                confidenceField.value = result.confidence.toFixed(3);
                assignFile(file);
              } else if (result.category === 'uncertain') {
                setStatus('Please upload a clearer photo showing the villa room.', 'uncertain');
              } else {
                setStatus('✕ Image rejected. Please upload a clear photo of the villa room only.', 'reject');
              }
            }).catch(function () {
              setStatus('Could not load the AI model right now — please try again, or submit your review without a photo.', 'reject');
            });
          };
          img.src = url;
        }

        uploadBtn.addEventListener('click', function () { fileInput.click(); });

        fileInput.addEventListener('change', function () {
          if (fileInput.files && fileInput.files[0]) handleFile(fileInput.files[0]);
        });

        function stopCamera() {
          if (cameraStream) {
            cameraStream.getTracks().forEach(function (t) { t.stop(); });
            cameraStream = null;
          }
          cameraModal.classList.remove('is-open');
        }

        cameraBtn.addEventListener('click', function () {
          setStatus('Requesting camera access…', 'processing');
          navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } }).then(function (stream) {
            cameraStream = stream;
            cameraVideo.srcObject = stream;
            cameraModal.classList.add('is-open');
            statusEl.hidden = true;
          }).catch(function () {
            setStatus('Camera access was denied or unavailable. Please use Upload Image instead.', 'reject');
          });
        });

        cancelBtn.addEventListener('click', stopCamera);

        captureBtn.addEventListener('click', function () {
          cameraCanvas.width = cameraVideo.videoWidth;
          cameraCanvas.height = cameraVideo.videoHeight;
          cameraCanvas.getContext('2d').drawImage(cameraVideo, 0, 0);
          cameraCanvas.toBlob(function (blob) {
            var file = new File([blob], 'camera-capture.jpg', { type: 'image/jpeg' });
            stopCamera();
            handleFile(file);
          }, 'image/jpeg', 0.9);
        });
      })();
      </script>

      <hr class="footer-divider">
      <p class="footer-bottom">&copy; 2026 Casadive Villa. All rights reserved.</p>
    </div>
  </footer>

  <?php if ($showWhatsapp): ?>
  <!-- WHATSAPP FLOATING CARD -->
  <a class="whatsapp-card" href="https://wa.me/60103851892" aria-label="Chat with us on WhatsApp">
    <svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
      <circle cx="16" cy="16" r="16" fill="#25D366"/>
      <path d="M16 7a9 9 0 0 0-7.8 13.5L7 25l4.6-1.2A9 9 0 1 0 16 7z" fill="#fff"/>
      <path d="M16 8.6a7.4 7.4 0 0 0-6.4 11.1l.2.4-.8 2.9 3-.8.4.2A7.4 7.4 0 1 0 16 8.6z" fill="#25D366"/>
      <path d="M12.9 11.7c-.2-.5-.4-.5-.6-.5h-.5c-.2 0-.5.1-.7.4-.2.3-1 1-.9 2.4.1 1.4 1 2.7 1.1 2.9.1.2 2 3.2 5 4.3 2.5 1 2.9.8 3.5.7.6-.1 1.7-.7 2-1.3.2-.7.2-1.2.1-1.3-.1-.1-.3-.2-.5-.4-.3-.1-1.7-.8-1.9-.9-.3-.1-.5-.1-.6.1-.2.3-.7.9-.9 1.1-.1.2-.3.2-.6.1-.3-.1-1.2-.5-2.3-1.4-.9-.8-1.4-1.7-1.6-2-.2-.3 0-.5.1-.6l.4-.5c.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5-.1-.2-.5-1.5-.8-2.1z" fill="#fff"/>
    </svg>
    <span>
      <strong>Need help?</strong>
      <small>Chat with us on WhatsApp!</small>
    </span>
  </a>
  <?php endif; ?>

</body>
</html>