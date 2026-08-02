<?php
/**
 * Bank authorization page.
 * This page redirects the user for the selected online banking payment method.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$allowedBanks = ['Bank Islam', 'Maybank', 'CIMB Bank', 'Public Bank', 'RHB Bank', 'Hong Leong Bank'];

$bookingId = filter_var($_POST['booking_id'] ?? $_GET['booking_id'] ?? '', FILTER_VALIDATE_INT);
$bank = trim((string) ($_POST['bank'] ?? $_GET['bank'] ?? ''));
if (!in_array($bank, $allowedBanks, true)) {
    $bank = 'your bank';
}

$booking = null;
$errors = [];
$paid = false;

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

if ($booking && !$paid && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please try again.';
    }

    $action = $_POST['action'] ?? '';

    if (!$errors && $action === 'cancel') {
        header('Location: payment.php?booking_id=' . $bookingId);
        exit;
    }

    if (!$errors && $action === 'authorize') {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "INSERT INTO payment (booking_id, deposit_paid, payment_method, payment_status)
                 VALUES (:booking_id, :deposit_paid, 'online_banking', 'paid')"
            );
            $stmt->execute([
                'booking_id' => $bookingId,
                'deposit_paid' => $booking['deposit_amount'],
            ]);

            $stmt = $pdo->prepare(
                "UPDATE booking SET booking_status = 'confirmed' WHERE booking_id = :id AND booking_status = 'pending'"
            );
            $stmt->execute(['id' => $bookingId]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Something went wrong while recording your payment. Please try again.';
        }

        if (!$errors) {
            header('Location: complete.php?ref=' . $bookingId . '&phone=' . urlencode($booking['phone']));
            exit;
        }
    }
}

$base = '../';
$active = '';
$pageTitle = 'Bank Authorization — Casadive Villa';
$pageCss = 'style/payment.css';
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
    <h1>Bank Authorization</h1>
    <p>You're being redirected to <?= htmlspecialchars($bank) ?> to authorize this payment.</p>
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
          <a href="complete.php?ref=<?= $bookingId ?>&phone=<?= urlencode($booking['phone']) ?>" class="pay-btn" style="max-width:320px;margin:24px auto 0;">View Confirmation</a>
        </div>

      <?php else: ?>

        <?php if ($errors): ?>
          <div class="payment-error" style="max-width:951px;margin:0 auto 24px;">
            <?php foreach ($errors as $error): ?>
              <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div class="payment-card bank-authorize-card">
          <div class="bank-authorize-brand"><?= htmlspecialchars($bank) ?></div>
          <p class="bank-authorize-lead">
            Please review and authorize this payment. This is a simulated bank authorization screen —
            no real funds are moved.
          </p>

          <div class="amount-due">
            <span>Amount to Authorize — Booking #<?= $bookingId ?></span>
            <span>RM <?= number_format((float) $booking['deposit_amount'], 2) ?></span>
          </div>

          <form method="post" class="bank-authorize-actions">
            <?= csrf_field() ?>
            <input type="hidden" name="booking_id" value="<?= $bookingId ?>">
            <input type="hidden" name="bank" value="<?= htmlspecialchars($bank) ?>">
            <button type="submit" name="action" value="authorize" class="pay-btn">Authorize Payment</button>
            <button type="submit" name="action" value="cancel" class="pay-btn pay-btn-outline">Cancel & Go Back</button>
          </form>
        </div>

      <?php endif; ?>
    </div>
  </section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
