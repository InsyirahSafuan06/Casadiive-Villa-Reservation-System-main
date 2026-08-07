<?php
/**
 * Halaman pembayaran.
 * Fail ini mengendalikan langkah pembayaran deposit bagi satu tempahan dan sahkan transaksi tersebut.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$bookingId = filter_var($_POST['booking_id'] ?? $_GET['booking_id'] ?? '', FILTER_VALIDATE_INT); // boleh datang dari URL atau borang
$booking = null;
$items = [];
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
            'SELECT bi.price, a.accommodation_name, a.accommodation_type
             FROM booking_item bi
             JOIN accommodation a ON a.accommodation_id = bi.accommodation_id
             WHERE bi.booking_id = :id'
        );
        $stmt->execute(['id' => $bookingId]);
        $items = $stmt->fetchAll();

        // check dulu — elak pelanggan bayar dua kali untuk tempahan yang sama
        $stmt = $pdo->prepare(
            "SELECT payment_id FROM payment WHERE booking_id = :id AND payment_status = 'paid' LIMIT 1"
        );
        $stmt->execute(['id' => $bookingId]);
        $paid = (bool) $stmt->fetch();
    }
}

// page ni cuma untuk pelanggan PILIH kaedah bayaran (QR atau online banking) — rekod
// pembayaran sebenar jadi kemudian dalam payment_method.php / process_payment.php
if ($booking && !$paid && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $method = $_POST['method'] ?? '';
    if (!in_array($method, ['qr', 'online_banking'], true)) {
        $errors[] = 'Please choose a payment method.';
    }

    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please try again.';
    }

    if (!$errors) {
        // hantar terus ke page seterusnya, bawa sekali kaedah & bank yang dia pilih
        $bank = $method === 'online_banking' ? trim((string) ($_POST['bank'] ?? '')) : '';
        header('Location: payment_method.php?booking_id=' . $bookingId . '&method=' . urlencode($method) . '&bank=' . urlencode($bank));
        exit;
    }
}

$base = '../'; // page ni dalam folder customer/, naik satu tahap untuk pergi root
$active = ''; // takde menu navbar yang perlu di-highlight untuk page ni
$pageTitle = 'Payment — Casadive Villa';
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

<?php include __DIR__ . '/../includes/footer.php'; ?>

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
