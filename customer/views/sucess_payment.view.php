<?php include __DIR__ . '/../../includes/header.php'; ?>

  <div class="step-bar" aria-label="Booking progress">
    <div class="step">
      <span class="step-circle">1</span>
      <span class="step-label">Details</span>
    </div>
    <span class="step-line"></span>
    <div class="step">
      <span class="step-circle">2</span>
      <span class="step-label">Payment</span>
    </div>
    <span class="step-line"></span>
    <div class="step is-active">
      <span class="step-circle">3</span>
      <span class="step-label">Complete</span>
    </div>
  </div>

  <?php # check kalau $booking kosong/tak jumpa untuk suruh user cari balik kat mybooking ?>
  <?php if (!$booking): ?>

    <div class="container page-title">
      <h1>Payment Successful!</h1>
    </div>
    <section class="payment-section">
      <div class="container">
        <div class="success-card">
          <p class="success-lead">We couldn't find that booking. Please look it up from MyBooking.</p>
          <div class="success-actions">
            <a href="mybooking.php" class="success-btn">Go to MyBooking</a>
          </div>
        </div>
      </div>
    </section>

  <?php else: ?>

    <div class="purchase-modal-overlay" id="purchase-modal-overlay">
      <div class="purchase-modal" role="dialog" aria-modal="true" aria-labelledby="purchase-modal-title">
        <button type="button" class="purchase-modal-close" id="purchase-modal-close" aria-label="Close">&times;</button>
        <span class="purchase-modal-leaf purchase-modal-leaf-bl" aria-hidden="true"></span>
        <span class="purchase-modal-leaf purchase-modal-leaf-br" aria-hidden="true"></span>
        <svg class="purchase-modal-icon" viewBox="0 0 120 110" aria-hidden="true">
          <path d="M100 20l4-4M108 24h6M104 15v6" stroke="var(--orange-deep)" stroke-width="2.5" stroke-linecap="round" fill="none"/>
          <rect x="34" y="16" width="52" height="72" rx="8" fill="#F3D9B1" stroke="var(--brown-price)" stroke-width="3"/>
          <rect x="48" y="8" width="24" height="16" rx="4" fill="var(--brown-price)"/>
          <circle cx="60" cy="16" r="3" fill="#F3D9B1"/>
          <rect x="42" y="34" width="8" height="8" rx="2" fill="var(--orange)"/>
          <rect x="54" y="36" width="24" height="4" rx="2" fill="var(--brown-price)" opacity=".5"/>
          <rect x="42" y="50" width="8" height="8" rx="2" fill="var(--orange)"/>
          <rect x="54" y="52" width="24" height="4" rx="2" fill="var(--brown-price)" opacity=".5"/>
          <rect x="42" y="66" width="8" height="8" rx="2" fill="var(--orange)"/>
          <rect x="54" y="68" width="18" height="4" rx="2" fill="var(--brown-price)" opacity=".5"/>
          <circle cx="82" cy="78" r="18" fill="var(--orange-deep)" stroke="var(--cream)" stroke-width="4"/>
          <path d="M74 78l6 6 12-14" stroke="#fff" stroke-width="3.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <h2 id="purchase-modal-title">Thanks for Purchase!</h2>
        <p>Your booking has been successfully confirmed. We look forward to welcoming you soon!</p>
        <div class="purchase-modal-customer">
          <span class="purchase-modal-avatar" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
          </span>
          <span>
            <span class="purchase-modal-customer-label">Customer Name</span>
            <span class="purchase-modal-customer-name"><?= htmlspecialchars($booking['full_name']) ?></span>
          </span>
        </div>
        <button type="button" class="purchase-modal-ok" id="purchase-modal-ok">OK</button>
      </div>
    </div>
    <script src="../assets/js/simple-modal.js"></script>
    <script>
      initSimpleModal('purchase-modal-overlay', { closeIds: ['purchase-modal-close', 'purchase-modal-ok'] });
    </script>

    <div class="container page-title">
      <h1>Payment Successful!</h1>
      <p>Your booking has been confirmed. A confirmation email has been sent to you.</p>
    </div>

    <section class="payment-section">
      <div class="container">
        <div class="success-card">

          <div class="success-check" aria-hidden="true">
            <svg viewBox="0 0 20 20"><path d="M4 10.5l3.5 3.5L16 5.5" fill="none" stroke="#F5F5F5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </div>

          <?php # ambil deposit_paid kalau ada, kalau tak calling function booking_grand_total() that assign to $amountPaid ?>
          <?php $amountPaid = (float) ($payment['deposit_paid'] ?? booking_grand_total($booking)); ?>
          <p class="success-lead">
            Thank you, <strong><?= htmlspecialchars($booking['full_name']) ?></strong> — your payment has been
            <?php # calling function format_booking_ref() & htmlspecialchars() untuk papar nombor rujukan booking yang selamat ?>
            received and booking <strong><?= htmlspecialchars(format_booking_ref((int) $booking['booking_id'])) ?></strong> is now
            <?php # calling function format_status() & htmlspecialchars() untuk papar status booking dalam bentuk senang dibaca ?>
            <strong><?= htmlspecialchars(format_status($booking['booking_status'])) ?></strong>.
          </p>

          <dl class="success-grid">
            <?php # calling function strtotime() & date() untuk tukar tarikh check_in ke format d/m/Y ?>
            <div><dt>Check-in</dt><dd><?= htmlspecialchars(date('d/m/Y', strtotime($booking['check_in']))) ?></dd></div>
            <?php # calling function strtotime() & date() untuk tukar tarikh check_out ke format d/m/Y ?>
            <div><dt>Check-out</dt><dd><?= htmlspecialchars(date('d/m/Y', strtotime($booking['check_out']))) ?></dd></div>
            <?php # calling function array_column() & implode() untuk gabung semua nama accommodation jadi satu string, kalau kosong papar '—' ?>
            <div><dt>Accommodation</dt><dd><?= htmlspecialchars(implode(', ', array_column($items, 'accommodation_name')) ?: '—') ?></dd></div>
            <?php # check kalau $payment wujud untuk papar label method, kalau tak papar '—' ?>
            <div><dt>Payment Method</dt><dd><?= $payment ? htmlspecialchars($methodLabels[$payment['payment_method']] ?? 'Other') : '—' ?></dd></div>
            <?php # calling function number_format() untuk papar $amountPaid dgn 2 titik perpuluhan ?>
            <div><dt>Amount Paid</dt><dd>RM <?= number_format($amountPaid, 2) ?></dd></div>
            <?php # calling function booking_grand_total() & number_format() untuk kira & papar jumlah keseluruhan ?>
            <div><dt>Total Amount</dt><dd>RM <?= number_format(booking_grand_total($booking), 2) ?></dd></div>
            <?php # kira baki belum bayar (grand total tolak amount paid) that assign display guna number_format() ?>
            <div><dt>Balance Due</dt><dd>RM <?= number_format(booking_grand_total($booking) - $amountPaid, 2) ?></dd></div>
            <?php # calling function format_booking_ref() & htmlspecialchars() untuk papar nombor rujukan booking yang selamat ?>
            <div><dt>Booking ID</dt><dd><?= htmlspecialchars(format_booking_ref((int) $booking['booking_id'])) ?></dd></div>
          </dl>

          <div class="policy-notice">
            <h3>Check-in Information</h3>
            <ul>
              <li><strong>Check-in:</strong> After 3.00 PM</li>
              <li><strong>Check-out:</strong> Before 12.00 PM</li>
              <li><strong>Check-in Method:</strong> Self Check-in</li>
            </ul>
          </div>

          <div class="success-actions">
            <?php # calling function urlencode() untuk selamatkan nombor phone sebelum letak dalam url ?>
            <a href="mybooking.php?ref=<?= (int) $booking['booking_id'] ?>&phone=<?= urlencode($booking['phone']) ?>" class="success-btn">View / Print Receipt</a>
            <a href="<?= $base ?>index.php" class="success-btn success-btn-outline">Back to Home</a>
          </div>
        </div>
      </div>
    </section>

  <?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
