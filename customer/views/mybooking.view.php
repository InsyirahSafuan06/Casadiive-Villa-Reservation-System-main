<?php include __DIR__ . '/../../includes/header.php'; ?>

  <!-- PAGE TITLE -->
  <div class="container page-title">
    <h1>MyBooking</h1>
    <p>Enter your booking reference and the phone number you booked with to view or print your receipt.</p>
  </div>

  <?php if ($booking): ?>

  <!-- RECEIPT -->
  <section class="receipt-section">
    <div class="container">
      <?php if ($reviewError && $booking['booking_status'] !== 'checked_out'): ?>
        <!-- A review was submitted (e.g. via the footer form) for a booking that isn't
             checked_out yet — the review section below is hidden in that case, so without
             this the rejection would happen silently and look like the button did nothing. -->
        <p class="lookup-error" style="max-width:700px;margin:0 auto 24px;"><?= htmlspecialchars($reviewError) ?></p>
      <?php endif; ?>
      <?php if ($cancelError): ?>
        <p class="lookup-error" style="max-width:700px;margin:0 auto 24px;"><?= htmlspecialchars($cancelError) ?></p>
      <?php endif; ?>
      <div class="receipt-card">
        <div class="receipt-head">
          <div>
            <p class="brand">Casadive Villa</p>
            <p class="receipt-ref">Booking Reference <?= htmlspecialchars(format_booking_ref((int) $booking['booking_id'])) ?> &middot; Booked on <?= htmlspecialchars(date('d M Y', strtotime($booking['booking_date']))) ?></p>
          </div>
          <span class="status-badge status-<?= htmlspecialchars($booking['booking_status']) ?>"><?= htmlspecialchars(format_status($booking['booking_status'])) ?></span>
        </div>

        <?php if ($booking['booking_status'] === 'cancelled' && $latestPaymentStatus): ?>
        <div class="receipt-block">
          <h3>Deposit Refund</h3>
          <?php if ($latestPaymentStatus === 'refunded'): ?>
            <p>Your payment of RM <?= number_format($amountPaid, 2) ?> has been refunded.</p>
          <?php elseif (in_array($latestPaymentStatus, ['paid', 'partial'], true)): ?>
            <p>Your payment of RM <?= number_format($amountPaid, 2) ?> is being processed for refund. Since refunds are handled manually (bank transfer/cash), please allow a few business days, or contact us directly if you need it sooner.</p>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="receipt-block">
          <h3>Guest Details</h3>
          <dl class="receipt-grid">
            <div><dt>Full Name</dt><dd><?= htmlspecialchars($booking['full_name']) ?></dd></div>
            <div><dt>Phone Number</dt><dd><?= htmlspecialchars($booking['phone']) ?></dd></div>
            <div><dt>Car Plate Number</dt><dd><?= htmlspecialchars($booking['plate_num'] ?: '—') ?></dd></div>
            <div><dt>Number of Guests</dt><dd><?= (int) $booking['total_guest'] ?></dd></div>
          </dl>
        </div>

        <div class="receipt-block">
          <h3>Stay Details</h3>
          <dl class="receipt-grid">
            <div><dt>Check-in</dt><dd><?= htmlspecialchars($booking['check_in']) ?></dd></div>
            <div><dt>Check-out</dt><dd><?= htmlspecialchars($booking['check_out']) ?></dd></div>
            <div><dt>Nights</dt><dd><?= $nights ?></dd></div>
            <div><dt>Accommodation</dt><dd><?= htmlspecialchars(implode(', ', array_column($items, 'accommodation_name')) ?: '—') ?></dd></div>
          </dl>
        </div>

        <?php if (!empty($booking['special_request'])): ?>
        <div class="receipt-block">
          <h3>Special Request</h3>
          <p class="receipt-request"><?= nl2br(htmlspecialchars($booking['special_request'])) ?></p>
        </div>
        <?php endif; ?>

        <div class="receipt-block">
          <h3>Price Breakdown</h3>
          <div class="receipt-rows">
            <?php foreach ($items as $item):
              // tunjuk pecahan weekday/weekend cuma kalau kadar dia memang lain, kalau sama je tunjuk simple
              $weekendRate = $item['nightly_price_weekend'] !== null ? (float) $item['nightly_price_weekend'] : (float) $item['nightly_price'];
              $rateLabel = $stay['weekend_nights'] > 0 && $weekendRate !== (float) $item['nightly_price']
                  ? "{$stay['weekday_nights']} weekday night" . ($stay['weekday_nights'] !== 1 ? 's' : '') . " &times; RM " . number_format((float) $item['nightly_price'], 2)
                    . " + {$stay['weekend_nights']} weekend night" . ($stay['weekend_nights'] !== 1 ? 's' : '') . " &times; RM " . number_format($weekendRate, 2)
                  : "RM " . number_format((float) $item['nightly_price'], 2) . " &times; {$nights} night" . ($nights > 1 ? 's' : '');
            ?>
              <div class="receipt-row">
                <span><?= htmlspecialchars($item['accommodation_name']) ?> (<?= $rateLabel ?>)</span>
                <span>RM <?= number_format((float) $item['price'], 2) ?></span>
              </div>
            <?php endforeach; ?>
            <div class="receipt-row">
              <span>Booking Deposit</span>
              <span>RM <?= number_format((float) $booking['deposit_amount'], 2) ?></span>
            </div>
            <div class="receipt-row total">
              <span>Total Price</span>
              <span>RM <?= number_format(booking_grand_total($booking), 2) ?></span>
            </div>
            <div class="receipt-row">
              <span>Amount Paid</span>
              <span>RM <?= number_format($amountPaid, 2) ?></span>
            </div>
            <div class="receipt-row balance">
              <span>Balance Due</span>
              <span>RM <?= number_format(booking_grand_total($booking) - $amountPaid, 2) ?></span>
            </div>
          </div>
        </div>

        <?php
          // booking belum bayar/deposit belum settle lagi kalau takde rekod payment atau status dia 'pending'
          $isUnpaid = $latestPaymentStatus === null || $latestPaymentStatus === 'pending';
          $showPayNow = $isUnpaid && in_array($booking['booking_status'], ['pending', 'confirmed'], true);
        ?>
        <div class="receipt-actions">
          <?php if ($showPayNow): ?>
            <a href="payment.php?booking_id=<?= (int) $booking['booking_id'] ?>" class="receipt-print">Pay Now</a>
          <?php endif; ?>
          <button type="button" class="receipt-print" onclick="window.print()">Print Receipt</button>
          <a href="mybooking.php" class="receipt-back">Look Up Another Booking</a>
          <?php if (in_array($booking['booking_status'], ['pending', 'confirmed'], true)): ?>
            <form method="post" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
              <?= csrf_field() ?>
              <input type="hidden" name="form" value="cancel">
              <input type="hidden" name="ref" value="<?= (int) $booking['booking_id'] ?>">
              <input type="hidden" name="phone" value="<?= htmlspecialchars($booking['phone']) ?>">
              <button type="submit" class="receipt-back" style="color:#c0392b;border-color:#c0392b;">Cancel Booking</button>
            </form>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($booking['booking_status'] === 'checked_out'): ?>
      <div class="receipt-card review-card">
        <?php if ($existingReview): ?>
          <h3>Your Review</h3>
          <div class="stars" aria-label="<?= (int) $existingReview['rating'] ?> out of 5 stars">
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <svg viewBox="0 0 20 19" class="<?= $i <= (int) $existingReview['rating'] ? '' : 'star-empty' ?>"><polygon points="10,0 12.5,7 20,7 14,11.5 16,19 10,14.5 4,19 6,11.5 0,7 7.5,7"/></svg>
            <?php endfor; ?>
          </div>
          <p class="review-date">Shown publicly as: <strong><?= htmlspecialchars($existingReview['display_name'] ?: 'Anonymous') ?></strong></p>
          <?php if ($existingReview['comment']): ?>
            <p class="review-comment"><?= nl2br(htmlspecialchars($existingReview['comment'])) ?></p>
          <?php endif; ?>
          <?php if ($existingReview['image_path']): ?>
            <img class="review-photo" src="../<?= htmlspecialchars($existingReview['image_path']) ?>" alt="Room photo shared by the guest">
          <?php endif; ?>
          <p class="review-date">Reviewed on <?= htmlspecialchars(date('d M Y', strtotime($existingReview['review_date']))) ?></p>
        <?php else: ?>
          <h3>Leave a Review</h3>
          <?php if ($reviewError): ?>
            <p class="lookup-error"><?= htmlspecialchars($reviewError) ?></p>
          <?php endif; ?>
          <form method="post" class="review-form" id="review-form" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="form" value="review">
            <input type="hidden" name="ref" value="<?= (int) $booking['booking_id'] ?>">
            <input type="hidden" name="phone" value="<?= htmlspecialchars($booking['phone']) ?>">
            <div class="rating-picker" role="radiogroup" aria-label="Rating">
              <?php for ($i = 5; $i >= 1; $i--): ?>
                <input type="radio" name="rating" id="rating-<?= $i ?>" value="<?= $i ?>" <?= $i === 5 ? 'checked' : '' ?>>
                <label for="rating-<?= $i ?>" title="<?= $i ?> stars">&#9733;</label>
              <?php endfor; ?>
            </div>
            <label for="review-display-name" class="review-name-label">Name to show on your review (optional)</label>
            <input type="text" id="review-display-name" name="display_name" maxlength="100" placeholder="e.g. Aina, or leave blank to stay anonymous">
            <label class="review-anon-check">
              <input type="checkbox" id="review-is-anonymous" name="is_anonymous" value="1">
              Post anonymously
            </label>
            <textarea name="comment" rows="3" placeholder="Tell us about your stay (optional)"></textarea>

            <div class="review-photo-section">
              <p class="review-photo-label">Add a room photo (optional) &mdash; our AI checks it's actually a photo of the room before it's attached.</p>
              <div class="review-photo-controls">
                <button type="button" class="photo-btn" id="review-upload-btn">Upload Image</button>
                <button type="button" class="photo-btn" id="review-camera-btn">Open Camera</button>
              </div>
              <input type="file" id="review-file-input" name="review_image" accept="image/*" hidden>
              <img id="review-image-preview" class="review-photo-preview" hidden alt="Selected room photo preview">
              <button type="button" class="photo-btn photo-btn-outline" id="review-remove-btn" hidden>Remove Photo</button>
              <p id="review-verify-status" class="review-verify-status" hidden></p>
            </div>

            <button type="submit" class="lookup-submit">Submit Review</button>
          </form>

          <!-- CAMERA MODAL -->
          <div class="camera-modal" id="camera-modal">
            <div class="camera-modal-inner">
              <video id="camera-video" autoplay playsinline muted></video>
              <canvas id="camera-canvas" hidden></canvas>
              <div class="camera-modal-actions">
                <button type="button" class="photo-btn" id="camera-capture-btn">Capture</button>
                <button type="button" class="photo-btn photo-btn-outline" id="camera-cancel-btn">Cancel</button>
              </div>
            </div>
          </div>

          <!-- PREVIEW LIGHTBOX (klik preview untuk besarkan, dari upload ATAU camera capture) -->
          <div class="photo-lightbox" id="review-lightbox">
            <button type="button" class="photo-lightbox-close" id="review-lightbox-close" aria-label="Close">&times;</button>
            <img id="review-lightbox-img" src="" alt="Room photo, enlarged">
            <button type="button" class="photo-lightbox-back" id="review-lightbox-back">Back to Homepage</button>
          </div>

          <?php
          // cache-bust supaya browser customer tak terus guna versi lama review-photo.js yang dah di-cache
          $reviewPhotoJsPath = __DIR__ . '/../../assets/js/review-photo.js';
          $reviewPhotoJsVer = is_file($reviewPhotoJsPath) ? '?v=' . filemtime($reviewPhotoJsPath) : '';
          ?>
          <script src="../assets/js/review-photo.js<?= $reviewPhotoJsVer ?>"></script>
          <script>
            initReviewPhotoWidget({
              form: 'review-form',
              uploadBtn: 'review-upload-btn',
              cameraBtn: 'review-camera-btn',
              removeBtn: 'review-remove-btn',
              fileInput: 'review-file-input',
              preview: 'review-image-preview',
              status: 'review-verify-status',
              statusClass: 'review-verify-status',
              cameraModal: 'camera-modal',
              cameraVideo: 'camera-video',
              cameraCanvas: 'camera-canvas',
              captureBtn: 'camera-capture-btn',
              cancelBtn: 'camera-cancel-btn',
              lightbox: 'review-lightbox',
              lightboxImg: 'review-lightbox-img',
              lightboxClose: 'review-lightbox-close',
              lightboxBack: 'review-lightbox-back',
              homeUrl: '<?= htmlspecialchars($base) ?>index.php'
            });
          </script>
          <script>
            (function () {
              var anon = document.getElementById('review-is-anonymous');
              var nameInput = document.getElementById('review-display-name');
              if (!anon || !nameInput) return;
              anon.addEventListener('change', function () {
                nameInput.disabled = anon.checked;
                if (anon.checked) nameInput.value = '';
              });
            })();
          </script>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <?php else: ?>

  <!-- LOOKUP FORM -->
  <section class="lookup-section">
    <div class="container">
      <div class="lookup-card">
        <?php if ($lookupError): ?>
          <p class="lookup-error"><?= htmlspecialchars($lookupError) ?></p>
        <?php endif; ?>

        <form method="get">
          <div class="form-field">
            <label for="ref">Booking Reference</label>
            <input type="text" id="ref" name="ref" placeholder="e.g. CDV12" value="<?= htmlspecialchars($ref) ?>" required>
          </div>
          <div class="form-field">
            <label for="phone">Phone Number</label>
            <input type="tel" id="phone" name="phone" placeholder="The phone number used to book" value="<?= htmlspecialchars($phone) ?>" required>
          </div>
          <button type="submit" class="lookup-submit">View My Booking</button>
        </form>

        <p class="lookup-hint">Your booking reference was shown after you completed a booking. Don't have one yet? <a href="villa.php" style="color:var(--orange-deep);font-weight:600;">Browse packages</a>.</p>
      </div>
    </div>
  </section>

  <?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
