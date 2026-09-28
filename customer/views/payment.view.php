<?php include __DIR__ . '/../../includes/header.php'; ?>

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
        <h1>Payment</h1>
        <p>Choose how you'd like to pay to confirm the booking.</p>
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

      <?php else: ?>

        <?php if ($errors): ?>
          <div class="payment-error" style="max-width:951px;margin:0 auto 24px;">
            <?php foreach ($errors as $error): ?>
              <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form class="payment-card" method="post" id="payment-form">
          <?= csrf_field() ?>
          <input type="hidden" name="booking_id" value="<?= $bookingId ?>">

          <h2 class="payment-title">Payment Method</h2>

          <div class="amount-due">
            <span>Amount Due — Booking <?= htmlspecialchars(format_booking_ref((int) $bookingId)) ?></span>
            <span>RM <?= number_format(booking_grand_total($booking), 2) ?></span>
          </div>

          <div class="method-options">
            <label class="method-option is-selected">
              <input type="radio" name="method" value="toyyibpay" checked>
              <svg class="method-option-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
              <span>Online Banking</span>
            </label>
            <label class="method-option">
              <input type="radio" name="method" value="qr">
              <svg class="method-option-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect><path d="M14 14h3v3h-3zM20 14v3M14 20h3M20 20v.01"></path></svg>
              <span>QR Payment</span>
            </label>
          </div>

          <div class="method-panel" id="panel-toyyibpay">
            <p class="method-hint">You'll be redirected to ToyyibPay to securely complete your payment via FPX online banking.</p>
          </div>

          <div class="method-panel" id="panel-qr" hidden>
            <p class="method-hint">Scan the QR code with your banking app or eWallet to pay, then upload your payment receipt on the next step.</p>
          </div>

          <button type="submit" class="pay-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
            Pay
          </button>
          <?php $backHref = ($items[0]['accommodation_type'] ?? '') === 'Campsite' ? 'campsite.php' : 'villa.php'; ?>
          <a href="<?= $backHref ?>" class="pay-btn pay-btn-outline" style="margin-top:14px;">Back to Booking</a>
        </form>

      <?php endif; ?>
    </div>
  </section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

<?php if ($booking && !$paid): ?>
<script>
  const options = document.querySelectorAll('.method-option');
  options.forEach(opt => {
    opt.addEventListener('click', () => {
      options.forEach(o => o.classList.remove('is-selected'));
      opt.classList.add('is-selected');
      const method = opt.querySelector('input').value;
      document.querySelectorAll('.method-panel').forEach(panel => {
        panel.hidden = panel.id !== `panel-${method}`;
      });
    });
  });
</script>
<?php endif; ?>
