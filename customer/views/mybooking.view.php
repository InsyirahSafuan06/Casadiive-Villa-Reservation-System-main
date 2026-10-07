<?php include __DIR__ . '/../../includes/header.php'; ?>
<script src="../assets/js/simple-modal.js"></script>

  <div class="container page-title">
    <svg class="mybooking-illustration" viewBox="0 0 160 100" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <circle cx="118" cy="20" r="10" fill="currentColor" stroke="none" opacity=".55"></circle>
      <path d="M128 16 134 13M130 22 137 22" opacity=".55"></path>
      <path d="M45 55 80 25 115 55"></path>
      <path d="M55 55V85H105V55"></path>
      <rect x="70" y="38" width="9" height="9" rx="1"></rect>
      <rect x="82" y="38" width="9" height="9" rx="1"></rect>
      <path d="M20 88c12-8 20-8 32 0s20 8 32 0 20-8 32 0 20 8 32 0" opacity=".7"></path>
    </svg>
    <h1>MyBooking</h1>
    <p>Enter your phone number you used to book with us to view or print your receipt.</p>
  </div>

  <?php # check kalau $booking dah jumpa (lookup berjaya), papar receipt, kalau tak papar form cari booking ?>
  <?php if ($booking): ?>

  <section class="receipt-section">
    <div class="container">
      <?php # check kalau ada reviewError & booking tu belum checked_out, papar mesej error review ?>
      <?php if ($reviewError && $booking['booking_status'] !== 'checked_out'): ?>
        <p class="lookup-error" style="max-width:700px;margin:0 auto 24px;"><?= htmlspecialchars($reviewError) ?></p>
      <?php endif; ?>
      <?php # check kalau ada cancelError, papar mesej error cancel ?>
      <?php if ($cancelError): ?>
        <p class="lookup-error" style="max-width:700px;margin:0 auto 24px;"><?= htmlspecialchars($cancelError) ?></p>
      <?php endif; ?>
      <div class="receipt-card">
        <div class="receipt-head">
          <div>
            <p class="brand">Casadive Villa</p>
            <?php # calling function format_booking_ref() untuk format nombor booking jadi reference, calling function date()+strtotime() untuk format tarikh booking ?>
            <p class="receipt-ref">Booking Reference <?= htmlspecialchars(format_booking_ref((int) $booking['booking_id'])) ?> &middot; Booked on <?= htmlspecialchars(date('d M Y', strtotime($booking['booking_date']))) ?></p>
          </div>
          <?php # calling function format_status() untuk tukar status booking jadi label senang baca ?>
          <span class="status-badge status-<?= htmlspecialchars($booking['booking_status']) ?>"><?= htmlspecialchars(format_status($booking['booking_status'])) ?></span>
        </div>

        <?php # check kalau booking dah cancelled & ada latest payment status, baru papar block refund ?>
        <?php if ($booking['booking_status'] === 'cancelled' && in_array($latestPaymentStatus, ['refunded', 'paid', 'partial'], true)): ?>
        <div class="receipt-block">
          <h3>Deposit Refund</h3>
          <?php # check kalau payment status 'refunded', papar mesej dah refund ?>
          <?php if ($latestPaymentStatus === 'refunded'): ?>
            <p>Your payment of RM <?= number_format($amountPaid, 2) ?> has been refunded.</p>
          <?php # calling function in_array() untuk check payment status masih 'paid' atau 'partial', kalau ya tunjuk mesej proses refund ?>
          <?php elseif (in_array($latestPaymentStatus, ['paid', 'partial'], true)): ?>
            <p>Your payment of RM <?= number_format($amountPaid, 2) ?> is being processed for refund. Since refunds are handled manually (bank transfer/cash), please allow a few business days, or contact us directly if you need it sooner.</p>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($latestPaymentStatus === 'failed'): ?>
        <div class="receipt-block invalid-payment-receipt">
          <h3>Payment Receipt <span class="invalid-receipt-badge">Invalid</span></h3>
          <p class="invalid-receipt-notice">This receipt was rejected and the booking was cancelled.</p>
          <?php if ($latestPaymentReceipt): ?>
            <div class="invalid-receipt-preview">
              <?php if ($latestPaymentReceiptType === 'image'): ?>
                <a href="<?= htmlspecialchars($latestPaymentReceiptUrl) ?>" target="_blank" rel="noopener noreferrer">
                  <img src="<?= htmlspecialchars($latestPaymentReceiptUrl) ?>" alt="Rejected payment receipt">
                </a>
              <?php else: ?>
                <object data="<?= htmlspecialchars($latestPaymentReceiptUrl) ?>" type="application/pdf" aria-label="Rejected payment receipt PDF">
                  <a href="<?= htmlspecialchars($latestPaymentReceiptUrl) ?>" target="_blank" rel="noopener noreferrer">Open rejected receipt (PDF)</a>
                </object>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <p>The uploaded receipt file is no longer available.</p>
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
            <?php # calling function date()+strtotime() untuk format tarikh check-in ikut format d/m/Y ?>
            <div><dt>Check-in</dt><dd><?= htmlspecialchars(date('d/m/Y', strtotime($booking['check_in']))) ?></dd></div>
            <?php # calling function date()+strtotime() untuk format tarikh check-out ikut format d/m/Y ?>
            <div><dt>Check-out</dt><dd><?= htmlspecialchars(date('d/m/Y', strtotime($booking['check_out']))) ?></dd></div>
            <div><dt>Nights</dt><dd><?= $nights ?></dd></div>
            <?php # calling function array_column() untuk ambil semua nama accommodation dari $items, calling function implode() untuk gabung jadi satu string ?>
            <div><dt>Accommodation</dt><dd><?= htmlspecialchars(implode(', ', array_column($items, 'accommodation_name')) ?: '—') ?></dd></div>
          </dl>
        </div>

        <?php # calling function empty() untuk check ada special request ke tak, kalau ada baru papar section ni ?>
        <?php if (!empty($booking['special_request'])): ?>
        <div class="receipt-block">
          <h3>Special Request</h3>
          <?php # calling function nl2br() untuk tukar baris baru dalam special request jadi <br> ?>
          <p class="receipt-request"><?= nl2br(htmlspecialchars($booking['special_request'])) ?></p>
        </div>
        <?php endif; ?>

        <div class="receipt-block">
          <h3>Price Breakdown</h3>
          <div class="receipt-rows">
            <?php # loop setiap item dalam $items untuk papar setiap accommodation yg dibooking
            foreach ($items as $item):
              $rateLabel = $nights . ' night' . ($nights > 1 ? 's' : '') . ' (price confirmed at booking)';
            ?>
              <div class="receipt-row">
                <span><?= htmlspecialchars($item['accommodation_name']) ?> (<?= $rateLabel ?>)</span>
                <span>RM <?= number_format((float) $item['price'], 2) ?></span>
              </div>
            <?php endforeach; ?>
            <?php # check kalau booking ni ada addon bbq, papar row bbq ?>
            <?php if ($booking['addon_bbq']): ?>
              <div class="receipt-row">
                <span>Add-on: BBQ Set</span>
                <span>RM <?= number_format(ADDON_BBQ_PRICE, 2) ?></span>
              </div>
            <?php endif; ?>
            <?php # check kalau booking ni ada addon mattress, papar row mattress ?>
            <?php if ($booking['addon_mattress']): ?>
              <div class="receipt-row">
                <span>Add-on: Extra Mattress</span>
                <span>RM <?= number_format(ADDON_MATTRESS_PRICE, 2) ?></span>
              </div>
            <?php endif; ?>
            <?php # check kalau discount_amount lebih dari 0, papar row long stay discount ?>
            <?php if ((float) $booking['discount_amount'] > 0): ?>
              <div class="receipt-row">
                <span>Long Stay Discount</span>
                <span>-RM <?= number_format((float) $booking['discount_amount'], 2) ?></span>
              </div>
            <?php endif; ?>
            <div class="receipt-row">
              <span>Booking Deposit</span>
              <span>RM <?= number_format((float) $booking['deposit_amount'], 2) ?></span>
            </div>
            <div class="receipt-row total">
              <span>Total Price</span>
              <?php # calling function booking_grand_total() untuk kira jumlah keseluruhan harga booking ?>
              <span>RM <?= number_format(booking_grand_total($booking), 2) ?></span>
            </div>
            <div class="receipt-row">
              <span>Amount Paid</span>
              <span>RM <?= number_format($amountPaid, 2) ?></span>
            </div>
            <div class="receipt-row balance">
              <span>Balance Due</span>
              <?php # calling function booking_grand_total() untuk tolak amount paid dari total, dapat baki yg perlu bayar ?>
              <span>RM <?= number_format(booking_grand_total($booking) - $amountPaid, 2) ?></span>
            </div>
          </div>
        </div>

        <?php
          # check kalau latest payment status kosong atau 'pending', maksudnya booking ni belum bayar
          $isUnpaid = $latestPaymentStatus === null || $latestPaymentStatus === 'pending';
          # calling function in_array() untuk check status booking masih pending/confirmed, gabung dgn $isUnpaid untuk decide papar butang Pay Now ke tak
          $showPayNow = $isUnpaid && in_array($booking['booking_status'], ['pending', 'confirmed'], true);
        ?>
        <div class="receipt-actions">
          <?php # check $showPayNow, kalau true papar butang Pay Now, kalau tak papar butang print receipt ?>
          <?php if ($showPayNow): ?>
            <a href="payment.php?booking_id=<?= (int) $booking['booking_id'] ?>" class="receipt-print">Pay Now</a>
          <?php else: ?>
            <button type="button" class="receipt-print" onclick="window.print()">Print Receipt</button>
            <a href="<?= htmlspecialchars($base) ?>index.php" class="receipt-back">Look Up Another Booking</a>
          <?php endif; ?>
          <?php # calling function in_array() untuk check status booking masih pending/confirmed, kalau ya papar butang cancel booking ?>
          <?php if (in_array($booking['booking_status'], ['pending', 'confirmed'], true)): ?>
            <form method="post" id="cancel-booking-form">
              <?php # calling function csrf_field() untuk bina hidden input token, elak CSRF masa submit cancel ?>
              <?= csrf_field() ?>
              <input type="hidden" name="form" value="cancel">
              <input type="hidden" name="ref" value="<?= (int) $booking['booking_id'] ?>">
              <input type="hidden" name="phone" value="<?= htmlspecialchars($booking['phone']) ?>">
              <button type="button" id="cancel-booking-trigger" class="receipt-back" style="color:#c0392b;border-color:#c0392b;">Cancel Booking</button>
            </form>

            <div class="cancel-modal-overlay" id="cancel-modal-overlay" hidden>
              <div class="cancel-modal" role="dialog" aria-modal="true" aria-labelledby="cancel-modal-title">
                <button type="button" class="cancel-modal-close" id="cancel-modal-close" aria-label="Close">&times;</button>
                <svg class="cancel-modal-icon" viewBox="0 0 120 100" aria-hidden="true">
                  <path d="M92 26l4-4M101 30h6M97 21v6" stroke="var(--orange-deep)" stroke-width="2.5" stroke-linecap="round" fill="none"/>
                  <path d="M14 55c8-4 14-2 18 4" stroke="var(--brown-price)" stroke-width="2.5" fill="none" stroke-linecap="round"/>
                  <path d="M10 63c8 2 12 6 14 12" stroke="var(--brown-price)" stroke-width="2.5" fill="none" stroke-linecap="round"/>
                  <path d="M106 55c-8-4-14-2-18 4" stroke="var(--brown-price)" stroke-width="2.5" fill="none" stroke-linecap="round"/>
                  <path d="M110 63c-8 2-12 6-14 12" stroke="var(--brown-price)" stroke-width="2.5" fill="none" stroke-linecap="round"/>
                  <rect x="28" y="25" width="64" height="55" rx="8" fill="#F3D9B1" stroke="var(--brown-price)" stroke-width="3"/>
                  <rect x="28" y="25" width="64" height="16" rx="8" fill="var(--brown-price)"/>
                  <rect x="40" y="16" width="6" height="16" rx="3" fill="var(--brown-price)"/>
                  <rect x="74" y="16" width="6" height="16" rx="3" fill="var(--brown-price)"/>
                  <rect x="37" y="50" width="10" height="10" rx="2" fill="var(--orange)"/>
                  <rect x="55" y="50" width="10" height="10" rx="2" fill="var(--orange)"/>
                  <circle cx="83" cy="66" r="14" fill="#E4572E" stroke="#fff" stroke-width="3"/>
                  <path d="M77 60l12 12M89 60l-12 12" stroke="#fff" stroke-width="3" stroke-linecap="round"/>
                </svg>
                <h2 id="cancel-modal-title">Cancel Booking Reminder</h2>
                <p>Are you sure you want to cancel this booking?<br>This action cannot be undone.</p>
                <div class="cancel-modal-actions">
                  <button type="button" class="cancel-modal-btn cancel-modal-btn-outline" id="cancel-modal-dismiss">Cancel</button>
                  <button type="button" class="cancel-modal-btn cancel-modal-btn-solid" id="cancel-modal-confirm">OK</button>
                </div>
              </div>
            </div>
            <script>
              (function () {
                var form = document.getElementById('cancel-booking-form');
                var trigger = document.getElementById('cancel-booking-trigger');
                var modal = initSimpleModal('cancel-modal-overlay', {
                  closeIds: ['cancel-modal-close', 'cancel-modal-dismiss'],
                  confirmId: 'cancel-modal-confirm',
                  onConfirm: function () { form.submit(); }
                });
                if (trigger && modal) trigger.addEventListener('click', modal.open);
              })();
            </script>
          <?php endif; ?>
        </div>
      </div>

      <?php # check kalau review baru je submit & memang ada existing review, papar popup thanks ?>
      <?php if ($reviewSubmitted && $existingReview): ?>
      <div class="purchase-modal-overlay" id="review-thanks-overlay">
        <div class="purchase-modal" role="dialog" aria-modal="true" aria-labelledby="review-thanks-title">
          <button type="button" class="purchase-modal-close" id="review-thanks-close" aria-label="Close">&times;</button>
          <span class="purchase-modal-leaf purchase-modal-leaf-bl" aria-hidden="true"></span>
          <span class="purchase-modal-leaf purchase-modal-leaf-br" aria-hidden="true"></span>
          <svg class="purchase-modal-icon" viewBox="0 0 120 100" aria-hidden="true">
            <path d="M14 26l4-4M22 30h6M18 21v6" stroke="var(--orange-deep)" stroke-width="2.5" stroke-linecap="round" fill="none"/>
            <path d="M100 22l4-4M108 26h6M104 17v6" stroke="var(--orange-deep)" stroke-width="2.5" stroke-linecap="round" fill="none"/>
            <path d="M30 20h60a8 8 0 0 1 8 8v28a8 8 0 0 1-8 8H50l-16 14V64h-4a8 8 0 0 1-8-8V28a8 8 0 0 1 8-8Z" fill="#fff" stroke="var(--brown-price)" stroke-width="3"/>
            <polygon points="45,32 47.5,39 55,39 49,43.5 51,50.5 45,46 39,50.5 41,43.5 35,39 42.5,39" fill="var(--orange)"/>
            <polygon points="63,32 65.5,39 73,39 67,43.5 69,50.5 63,46 57,50.5 59,43.5 53,39 60.5,39" fill="var(--orange)"/>
            <polygon points="81,32 83.5,39 91,39 85,43.5 87,50.5 81,46 75,50.5 77,43.5 71,39 78.5,39" fill="var(--orange)"/>
            <circle cx="94" cy="60" r="14" fill="var(--orange-deep)" stroke="var(--cream)" stroke-width="4"/>
            <path d="M94 66c-5-3.5-7-6-7-9a4 4 0 0 1 7-2.6A4 4 0 0 1 101 57c0 3-2 5.5-7 9Z" fill="#fff"/>
          </svg>
          <h2 id="review-thanks-title">Thanks for Reviewing!</h2>
          <p>Your feedback means a lot to us. It helps us improve and gives others confidence to book with us.</p>
          <div class="purchase-modal-customer">
            <span class="purchase-modal-avatar" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
            </span>
            <span>
              <span class="purchase-modal-customer-label">Customer Name</span>
              <span class="purchase-modal-customer-name"><?= htmlspecialchars($booking['full_name']) ?></span>
            </span>
          </div>
          <button type="button" class="purchase-modal-ok" id="review-thanks-ok">OK</button>
        </div>
      </div>
      <script>
        initSimpleModal('review-thanks-overlay', { closeIds: ['review-thanks-close', 'review-thanks-ok'] });
      </script>
      <?php endif; ?>

      <?php # check kalau booking dah checked_out, baru papar section review (tengok review lama atau leave review baru) ?>
      <?php if ($booking['booking_status'] === 'checked_out'): ?>
      <div class="receipt-card review-card">
        <?php # check kalau dah ada existing review, papar review tu, kalau belum papar form untuk leave review ?>
        <?php if ($existingReview): ?>
          <h3>Your Review</h3>
          <div class="stars" aria-label="<?= (int) $existingReview['rating'] ?> out of 5 stars">
            <?php # loop dari 1 sampai 5 untuk papar 5 bintang ?>
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <?php # check kalau $i tak lebih dari rating sebenar, bintang penuh, kalau lebih bintang kosong ?>
              <svg viewBox="0 0 20 19" class="<?= $i <= (int) $existingReview['rating'] ? '' : 'star-empty' ?>"><polygon points="10,0 12.5,7 20,7 14,11.5 16,19 10,14.5 4,19 6,11.5 0,7 7.5,7"/></svg>
            <?php endfor; ?>
          </div>
          <?php # check kalau display_name takde, papar 'Anonymous' sebagai default ?>
          <p class="review-date">Shown publicly as: <strong><?= htmlspecialchars($existingReview['display_name'] ?: 'Anonymous') ?></strong></p>
          <?php # check kalau ada comment, baru papar, calling function nl2br() untuk tukar baris baru jadi <br> ?>
          <?php if ($existingReview['comment']): ?>
            <p class="review-comment"><?= nl2br(htmlspecialchars($existingReview['comment'])) ?></p>
          <?php endif; ?>
          <?php # check kalau ada image_path, baru papar gambar review ?>
          <?php if ($existingReview['image_path']): ?>
            <img class="review-photo" src="../<?= htmlspecialchars($existingReview['image_path']) ?>" alt="Room photo shared by the guest">
          <?php endif; ?>
          <?php # calling function date()+strtotime() untuk format tarikh review ?>
          <p class="review-date">Reviewed on <?= htmlspecialchars(date('d M Y', strtotime($existingReview['review_date']))) ?></p>
        <?php else: ?>
          <h3>Leave a Review</h3>
          <?php # check kalau ada reviewError, papar mesej error kat atas form ?>
          <?php if ($reviewError): ?>
            <p class="lookup-error"><?= htmlspecialchars($reviewError) ?></p>
          <?php endif; ?>
          <form method="post" class="review-form" id="review-form" enctype="multipart/form-data">
            <?php # calling function csrf_field() untuk bina hidden input token, elak CSRF masa submit review ?>
            <?= csrf_field() ?>
            <input type="hidden" name="form" value="review">
            <input type="hidden" name="ref" value="<?= (int) $booking['booking_id'] ?>">
            <input type="hidden" name="phone" value="<?= htmlspecialchars($booking['phone']) ?>">
            <div class="rating-picker" role="radiogroup" aria-label="Rating">
              <?php # loop dari 5 turun ke 1 untuk papar pilihan bintang rating (5 star tersusun dulu sbb reverse untuk css) ?>
              <?php for ($i = 5; $i >= 1; $i--): ?>
                <?php # check kalau $i ni 5, set default checked untuk rating penuh ?>
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
              <p class="review-photo-label">Add a room photo (optional).</p>
              <div class="review-photo-controls">
                <button type="button" class="photo-btn" id="review-upload-btn">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                  Upload Image
                </button>
                <button type="button" class="photo-btn" id="review-camera-btn">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                  Open Camera
                </button>
              </div>
              <input type="file" id="review-file-input" name="review_image" accept="image/*" hidden>
              <img id="review-image-preview" class="review-photo-preview" hidden alt="Selected room photo preview">
              <button type="button" class="photo-btn photo-btn-outline" id="review-remove-btn" hidden>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6M14 11v6"></path></svg>
                Remove Photo
              </button>
              <p id="review-verify-status" class="review-verify-status" hidden></p>
            </div>

            <button type="submit" class="lookup-submit">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
              Submit Review
            </button>
          </form>

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

          <div class="photo-lightbox" id="review-lightbox">
            <button type="button" class="photo-lightbox-close" id="review-lightbox-close" aria-label="Close">&times;</button>
            <img id="review-lightbox-img" src="" alt="Room photo, enlarged">
            <button type="button" class="photo-lightbox-back" id="review-lightbox-back">Back to Homepage</button>
          </div>

          <?php
          # assign path fail js review-photo.js ke $reviewPhotoJsPath
          $reviewPhotoJsPath = __DIR__ . '/../../assets/js/review-photo.js';
          # calling function is_file() untuk check fail tu wujud, calling function filemtime() untuk ambil masa fail last update, supaya browser cache-bust bila fail berubah
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

  <section class="lookup-section">
    <div class="container">
      <div class="lookup-card">
        <?php # check kalau ada lookupError (contoh phone number tak jumpa), papar mesej error ?>
        <?php if ($lookupError): ?>
          <p class="lookup-error"><?= htmlspecialchars($lookupError) ?></p>
        <?php endif; ?>

        <form method="get">
          <div class="form-field">
            <label for="phone">
              <span class="form-field-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="2" width="10" height="20" rx="2" ry="2"></rect><line x1="11" y1="18" x2="13" y2="18"></line></svg>
              </span>
              Phone Number
            </label>
            <input type="tel" id="phone" name="phone" placeholder="The phone number used to book" value="<?= htmlspecialchars($phone) ?>" required>
          </div>
          <button type="submit" class="lookup-submit">
            View My Booking
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
          </button>
        </form>

        <p class="lookup-hint">Your booking details will be shown after you enter the correct phone number. Don't have one yet? <a href="villa.php" style="color:var(--orange-deep);font-weight:600;">Browse packages</a>.</p>
      </div>
    </div>
  </section>

  <?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
