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
    <h1>Payment</h1>
    <p>Choose how you'd like to pay your deposit to confirm the booking.</p>
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
            <span>Deposit Due — Booking #<?= $bookingId ?></span>
            <span>RM <?= number_format((float) $booking['deposit_amount'], 2) ?></span>
          </div>

          <div class="method-options">
            <label class="method-option is-selected">
              <input type="radio" name="method" value="qr" checked>
              <span>QR</span>
            </label>
            <label class="method-option">
              <input type="radio" name="method" value="online_banking">
              <span>Online Banking</span>
            </label>
          </div>

          <div class="method-panel" id="panel-qr">
            <div class="qr-box">Scan with your banking app</div>
            <p class="qr-hint">Scan the DuitNow QR to pay RM <?= number_format((float) $booking['deposit_amount'], 2) ?> directly from your bank or e-wallet app.</p>
          </div>

          <div class="method-panel" id="panel-online_banking" hidden>
            <label for="bank" class="bank-label">Select your bank</label>
            <div class="bank-select-wrap">
              <select id="bank" name="bank">
                <option>Bank Islam</option>
                <option>Maybank</option>
                <option>CIMB Bank</option>
                <option>Public Bank</option>
                <option>RHB Bank</option>
                <option>Hong Leong Bank</option>
              </select>
            </div>
          </div>

          <button type="submit" class="pay-btn">Pay</button>
        </form>

      <?php endif; ?>
    </div>
  </section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

<?php if ($booking && !$paid): ?>
<script>
  // klik QR atau Online Banking akan sorot pilihan tu dan tunjuk panel yang sepadan kat bawah
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
