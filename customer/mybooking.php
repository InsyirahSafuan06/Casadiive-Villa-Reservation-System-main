<?php
/**
 * Halaman carian MyBooking.
 * Pelanggan boleh cari tempahan mengikut nombor rujukan dan nombor telefon untuk lihat atau cetak resit.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$ref = isset($_GET['ref']) ? trim((string) $_GET['ref']) : '';
$phone = isset($_GET['phone']) ? trim((string) $_GET['phone']) : '';
$reviewError = null;

// Tetamu hanya boleh beri ulasan selepas mereka checked_out, dan hanya sekali bagi setiap tempahan —
// kedua-duanya dikuatkuasakan dalam query di bawah sebelum INSERT dicuba.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'review') {
    $rRef = filter_var($_POST['ref'] ?? '', FILTER_VALIDATE_INT);
    $rPhone = trim((string) ($_POST['phone'] ?? ''));
    $rating = filter_var($_POST['rating'] ?? '', FILTER_VALIDATE_INT);
    $comment = trim((string) ($_POST['comment'] ?? ''));

    if (!csrf_verify()) {
        $reviewError = 'Your session expired. Please try again.';
    } elseif ($rRef === false || $rPhone === '') {
        $reviewError = 'Invalid booking reference.';
    } elseif ($rating === false || $rating < 1 || $rating > 5) {
        $reviewError = 'Please choose a rating between 1 and 5 stars.';
    } else {
        $stmt = $pdo->prepare(
            "SELECT b.booking_id FROM booking b JOIN customer c ON c.customer_id = b.customer_id
             WHERE b.booking_id = :ref AND c.phone = :phone AND b.booking_status = 'checked_out'"
        );
        $stmt->execute(['ref' => $rRef, 'phone' => $rPhone]);
        if (!$stmt->fetch()) {
            $reviewError = 'We could not verify that booking for a review.';
        } else {
            try {
                // Jadual `review` ada UNIQUE constraint pada booking_id, jadi ulasan kedua
                // untuk tempahan yang sama akan gagal di sini dan jatuh ke catch di bawah.
                $stmt = $pdo->prepare(
                    'INSERT INTO review (booking_id, rating, comment) VALUES (:booking_id, :rating, :comment)'
                );
                $stmt->execute([
                    'booking_id' => $rRef,
                    'rating' => $rating,
                    'comment' => $comment !== '' ? $comment : null,
                ]);
            } catch (Exception $e) {
                $reviewError = 'You have already reviewed this booking.';
            }
        }
    }

    if (!$reviewError) {
        header('Location: mybooking.php?ref=' . $rRef . '&phone=' . urlencode($rPhone));
        exit;
    }

    $ref = (string) $rRef;
    $phone = $rPhone;
}

// Tempahan dicari mengikut nombor rujukan + nombor telefon yang digunakan untuk tempah — ini berfungsi
// sebagai "kata laluan" ringkas supaya tetamu tidak boleh lihat tempahan orang lain hanya dengan meneka ID.
$lookupAttempted = $ref !== '' || $phone !== '';
$lookupError = null;
$booking = null;
$items = [];
$existingReview = null;

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

            $stmt = $pdo->prepare('SELECT rating, comment, review_date FROM review WHERE booking_id = :id');
            $stmt->execute(['id' => $booking['booking_id']]);
            $existingReview = $stmt->fetch() ?: null;
        }
    }
}

$nights = 1;
$stay = ['weekday_nights' => 1, 'weekend_nights' => 0];
if ($booking) {
    $checkIn = new DateTime($booking['check_in']);
    $checkOut = new DateTime($booking['check_out']);
    $nights = max(1, $checkOut->diff($checkIn)->days);
    // Menghantar 1/1 sebagai harga di sini adalah satu helah: kita tidak perlukan jumlah dari panggilan ini,
    // hanya kiraan malam hari biasa/hujung minggu yang dipulangkan, untuk bina pecahan harga di bawah.
    $stay = compute_stay_price(1, 1, $checkIn, $checkOut);
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
      <?php if ($reviewError && $booking['booking_status'] !== 'checked_out'): ?>
        <!-- A review was submitted (e.g. via the footer form) for a booking that isn't
             checked_out yet — the review section below is hidden in that case, so without
             this the rejection would happen silently and look like the button did nothing. -->
        <p class="lookup-error" style="max-width:700px;margin:0 auto 24px;"><?= htmlspecialchars($reviewError) ?></p>
      <?php endif; ?>
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
              <span>Deposit</span>
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

      <?php if ($booking['booking_status'] === 'checked_out'): ?>
      <div class="receipt-card review-card">
        <?php if ($existingReview): ?>
          <h3>Your Review</h3>
          <div class="stars" aria-label="<?= (int) $existingReview['rating'] ?> out of 5 stars">
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <svg viewBox="0 0 20 19" class="<?= $i <= (int) $existingReview['rating'] ? '' : 'star-empty' ?>"><polygon points="10,0 12.5,7 20,7 14,11.5 16,19 10,14.5 4,19 6,11.5 0,7 7.5,7"/></svg>
            <?php endfor; ?>
          </div>
          <?php if ($existingReview['comment']): ?>
            <p class="review-comment"><?= nl2br(htmlspecialchars($existingReview['comment'])) ?></p>
          <?php endif; ?>
          <p class="review-date">Reviewed on <?= htmlspecialchars(date('d M Y', strtotime($existingReview['review_date']))) ?></p>
        <?php else: ?>
          <h3>Leave a Review</h3>
          <?php if ($reviewError): ?>
            <p class="lookup-error"><?= htmlspecialchars($reviewError) ?></p>
          <?php endif; ?>
          <form method="post" class="review-form">
            <?= csrf_field() ?>
            <input type="hidden" name="form" value="review">
            <input type="hidden" name="ref" value="<?= (int) $booking['booking_id'] ?>">
            <input type="hidden" name="phone" value="<?= htmlspecialchars($booking['phone']) ?>">
            <div class="rating-picker" role="radiogroup" aria-label="Rating">
              <?php for ($i = 5; $i >= 1; $i--): ?>
                <input type="radio" name="rating" id="rating-<?= $i ?>" value="<?= $i ?>" <?= $i === 5 ? 'checked' : '' ?>>
                <label for="rating-<?= $i ?>" title="<?= $i ?> stars">&#9733;</label>
              <?php endfor; ?>
            </div>
            <textarea name="comment" rows="3" placeholder="Tell us about your stay (optional)"></textarea>
            <button type="submit" class="lookup-submit">Submit Review</button>
          </form>
        <?php endif; ?>
      </div>
      <?php endif; ?>
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
