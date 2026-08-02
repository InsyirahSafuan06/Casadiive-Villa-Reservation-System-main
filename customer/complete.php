<?php
/**
 * Completion page.
 * This page confirms that a booking has been processed successfully and shows the final details.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$bookingId = filter_var($_GET['ref'] ?? $_GET['booking_id'] ?? '', FILTER_VALIDATE_INT);
$phone = trim((string) ($_GET['phone'] ?? ''));
$booking = null;
$items = [];

if ($bookingId !== false && $phone !== '') {
    $stmt = $pdo->prepare(
        'SELECT b.*, c.full_name, c.phone
         FROM booking b JOIN customer c ON c.customer_id = b.customer_id
         WHERE b.booking_id = :id AND c.phone = :phone'
    );
    $stmt->execute(['id' => $bookingId, 'phone' => $phone]);
    $booking = $stmt->fetch();

    if ($booking) {
        $stmt = $pdo->prepare(
            'SELECT bi.price, a.accommodation_name
             FROM booking_item bi
             JOIN accommodation a ON a.accommodation_id = bi.accommodation_id
             WHERE bi.booking_id = :id'
        );
        $stmt->execute(['id' => $bookingId]);
        $items = $stmt->fetchAll();
    }
}

$base = '../';
$active = '';
$pageTitle = 'Booking Complete — Casadive Villa';
$pageCss = 'style/complete.css';
include __DIR__ . '/../includes/header.php';
?>

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

  <section class="complete-section">
    <div class="container">

      <?php if (!$booking): ?>
        <div class="complete-card" style="text-align:center;">
          <p class="complete-error">We couldn't find that booking. Please look it up from MyBooking.</p>
          <a href="mybooking.php" class="complete-btn" style="max-width:320px;margin:24px auto 0;">Go to MyBooking</a>
        </div>

      <?php else: ?>

        <div class="complete-card">
          <div class="complete-check" aria-hidden="true">&#10003;</div>
          <h1 class="complete-title">Booking Confirmed!</h1>
          <p class="complete-lead">
            Thank you, <?= htmlspecialchars($booking['full_name']) ?> — your deposit has been received and
            booking <strong>#<?= (int) $booking['booking_id'] ?></strong> is now
            <strong><?= htmlspecialchars(format_status($booking['booking_status'])) ?></strong>.
          </p>

          <dl class="complete-grid">
            <div><dt>Check-in</dt><dd><?= htmlspecialchars($booking['check_in']) ?></dd></div>
            <div><dt>Check-out</dt><dd><?= htmlspecialchars($booking['check_out']) ?></dd></div>
            <div><dt>Accommodation</dt><dd><?= htmlspecialchars(implode(', ', array_column($items, 'accommodation_name')) ?: '—') ?></dd></div>
            <div><dt>Deposit Paid</dt><dd>RM <?= number_format((float) $booking['deposit_amount'], 2) ?></dd></div>
            <div><dt>Total Amount</dt><dd>RM <?= number_format((float) $booking['total_amount'], 2) ?></dd></div>
            <div><dt>Balance Due</dt><dd>RM <?= number_format((float) $booking['total_amount'] - (float) $booking['deposit_amount'], 2) ?></dd></div>
          </dl>

          <div class="policy-notice">
            <h3>Check-in Information</h3>
            <ul>
              <li><strong>Check-in:</strong> After 3.00 PM</li>
              <li><strong>Check-out:</strong> Before 12.00 PM</li>
              <li><strong>Check-in Method:</strong> Self Check-in</li>
              <li>Please contact the admin one day before check-in to get the lock box code.</li>
            </ul>
          </div>

          <div class="complete-actions">
            <a href="mybooking.php?ref=<?= (int) $booking['booking_id'] ?>&phone=<?= urlencode($booking['phone']) ?>" class="complete-btn">View / Print Receipt</a>
            <a href="<?= $base ?>index.php" class="complete-btn complete-btn-outline">Back to Home</a>
          </div>
        </div>

      <?php endif; ?>
    </div>
  </section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
