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
    <h1>Payment Confirmation</h1>
    <p>Please confirm your payment details before proceeding.</p>
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
            <h2 class="summary-heading">Booking Summary</h2>

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
            <div class="qr-payment-block" style="text-align:center;margin:24px 0;">
              <h2 class="summary-heading">Scan &amp; Pay</h2>
              <img src="<?= htmlspecialchars($base) ?>assets/images/qr-payment.jpg" alt="Touch 'n Go eWallet payment QR code" style="max-width:280px;width:100%;margin:12px auto;display:block;border-radius:12px;">
              <p class="method-hint">Scan this QR with your banking app or eWallet to pay the amount above, then upload your payment receipt/screenshot below.</p>

              <label class="confirm-check" style="display:block;text-align:left;margin-top:16px;">
                <span>Upload payment receipt (JPG, PNG or WEBP, max 5MB)</span><br>
                <input type="file" name="payment_proof" accept=".jpg,.jpeg,.png,.webp,image/*" required style="margin-top:8px;">
              </label>
            </div>
          <?php endif; ?>

          <label class="confirm-check">
            <input type="checkbox" name="confirm" required>
            <span>I confirm that the payment details above are correct.</span>
          </label>

          <div class="confirm-actions">
            <button type="submit" class="pay-btn"><?= $method === 'qr' ? 'Submit Payment Proof' : 'Confirm Payment' ?></button>
            <a href="payment.php?booking_id=<?= $bookingId ?>" class="pay-btn pay-btn-outline">Cancel &amp; Go Back</a>
          </div>
        </form>

      <?php endif; ?>
    </div>
  </section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
