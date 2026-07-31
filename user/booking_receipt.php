<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login(['admin', 'staff']);

$currentUser = current_user();
$bookingId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$bookingId) {
    header('Location: ' . ($currentUser['role'] === 'admin' ? 'admin_dashboard.php' : 'staff_dashboard.php'));
    exit;
}

$stmt = $pdo->prepare(
    'SELECT b.*, c.full_name, c.phone, c.plate_num
     FROM booking b JOIN customer c ON c.customer_id = b.customer_id
     WHERE b.booking_id = :id'
);
$stmt->execute(['id' => $bookingId]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: ' . ($currentUser['role'] === 'admin' ? 'admin_dashboard.php' : 'staff_dashboard.php'));
    exit;
}

$stmt = $pdo->prepare(
    'SELECT bi.quantity, bi.price, a.accommodation_name, a.accommodation_type,
            a.price AS nightly_price, a.price_weekend AS nightly_price_weekend
     FROM booking_item bi
     JOIN accommodation a ON a.accommodation_id = bi.accommodation_id
     WHERE bi.booking_id = :id'
);
$stmt->execute(['id' => $bookingId]);
$items = $stmt->fetchAll();

$checkIn = new DateTime($booking['check_in']);
$checkOut = new DateTime($booking['check_out']);
$nights = max(1, $checkOut->diff($checkIn)->days);
$stay = compute_stay_price(1, 1, $checkIn, $checkOut); // only need the night counts here

$backUrl = $currentUser['role'] === 'admin' ? 'admin_dashboard.php' : 'staff_dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Booking Receipt #<?= $bookingId ?> — Casadive Villa</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600;700&family=Mulish:wght@700;800&family=Poppins:wght@400;500;600&family=Raleway:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style/receipt.css">
</head>
<body>

  <header class="dash-topbar">
    <div class="container">
      <div class="brand">Casadive Villa</div>
      <div class="dash-user">
        <span class="who">Hi, <strong><?= htmlspecialchars($currentUser['fullname']) ?></strong><span class="role-badge"><?= htmlspecialchars($currentUser['role']) ?></span></span>
        <a href="<?= $backUrl ?>" class="btn btn-outline">Back to Dashboard</a>
        <a href="logout.php" class="btn btn-primary">Logout</a>
      </div>
    </div>
  </header>

  <main class="dash-main">
    <div class="container">
      <h1 class="dash-heading">Booking Receipt</h1>

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
          <a href="<?= $backUrl ?>" class="receipt-back">Back to Dashboard</a>
        </div>
      </div>
    </div>
  </main>

</body>
</html>
