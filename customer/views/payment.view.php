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

      <?php # check kalau $booking kosong/tak jumpa untuk papar mesej booking tak wujud ?>
      <?php if (!$booking): ?>
        <div class="payment-card" style="text-align:center;">
          <p class="payment-error">We couldn't find that booking. Please start again from the booking form.</p>
          <a href="bookingform.php" class="pay-btn" style="max-width:320px;margin:24px auto 0;">Back to Booking Details</a>
        </div>

      <?php # check kalau $paid true (booking dah bayar) untuk papar mesej dah paid ?>
      <?php elseif ($paid): ?>
        <div class="payment-card" style="text-align:center;">
          <p class="payment-error" style="background:#e3f7e8;border-color:#bfe6c9;color:#1e6b34;">This booking has already been paid for.</p>
          <a href="sucess_payment.php?ref=<?= $bookingId ?>&phone=<?= urlencode($booking['phone']) ?>" class="pay-btn" style="max-width:320px;margin:24px auto 0;">View Confirmation</a>
        </div>

      <?php else: ?>

        <?php # check kalau ada $errors untuk papar mesej error kat atas form ?>
        <?php if ($errors): ?>
          <div class="payment-error" style="max-width:951px;margin:0 auto 24px;">
            <?php # loop setiap $error dalam $errors untuk papar semua mesej error satu-satu ?>
            <?php foreach ($errors as $error): ?>
              <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form class="payment-card" method="post" id="payment-form">
          <?php # calling function csrf_field() untuk papar hidden input token csrf, elak serangan CSRF ?>
          <?= csrf_field() ?>
          <input type="hidden" name="booking_id" value="<?= $bookingId ?>">

          <h2 class="payment-title">Payment Method</h2>

          <div class="amount-due">
            <?php # calling function format_booking_ref() & htmlspecialchars() untuk papar nombor rujukan booking yang selamat ?>
            <span>Amount Due — Booking <?= htmlspecialchars(format_booking_ref((int) $bookingId)) ?></span>
            <?php # calling function booking_grand_total() & number_format() untuk kira & papar jumlah bayaran perlu dibayar ?>
            <span>RM <?= number_format(booking_grand_total($booking), 2) ?></span>
          </div>

          <div class="method-options">
            <label class="method-option is-selected">
              <input type="radio" name="method" value="qr" checked>
              <svg class="method-option-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect><path d="M14 14h3v3h-3zM20 14v3M14 20h3M20 20v.01"></path></svg>
              <span>QR Payment</span>
            </label>
          </div>

          <div class="method-panel" id="panel-qr">
            <p class="method-hint">Scan the QR code with your banking app or eWallet to pay, then upload your payment receipt on the next step.</p>
          </div>

          <button type="submit" class="pay-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
            Pay
          </button>
          <?php # check accommodation_type item pertama sama dgn 'Campsite' that assign to $backHref untuk tentukan link balik ?>
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
