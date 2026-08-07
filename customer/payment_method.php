<?php
/**
 * Halaman pengesahan pembayaran.
 * Paparkan tempahan dan kaedah pembayaran yang dipilih pada payment.php untuk semakan
 * sebelum deposit benar-benar dicaj dalam process_payment.php.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$allowedBanks = ['Bank Islam', 'Maybank', 'CIMB Bank', 'Public Bank', 'RHB Bank', 'Hong Leong Bank'];
$methodLabels = ['qr' => 'QR / DuitNow', 'online_banking' => 'FPX Online Banking'];

$bookingId = filter_var($_POST['booking_id'] ?? $_GET['booking_id'] ?? '', FILTER_VALIDATE_INT);
$method = $_POST['method'] ?? $_GET['method'] ?? '';
$bank = trim((string) ($_POST['bank'] ?? $_GET['bank'] ?? ''));
$booking = null;
$items = [];
$errors = [];
$paid = false;
$nights = 0;

// Nilai-nilai ini datang dari URL yang dibina oleh payment.php, tetapi sahkan semula di sini juga —
// jangan sekali-kali percaya nilai hanya kerana ia tiba melalui redirect "Location:".
if (!in_array($method, ['qr', 'online_banking'], true)) {
    $method = '';
}
if ($method === 'online_banking') {
    if (!in_array($bank, $allowedBanks, true)) {
        $bank = '';
    }
} else {
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
            'SELECT bi.price, a.accommodation_name
             FROM booking_item bi
             JOIN accommodation a ON a.accommodation_id = bi.accommodation_id
             WHERE bi.booking_id = :id'
        );
        $stmt->execute(['id' => $bookingId]);
        $items = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            "SELECT payment_id FROM payment WHERE booking_id = :id AND payment_status = 'paid' LIMIT 1"
        );
        $stmt->execute(['id' => $bookingId]);
        $paid = (bool) $stmt->fetch();

        $checkIn = DateTime::createFromFormat('Y-m-d', (string) $booking['check_in']);
        $checkOut = DateTime::createFromFormat('Y-m-d', (string) $booking['check_out']);
        if ($checkIn && $checkOut) {
            $nights = (int) $checkIn->diff($checkOut)->days;
        }
    }
}

// Pelanggan mesti tanda kotak "I confirm" sebelum kita teruskan untuk benar-benar
// mengecaj deposit — halaman ini tidak pernah sentuh pangkalan data sendiri.
if ($booking && !$paid && $method !== '' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please try again.';
    }
    if (!$errors && !($_POST['confirm'] ?? false)) {
        $errors[] = 'Please confirm the payment details before proceeding.';
    }

    if (!$errors) {
        header('Location: process_payment.php?booking_id=' . $bookingId . '&method=' . urlencode($method) . '&bank=' . urlencode($bank));
        exit;
    }
}

$base = '../';
$active = '';
$pageTitle = 'Payment Confirmation — Casadive Villa';
$pageCss = 'style/payment_method.css';
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

        <form class="payment-card" method="post" id="confirm-form">
          <?= csrf_field() ?>
          <input type="hidden" name="booking_id" value="<?= $bookingId ?>">
          <input type="hidden" name="method" value="<?= htmlspecialchars($method) ?>">
          <input type="hidden" name="bank" value="<?= htmlspecialchars($bank) ?>">

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
              <span class="summary-value">#<?= $bookingId ?></span>
            </div>
            <div class="summary-row">
              <span class="summary-label">Customer Name</span>
              <span class="summary-value"><?= htmlspecialchars($booking['full_name']) ?></span>
            </div>
            <?php if ($method === 'online_banking'): ?>
            <div class="summary-row">
              <span class="summary-label">Bank</span>
              <span class="summary-value"><?= htmlspecialchars($bank ?: 'Not selected') ?></span>
            </div>
            <?php endif; ?>
            <div class="summary-row">
              <span class="summary-label">Payment Method</span>
              <span class="summary-value"><?= htmlspecialchars($methodLabels[$method]) ?></span>
            </div>
            <div class="summary-row summary-total">
              <span class="summary-label">Amount to Pay</span>
              <span class="summary-value">RM <?= number_format((float) $booking['deposit_amount'], 2) ?></span>
            </div>
          </div>

          <label class="confirm-check">
            <input type="checkbox" name="confirm" required>
            <span>I confirm that the payment details above are correct.</span>
          </label>

          <div class="confirm-actions">
            <button type="submit" class="pay-btn">Confirm Payment</button>
            <a href="payment.php?booking_id=<?= $bookingId ?>" class="pay-btn pay-btn-outline">Cancel &amp; Go Back</a>
          </div>
        </form>

      <?php endif; ?>
    </div>
  </section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
