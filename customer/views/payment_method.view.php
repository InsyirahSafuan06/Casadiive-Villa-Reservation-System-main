<?php include __DIR__ . '/../../includes/header.php'; ?>

  <!-- STEP BAR -->
  <div class="step-bar" aria-label="Booking progress">
    <div class="step">
      <span class="step-circle">1</span>
      <span class="step-label">Details</span>
    </div>
    <span class="step-line"></span>
    <div class="step is-active">
      <span class="step-circle">2</span>
      <span class="step-label">Payment</span>
    </div>
    <span class="step-line"></span>
    <div class="step">
      <span class="step-circle">3</span>
      <span class="step-label">Complete</span>
    </div>
  </div>

  <div class="container page-title">
    <div class="page-title-heading">
      <span class="page-title-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
      </span>
      <div>
        <h1>Payment Confirmation</h1>
        <p>Please confirm your payment details before proceeding.</p>
      </div>
    </div>
  </div>

  <section class="payment-section">
    <div class="container">

      <?php if (!$booking): ?>
        <div class="payment-card" style="text-align:center;">
          <p class="payment-error">We couldn't find that booking. Please start again from the booking form.</p>
          <a href="bookingform.php" class="pay-btn" style="max-width:320px;margin:24px auto 0;">Back to Booking Details</a>
        </div>

      <?php elseif ($paid): ?>
        <div class="payment-card" style="text-align:center;">
          <p class="payment-error" style="background:#e3f7e8;border-color:#bfe6c9;color:#1e6b34;">This booking has already been paid for.</p>
          <a href="sucess_payment.php?ref=<?= $bookingId ?>&phone=<?= urlencode($booking['phone']) ?>" class="pay-btn" style="max-width:320px;margin:24px auto 0;">View Confirmation</a>
        </div>

      <?php elseif ($method === ''): ?>
        <div class="payment-card" style="text-align:center;">
          <p class="payment-error">Please choose a payment method first.</p>
          <a href="payment.php?booking_id=<?= $bookingId ?>" class="pay-btn" style="max-width:320px;margin:24px auto 0;">Back to Payment</a>
        </div>

      <?php else: ?>

        <?php if ($errors): ?>
          <div class="payment-error" style="max-width:951px;margin:0 auto 24px;">
            <?php foreach ($errors as $error): ?>
              <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form class="payment-card" method="post" id="confirm-form" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <input type="hidden" name="booking_id" value="<?= $bookingId ?>">
          <input type="hidden" name="method" value="<?= htmlspecialchars($method) ?>">

          <div class="booking-summary">
            <h2 class="summary-heading">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
              Booking Summary
            </h2>

            <div class="summary-row">
              <span class="summary-label"><?= htmlspecialchars(implode(', ', array_column($items, 'accommodation_name')) ?: '—') ?></span>
              <span class="summary-value"><?= $nights ?> night<?= $nights === 1 ? '' : 's' ?></span>
            </div>
            <div class="summary-row">
              <span class="summary-label">Check In</span>
              <span class="summary-value"><?= htmlspecialchars($booking['check_in']) ?></span>
            </div>
            <div class="summary-row">
              <span class="summary-label">Check Out</span>
              <span class="summary-value"><?= htmlspecialchars($booking['check_out']) ?></span>
            </div>
            <div class="summary-row">
              <span class="summary-label">Guests</span>
              <span class="summary-value"><?= (int) $booking['total_guest'] ?></span>
            </div>
            <div class="summary-row">
              <span class="summary-label">Booking ID</span>
              <span class="summary-value"><?= htmlspecialchars(format_booking_ref((int) $bookingId)) ?></span>
            </div>
            <div class="summary-row">
              <span class="summary-label">Customer Name</span>
              <span class="summary-value"><?= htmlspecialchars($booking['full_name']) ?></span>
            </div>
            <div class="summary-row">
              <span class="summary-label">Payment Method</span>
              <span class="summary-value"><?= htmlspecialchars($methodLabels[$method]) ?></span>
            </div>
            <div class="summary-row summary-total">
              <span class="summary-label">Amount to Pay</span>
              <span class="summary-value">RM <?= number_format(booking_grand_total($booking), 2) ?></span>
            </div>
          </div>

          <?php if ($method === 'qr'): ?>
            <div class="qr-payment-block">
              <h2 class="summary-heading">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect><path d="M14 14h3v3h-3zM20 14v3M14 20h3M20 20v.01"></path></svg>
                Scan &amp; Pay
              </h2>

              <div class="qr-code-frame" id="qr-code-frame">
                <img src="<?= htmlspecialchars($base) ?>assets/images/qr-payment.jpg" alt="Touch 'n Go eWallet payment QR code" id="qr-code-img">
                <div class="qr-code-fallback">
                  <svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM20 14v3M14 20h3M20 20v.01"/></svg>
                  <span>QR code is not available right now.<br>Please contact us on WhatsApp to pay.</span>
                </div>
              </div>

              <p class="method-hint">Scan this QR with your banking app or eWallet to pay the amount above, then upload your payment receipt/screenshot below.</p>

              <label class="upload-dropzone" id="payment-proof-dropzone" for="payment-proof-input">
                <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4M12 4 7 9M12 4l5 5"/><path d="M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/></svg>
                <span class="upload-dropzone-title">Click to upload payment receipt</span>
                <span class="upload-dropzone-hint">JPG, PNG or WEBP &middot; max 5MB</span>
                <span class="upload-filename" id="payment-proof-filename" hidden></span>
              </label>
              <input type="file" id="payment-proof-input" name="payment_proof" accept=".jpg,.jpeg,.png,.webp,image/*" required hidden>

              <script>
                (function () {
                  var input = document.getElementById('payment-proof-input');
                  var dropzone = document.getElementById('payment-proof-dropzone');
                  var filenameEl = document.getElementById('payment-proof-filename');
                  input.addEventListener('change', function () {
                    var file = input.files && input.files[0];
                    if (!file) {
                      dropzone.classList.remove('has-file');
                      filenameEl.hidden = true;
                      return;
                    }
                    dropzone.classList.add('has-file');
                    filenameEl.hidden = false;
                    filenameEl.textContent = '✓ ' + file.name;
                  });

                  var qrFrame = document.getElementById('qr-code-frame');
                  var qrImg = document.getElementById('qr-code-img');
                  qrImg.addEventListener('error', function () {
                    qrFrame.classList.add('is-broken');
                  });
                })();
              </script>
            </div>
          <?php endif; ?>

          <label class="confirm-check">
            <input type="checkbox" name="confirm" required>
            <span>I confirm that the payment details above are correct.</span>
          </label>

          <div class="confirm-actions">
            <button type="submit" class="pay-btn">
              <?php if ($method === 'qr'): ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V4M12 4 7 9M12 4l5 5"></path><path d="M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"></path></svg>
                Submit Payment Proof
              <?php else: ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                Confirm Payment
              <?php endif; ?>
            </button>
            <a href="payment.php?booking_id=<?= $bookingId ?>" class="pay-btn pay-btn-outline">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
              Cancel &amp; Go Back
            </a>
          </div>
        </form>

      <?php endif; ?>
    </div>
  </section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
