<?php include __DIR__ . '/../../includes/header.php'; ?>

  <div class="step-bar" aria-label="Booking progress">
    <div class="step">
      <span class="step-circle">1</span>
      <span class="step-label">Details</span>
    </div>
    <span class="step-line"></span>
    <div class="step<?= ($payment['payment_status'] ?? null) === 'paid' ? '' : ' is-active' ?>">
      <span class="step-circle">2</span>
      <span class="step-label">Payment</span>
    </div>
    <span class="step-line"></span>
    <div class="step<?= ($payment['payment_status'] ?? null) === 'paid' ? ' is-active' : '' ?>">
      <span class="step-circle">3</span>
      <span class="step-label">Complete</span>
    </div>
  </div>

  <?php # Show a successful state only after a manager-approved paid payment exists. ?>
  <?php if (!$booking): ?>

    <div class="container page-title">
      <h1>Booking Not Found</h1>
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

  <?php elseif (($payment['payment_status'] ?? null) === 'pending'): ?>

    <section class="payment-section payment-status-section">
      <div class="container payment-status-container">
        <div class="payment-status-card">
          <div class="payment-status-art" aria-hidden="true">
            <svg viewBox="0 0 360 280" role="img">
              <ellipse cx="173" cy="245" rx="142" ry="17" fill="#fbe8d5"/>
              <circle cx="172" cy="137" r="104" fill="#fff0df"/>
              <path d="M70 207c-27 14-37 27-35 37 1 6 20 9 51 9h174c25 0 42-3 43-10 2-10-15-20-43-31" fill="#f9e1c9"/>
              <path d="m91 69 151-12a13 13 0 0 1 14 12l13 147a13 13 0 0 1-12 14l-151 12a13 13 0 0 1-14-12L79 83a13 13 0 0 1 12-14Z" fill="#c9824f"/>
              <path d="m97 78 137-11a8 8 0 0 1 9 8l12 132a8 8 0 0 1-8 9l-137 11a8 8 0 0 1-9-8L89 87a8 8 0 0 1 8-9Z" fill="#fffaf4"/>
              <path d="m144 66-2-20a9 9 0 0 1 8-10l34-3a9 9 0 0 1 10 8l2 20" fill="#844525"/>
              <path d="m113 112 91-8m-88 29 70-6m-67 28 61-5" stroke="#e8d2bd" stroke-width="9" stroke-linecap="round"/>
              <circle cx="213" cy="171" r="45" fill="#9b5a32"/>
              <circle cx="213" cy="171" r="32" fill="#fff8ed"/>
              <path d="m237 204 36 38a10 10 0 0 0 15-13l-36-39" fill="#844525"/>
              <path d="m196 170 12 12 23-27" fill="none" stroke="#e57832" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="m273 88 11-10m-2 25 16-3m-28-17 2-16" stroke="#e57832" stroke-width="5" stroke-linecap="round"/>
              <path d="M51 203c-13-8-21-19-26-34m30 37c3-16 10-28 23-39" fill="none" stroke="#74816a" stroke-width="7" stroke-linecap="round"/>
              <ellipse cx="42" cy="188" rx="18" ry="8" transform="rotate(35 42 188)" fill="#74816a"/>
              <ellipse cx="67" cy="178" rx="18" ry="8" transform="rotate(-42 67 178)" fill="#74816a"/>
            </svg>
          </div>
          <div class="payment-status-content">
            <span class="payment-status-badge">
              <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
              Payment Under Review
            </span>
            <h1>Receipt Under Review</h1>
            <p class="payment-status-subtitle">Your payment is not confirmed until a manager reviews your receipt.</p>
            <div class="payment-status-note">
              <span class="payment-status-info" aria-hidden="true">i</span>
              <p><strong>Thank you, <?= htmlspecialchars($booking['full_name']) ?>.</strong><br>
                Your receipt for booking <?= htmlspecialchars(format_booking_ref((int) $booking['booking_id'])) ?> is awaiting review.</p>
            </div>
            <p class="payment-status-reminder">We’ll confirm your booking once it’s approved.</p>
            <div class="success-actions">
              <a href="mybooking.php?ref=<?= (int) $booking['booking_id'] ?>&phone=<?= urlencode($booking['phone']) ?>" class="success-btn">
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><path d="M7 3v4m10-4v4M3 10h18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                View Booking
                <span aria-hidden="true">→</span>
              </a>
              <a href="<?= $base ?>index.php" class="success-btn success-btn-outline">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-6v-7h-4v7H4a1 1 0 0 1-1-1z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                Back to Home
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>

  <?php elseif (($payment['payment_status'] ?? null) !== 'paid'): ?>

    <div class="container page-title">
      <h1>
        <?= ($payment['payment_status'] ?? null) === 'failed' && $booking['booking_status'] === 'cancelled'
          ? 'Booking Cancelled'
          : (($payment['payment_status'] ?? null) === 'failed' ? 'Payment Failed' : 'Payment Not Received') ?>
      </h1>
      <?php if (($payment['payment_status'] ?? null) !== 'failed'): ?>
        <p>No approved payment is recorded for this booking yet.</p>
      <?php endif; ?>
    </div>
    <section class="payment-section">
      <div class="container">
        <div class="success-card">
          <p class="success-lead">
            <?= ($payment['payment_status'] ?? null) === 'failed'
              ? ($booking['booking_status'] === 'cancelled'
                ? 'Your receipt was rejected and marked invalid. This booking has been cancelled.'
                : 'Your receipt was rejected. Please resubmit your payment.')
              : 'Your booking is not confirmed until your payment receipt has been reviewed and approved.' ?>
          </p>
          <div class="success-actions">
            <?php if (($payment['payment_status'] ?? null) === 'failed' && $booking['booking_status'] === 'cancelled'): ?>
              <a href="mybooking.php?ref=<?= (int) $booking['booking_id'] ?>&phone=<?= urlencode($booking['phone']) ?>" class="success-btn">View Receipt</a>
            <?php else: ?>
              <a href="payment.php?booking_id=<?= (int) $booking['booking_id'] ?>" class="success-btn">Submit Payment Proof</a>
            <?php endif; ?>
            <a href="<?= $base ?>index.php" class="success-btn success-btn-outline">Back to Home</a>
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
