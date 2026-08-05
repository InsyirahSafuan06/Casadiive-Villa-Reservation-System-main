<?php
/**
 * Processing page.
 * Records the deposit payment for a booking, then shows a brief "processing"
 * animation before handing off to sucess_payment.php.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$allowedBanks = ['Bank Islam', 'Maybank', 'CIMB Bank', 'Public Bank', 'RHB Bank', 'Hong Leong Bank'];

$bookingId = filter_var($_GET['booking_id'] ?? '', FILTER_VALIDATE_INT);
$method = $_GET['method'] ?? '';
$bank = trim((string) ($_GET['bank'] ?? ''));
$booking = null;
$errors = [];
$paid = false;

if (!in_array($method, ['qr', 'online_banking'], true)) {
    $method = '';
}
if ($method !== 'online_banking' || !in_array($bank, $allowedBanks, true)) {
    $bank = '';
}

if ($bookingId !== false) {
    $stmt = $pdo->prepare(
        'SELECT b.*, c.full_name, c.phone
         FROM booking b JOIN customer c ON c.customer_id = b.customer_id
         WHERE b.booking_id = :id'
    );
    $stmt->execute(['id' => $bookingId]);
    $booking = $stmt->fetch();

    if ($booking) {
        $stmt = $pdo->prepare(
            "SELECT payment_id FROM payment WHERE booking_id = :id AND payment_status = 'paid' LIMIT 1"
        );
        $stmt->execute(['id' => $bookingId]);
        $paid = (bool) $stmt->fetch();
    }
}

if (!$booking) {
    $errors[] = 'We couldn\'t find that booking. Please start again from the booking form.';
} elseif ($method === '') {
    $errors[] = 'Missing payment method. Please choose a payment method again.';
}

if ($booking && !$paid && !$errors) {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            "INSERT INTO payment (booking_id, deposit_paid, payment_method, payment_status)
             VALUES (:booking_id, :deposit_paid, :payment_method, 'paid')"
        );
        $stmt->execute([
            'booking_id' => $bookingId,
            'deposit_paid' => $booking['deposit_amount'],
            'payment_method' => $method,
        ]);

        $stmt = $pdo->prepare(
            "UPDATE booking SET booking_status = 'confirmed' WHERE booking_id = :id AND booking_status = 'pending'"
        );
        $stmt->execute(['id' => $bookingId]);

        $pdo->commit();
        $paid = true;
    } catch (Exception $e) {
        $pdo->rollBack();
        $errors[] = 'Something went wrong while recording your payment. Please try again.';
    }
}

$redirectUrl = $booking
    ? 'sucess_payment.php?ref=' . $bookingId . '&phone=' . urlencode($booking['phone'])
    : '';

$base = '../';
$active = '';
$pageTitle = 'Processing Payment — Casadive Villa';
$pageCss = 'style/process_payment.css';
include __DIR__ . '/../includes/header.php';
?>

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

  <?php if ($errors || !$paid): ?>

    <div class="container page-title">
      <h1>Payment Not Completed</h1>
    </div>
    <section class="payment-section">
      <div class="container">
        <div class="processing-card" style="text-align:center;">
          <?php foreach ($errors as $error): ?>
            <p class="process-note" style="color:#9a3226;"><?= htmlspecialchars($error) ?></p>
          <?php endforeach; ?>
          <a href="<?= $booking ? 'payment.php?booking_id=' . $bookingId : 'bookingform.php' ?>" class="process-btn" style="max-width:320px;margin:20px auto 0;">Back to Payment</a>
        </div>
      </div>
    </section>

  <?php else: ?>

    <div class="container page-title">
      <h1>Processing Payment</h1>
      <p>Please wait while we are processing your payment.</p>
    </div>

    <!-- BIG SPINNER -->
    <div class="spinner-hero" role="status" aria-live="polite">
      <div class="spinner-ring" aria-hidden="true"></div>
      <h2>Verifying your transaction...</h2>
      <p>This may take a few seconds.</p>
    </div>

    <section class="payment-section">
      <div class="container">

        <!-- STEP CHECKLIST -->
        <div class="processing-card">
          <ul class="process-steps">
            <li class="process-step is-done">
              <span class="step-icon">
                <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M4 10.5l3.5 3.5L16 5.5" fill="none" stroke="#F5F5F5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </span>
              <span class="step-text"><?= $method === 'online_banking' ? 'Connecting to ' . htmlspecialchars($bank ?: 'your bank') : 'Connecting to payment gateway' ?></span>
            </li>
            <li class="process-step is-done">
              <span class="step-icon">
                <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M4 10.5l3.5 3.5L16 5.5" fill="none" stroke="#F5F5F5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </span>
              <span class="step-text">Verifying payment details</span>
            </li>
            <li class="process-step is-active">
              <span class="step-icon">
                <span class="mini-spinner" aria-hidden="true"></span>
              </span>
              <span class="step-text">Processing payment</span>
            </li>
          </ul>
          <p class="process-note">Please do not refresh or close this page</p>
        </div>

        <!-- WARNING BANNER -->
        <div class="process-alert">
          <span class="alert-icon" aria-hidden="true">!</span>
          <div class="alert-text">
            <p class="alert-strong">Do not close this window or click the back button.</p>
            <p>You will be redirected automatically once the payment is complete.</p>
          </div>
        </div>

      </div>
    </section>

    <script>
      setTimeout(function () {
        window.location.href = <?= json_encode($redirectUrl) ?>;
      }, 2200);
    </script>

  <?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
