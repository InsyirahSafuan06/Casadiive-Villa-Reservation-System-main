<?php
/**
 * Halaman berjaya / selesai.
 * Sahkan bahawa deposit sesuatu tempahan telah dibayar dan papar butiran akhir.
 * Ini adalah penghujung aliran pembayaran (payment.php -> payment_method.php -> process_payment.php -> sini).
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$methodLabels = ['qr' => 'QR / DuitNow', 'online_banking' => 'FPX Online Banking'];

// carian guna "no rujukan + no phone" sama macam mybooking.php — ni elak orang lain
// tengok booking orang lain just dengan teka-teka ID kat URL
$bookingId = filter_var($_GET['ref'] ?? $_GET['booking_id'] ?? '', FILTER_VALIDATE_INT);
$phone = trim((string) ($_GET['phone'] ?? ''));
$booking = null;
$items = [];
$payment = null;

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

        $stmt = $pdo->prepare(
            "SELECT payment_method, deposit_paid FROM payment
             WHERE booking_id = :id AND payment_status = 'paid'
             ORDER BY payment_id DESC LIMIT 1"
        );
        $stmt->execute(['id' => $bookingId]);
        $payment = $stmt->fetch(); // ambil rekod payment terbaru untuk booking ni
    }
}

$base = '../'; // page ni dalam folder customer/, naik satu tahap untuk pergi root
$active = ''; // takde menu navbar yang perlu di-highlight untuk page ni
$pageTitle = 'Payment Successful — Casadive Villa';
$pageCss = 'style/sucess_payment.css';
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

          <p class="success-lead">
            Thank you, <strong><?= htmlspecialchars($booking['full_name']) ?></strong> — your deposit has been
            received and booking <strong>#<?= (int) $booking['booking_id'] ?></strong> is now
            <strong><?= htmlspecialchars(format_status($booking['booking_status'])) ?></strong>.
          </p>

          <dl class="success-grid">
            <div><dt>Check-in</dt><dd><?= htmlspecialchars($booking['check_in']) ?></dd></div>
            <div><dt>Check-out</dt><dd><?= htmlspecialchars($booking['check_out']) ?></dd></div>
            <div><dt>Accommodation</dt><dd><?= htmlspecialchars(implode(', ', array_column($items, 'accommodation_name')) ?: '—') ?></dd></div>
            <div><dt>Payment Method</dt><dd><?= $payment ? htmlspecialchars($methodLabels[$payment['payment_method']] ?? $payment['payment_method']) : '—' ?></dd></div>
            <div><dt>Deposit Paid</dt><dd>RM <?= number_format((float) $booking['deposit_amount'], 2) ?></dd></div>
            <div><dt>Total Amount</dt><dd>RM <?= number_format((float) $booking['total_amount'], 2) ?></dd></div>
            <div><dt>Balance Due</dt><dd>RM <?= number_format((float) $booking['total_amount'] - (float) $booking['deposit_amount'], 2) ?></dd></div>
            <div><dt>Booking ID</dt><dd>#<?= (int) $booking['booking_id'] ?></dd></div>
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

<?php include __DIR__ . '/../includes/footer.php'; ?>
