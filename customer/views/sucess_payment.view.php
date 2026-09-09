<?php include __DIR__ . '/../../includes/header.php'; ?>

  <!-- STEP BAR -->
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

          <?php $amountPaid = (float) ($payment['deposit_paid'] ?? booking_grand_total($booking)); ?>
          <p class="success-lead">
            Thank you, <strong><?= htmlspecialchars($booking['full_name']) ?></strong> — your payment has been
            received and booking <strong><?= htmlspecialchars(format_booking_ref((int) $booking['booking_id'])) ?></strong> is now
            <strong><?= htmlspecialchars(format_status($booking['booking_status'])) ?></strong>.
          </p>

          <dl class="success-grid">
            <div><dt>Check-in</dt><dd><?= htmlspecialchars($booking['check_in']) ?></dd></div>
            <div><dt>Check-out</dt><dd><?= htmlspecialchars($booking['check_out']) ?></dd></div>
            <div><dt>Accommodation</dt><dd><?= htmlspecialchars(implode(', ', array_column($items, 'accommodation_name')) ?: '—') ?></dd></div>
            <div><dt>Payment Method</dt><dd><?= $payment ? htmlspecialchars($methodLabels[$payment['payment_method']] ?? $payment['payment_method']) : '—' ?></dd></div>
            <div><dt>Amount Paid</dt><dd>RM <?= number_format($amountPaid, 2) ?></dd></div>
            <div><dt>Total Amount</dt><dd>RM <?= number_format(booking_grand_total($booking), 2) ?></dd></div>
            <div><dt>Balance Due</dt><dd>RM <?= number_format(booking_grand_total($booking) - $amountPaid, 2) ?></dd></div>
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
            <a href="mybooking.php?ref=<?= (int) $booking['booking_id'] ?>&phone=<?= urlencode($booking['phone']) ?>" class="success-btn">View / Print Receipt</a>
            <a href="<?= $base ?>index.php" class="success-btn success-btn-outline">Back to Home</a>
          </div>
        </div>
      </div>
    </section>

  <?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
