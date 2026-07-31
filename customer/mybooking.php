<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$ref = isset($_GET['ref']) ? trim((string) $_GET['ref']) : '';
$phone = isset($_GET['phone']) ? trim((string) $_GET['phone']) : '';
$lookupAttempted = $ref !== '' || $phone !== '';
$lookupError = null;
$booking = null;
$items = [];

if ($lookupAttempted) {
    $refId = filter_var($ref, FILTER_VALIDATE_INT);

    if ($refId === false || $phone === '') {
        $lookupError = 'Please enter a valid booking reference and the phone number used to book.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT b.*, c.full_name, c.phone, c.plate_num
             FROM booking b
             JOIN customer c ON c.customer_id = b.customer_id
             WHERE b.booking_id = :ref AND c.phone = :phone'
        );
        $stmt->execute(['ref' => $refId, 'phone' => $phone]);
        $booking = $stmt->fetch();

        if (!$booking) {
            $lookupError = 'No booking found for that reference number and phone number. Please double-check and try again.';
        } else {
            $stmt = $pdo->prepare(
                'SELECT bi.quantity, bi.price, a.accommodation_name, a.accommodation_type,
                        a.price AS nightly_price, a.price_weekend AS nightly_price_weekend
                 FROM booking_item bi
                 JOIN accommodation a ON a.accommodation_id = bi.accommodation_id
                 WHERE bi.booking_id = :id'
            );
            $stmt->execute(['id' => $booking['booking_id']]);
            $items = $stmt->fetchAll();
        }
    }
}

$nights = 1;
$stay = ['weekday_nights' => 1, 'weekend_nights' => 0];
if ($booking) {
    $checkIn = new DateTime($booking['check_in']);
    $checkOut = new DateTime($booking['check_out']);
    $nights = max(1, $checkOut->diff($checkIn)->days);
    $stay = compute_stay_price(1, 1, $checkIn, $checkOut); // only need the night counts here
}

$base = '../';
$active = 'mybooking';
$pageTitle = 'MyBooking — Casadive Villa';
$pageCss = 'style/mybooking.css';
include __DIR__ . '/../includes/header.php';
?>

  <!-- PAGE TITLE -->
  <div class="container page-title">
    <h1>MyBooking</h1>
    <p>Enter your booking reference and the phone number you booked with to view or print your receipt.</p>
  </div>

  <?php if ($booking): ?>

  <!-- RECEIPT -->
  <section class="receipt-section">
    <div class="container">
      <div class="receipt-card">
        <div class="receipt-head">
          <div>
            <p class="brand">Casadive Villa</p>
            <p class="receipt-ref">Booking Reference #<?= (int) $booking['booking_id'] ?> &middot; Booked on <?= htmlspecialchars(date('d M Y', strtotime($booking['booking_date']))) ?></p>
          </div>
          <span class="status-badge status-<?= htmlspecialchars($booking['booking_status']) ?>"><?= htmlspecialchars(format_status($booking['booking_status'])) ?></span>
        </div>

        <div class="receipt-block">
          <h3>Guest Details</h3>
          <dl class="receipt-grid">
            <div><dt>Full Name</dt><dd><?= htmlspecialchars($booking['full_name']) ?></dd></div>
            <div><dt>Phone Number</dt><dd><?= htmlspecialchars($booking['phone']) ?></dd></div>
            <div><dt>Car Plate Number</dt><dd><?= htmlspecialchars($booking['plate_num'] ?: '—') ?></dd></div>
            <div><dt>Number of Guests</dt><dd><?= (int) $booking['total_guest'] ?></dd></div>
          </dl>
        </div>

        <div class="receipt-block">
          <h3>Stay Details</h3>
          <dl class="receipt-grid">
            <div><dt>Check-in</dt><dd><?= htmlspecialchars($booking['check_in']) ?></dd></div>
            <div><dt>Check-out</dt><dd><?= htmlspecialchars($booking['check_out']) ?></dd></div>
            <div><dt>Nights</dt><dd><?= $nights ?></dd></div>
            <div><dt>Accommodation</dt><dd><?= htmlspecialchars(implode(', ', array_column($items, 'accommodation_name')) ?: '—') ?></dd></div>
          </dl>
        </div>

        <?php if (!empty($booking['special_request'])): ?>
        <div class="receipt-block">
          <h3>Special Request</h3>
          <p class="receipt-request"><?= nl2br(htmlspecialchars($booking['special_request'])) ?></p>
        </div>
        <?php endif; ?>

        <div class="receipt-block">
          <h3>Price Breakdown</h3>
          <div class="receipt-rows">
            <?php foreach ($items as $item):
              $weekendRate = $item['nightly_price_weekend'] !== null ? (float) $item['nightly_price_weekend'] : (float) $item['nightly_price'];
              $rateLabel = $stay['weekend_nights'] > 0 && $weekendRate !== (float) $item['nightly_price']
                  ? "{$stay['weekday_nights']} weekday night" . ($stay['weekday_nights'] !== 1 ? 's' : '') . " &times; RM " . number_format((float) $item['nightly_price'], 2)
                    . " + {$stay['weekend_nights']} weekend night" . ($stay['weekend_nights'] !== 1 ? 's' : '') . " &times; RM " . number_format($weekendRate, 2)
                  : "RM " . number_format((float) $item['nightly_price'], 2) . " &times; {$nights} night" . ($nights > 1 ? 's' : '');
            ?>
              <div class="receipt-row">
                <span><?= htmlspecialchars($item['accommodation_name']) ?> (<?= $rateLabel ?>)</span>
                <span>RM <?= number_format((float) $item['price'], 2) ?></span>
              </div>
            <?php endforeach; ?>
            <div class="receipt-row total">
              <span>Total Price</span>
              <span>RM <?= number_format((float) $booking['total_amount'], 2) ?></span>
            </div>
            <div class="receipt-row">
              <span>Deposit (30%)</span>
              <span>RM <?= number_format((float) $booking['deposit_amount'], 2) ?></span>
            </div>
            <div class="receipt-row balance">
              <span>Balance Due</span>
              <span>RM <?= number_format((float) $booking['total_amount'] - (float) $booking['deposit_amount'], 2) ?></span>
            </div>
          </div>
        </div>

        <div class="receipt-actions">
          <button type="button" class="receipt-print" onclick="window.print()">Print Receipt</button>
          <a href="mybooking.php" class="receipt-back">Look Up Another Booking</a>
        </div>
      </div>
    </div>
  </section>

  <?php else: ?>

  <!-- LOOKUP FORM -->
  <section class="lookup-section">
    <div class="container">
      <div class="lookup-card">
        <?php if ($lookupError): ?>
          <p class="lookup-error"><?= htmlspecialchars($lookupError) ?></p>
        <?php endif; ?>

        <form method="get">
          <div class="form-field">
            <label for="ref">Booking Reference</label>
            <input type="text" id="ref" name="ref" placeholder="e.g. 12" value="<?= htmlspecialchars($ref) ?>" required>
          </div>
          <div class="form-field">
            <label for="phone">Phone Number</label>
            <input type="tel" id="phone" name="phone" placeholder="The phone number used to book" value="<?= htmlspecialchars($phone) ?>" required>
          </div>
          <button type="submit" class="lookup-submit">View My Booking</button>
        </form>

        <p class="lookup-hint">Your booking reference was shown after you completed a booking. Don't have one yet? <a href="villa.php" style="color:var(--orange-deep);font-weight:600;">Browse packages</a>.</p>
      </div>
    </div>
  </section>

  <?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
