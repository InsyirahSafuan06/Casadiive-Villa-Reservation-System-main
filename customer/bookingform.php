<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$errors = [];
$old = [
    'full_name' => '',
    'phone' => '',
    'plate_num' => '',
    'check_in' => '',
    'check_out' => '',
    'total_guest' => '1',
    'accommodation_id' => '',
    'special_request' => '',
];
$successBookingId = null;

$accommodations = $pdo->query(
    "SELECT accommodation_id, accommodation_name, accommodation_type, price, price_weekend, capacity
     FROM accommodation
     WHERE status = 'available'
     ORDER BY accommodation_type, accommodation_id"
)->fetchAll();
$accommodationsById = [];
foreach ($accommodations as $acc) {
    $accommodationsById[(int) $acc['accommodation_id']] = $acc;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Prefill from the homepage's quick booking bar (check_in/check_out/guests/type).
    $qbCheckIn = trim((string) ($_GET['check_in'] ?? ''));
    $qbCheckOut = trim((string) ($_GET['check_out'] ?? ''));
    $qbGuests = trim((string) ($_GET['guests'] ?? ''));

    if (DateTime::createFromFormat('Y-m-d', $qbCheckIn)) {
        $old['check_in'] = $qbCheckIn;
    }
    if (DateTime::createFromFormat('Y-m-d', $qbCheckOut)) {
        $old['check_out'] = $qbCheckOut;
    }
    if (filter_var($qbGuests, FILTER_VALIDATE_INT) !== false && (int) $qbGuests >= 1) {
        $old['total_guest'] = $qbGuests;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['full_name'] = trim((string) ($_POST['full_name'] ?? ''));
    $old['phone'] = trim((string) ($_POST['phone'] ?? ''));
    $old['plate_num'] = trim((string) ($_POST['plate_num'] ?? ''));
    $old['check_in'] = trim((string) ($_POST['check_in'] ?? ''));
    $old['check_out'] = trim((string) ($_POST['check_out'] ?? ''));
    $old['total_guest'] = trim((string) ($_POST['total_guest'] ?? '1'));
    $old['accommodation_id'] = trim((string) ($_POST['accommodation_id'] ?? ''));
    $old['special_request'] = trim((string) ($_POST['special_request'] ?? ''));

    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please review your details and submit again.';
    }

    if ($old['full_name'] === '') {
        $errors[] = 'Full name is required.';
    }
    if ($old['phone'] === '') {
        $errors[] = 'Phone number is required.';
    }

    $checkIn = DateTime::createFromFormat('Y-m-d', $old['check_in']) ?: null;
    $checkOut = DateTime::createFromFormat('Y-m-d', $old['check_out']) ?: null;
    if (!$checkIn || !$checkOut) {
        $errors[] = 'Please provide valid check-in and check-out dates.';
    } elseif ($checkOut <= $checkIn) {
        $errors[] = 'Check-out date must be after check-in date.';
    }

    $totalGuest = filter_var($old['total_guest'], FILTER_VALIDATE_INT);
    if ($totalGuest === false || $totalGuest < 1) {
        $errors[] = 'Number of guests must be at least 1.';
    }

    $accommodationId = filter_var($old['accommodation_id'], FILTER_VALIDATE_INT);
    $selectedAccommodation = $accommodationId !== false ? ($accommodationsById[$accommodationId] ?? null) : null;
    if (!$selectedAccommodation) {
        $errors[] = 'Please select a valid accommodation package.';
    } elseif ($totalGuest !== false && $totalGuest > (int) $selectedAccommodation['capacity']) {
        $errors[] = "This package can only host up to {$selectedAccommodation['capacity']} guests.";
    }

    if (!$errors) {
        $weekdayPrice = (float) $selectedAccommodation['price'];
        $weekendPrice = $selectedAccommodation['price_weekend'] !== null ? (float) $selectedAccommodation['price_weekend'] : null;
        $stay = compute_stay_price($weekdayPrice, $weekendPrice, $checkIn, $checkOut);
        $totalAmount = $stay['total'];
        $depositAmount = round($totalAmount * 0.30, 2);

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'INSERT INTO customer (full_name, phone, plate_num) VALUES (:full_name, :phone, :plate_num)'
            );
            $stmt->execute([
                'full_name' => $old['full_name'],
                'phone' => $old['phone'],
                'plate_num' => $old['plate_num'] !== '' ? $old['plate_num'] : null,
            ]);
            $customerId = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'INSERT INTO booking (customer_id, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, special_request)
                 VALUES (:customer_id, :check_in, :check_out, :total_guest, :deposit_amount, :total_amount, "pending", :special_request)'
            );
            $stmt->execute([
                'customer_id' => $customerId,
                'check_in' => $checkIn->format('Y-m-d'),
                'check_out' => $checkOut->format('Y-m-d'),
                'total_guest' => $totalGuest,
                'deposit_amount' => $depositAmount,
                'total_amount' => $totalAmount,
                'special_request' => $old['special_request'] !== '' ? $old['special_request'] : null,
            ]);
            $bookingId = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'INSERT INTO booking_item (booking_id, accommodation_id, quantity, price)
                 VALUES (:booking_id, :accommodation_id, 1, :price)'
            );
            $stmt->execute([
                'booking_id' => $bookingId,
                'accommodation_id' => $accommodationId,
                'price' => $totalAmount,
            ]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Something went wrong while saving your booking. Please try again.';
        }

        if (!$errors) {
            header('Location: bookingform.php?success=' . $bookingId);
            exit;
        }
    }
}

if (isset($_GET['success']) && ctype_digit((string) $_GET['success'])) {
    $stmt = $pdo->prepare(
        'SELECT b.*, c.full_name, c.phone, c.plate_num
         FROM booking b JOIN customer c ON c.customer_id = b.customer_id
         WHERE b.booking_id = :id'
    );
    $stmt->execute(['id' => $_GET['success']]);
    $successBooking = $stmt->fetch();
    if ($successBooking) {
        $successBookingId = (int) $successBooking['booking_id'];
    }
}

$base = '../';
$active = '';
$pageTitle = 'Booking Details — Casadive Villa';
$pageCss = 'style/bookingform.css';
include __DIR__ . '/../includes/header.php';
?>

  <!-- STEP BAR -->
  <div class="step-bar" aria-label="Booking progress">
    <div class="step is-active">
      <span class="step-circle">1</span>
      <span class="step-label">Details</span>
    </div>
    <span class="step-line"></span>
    <div class="step<?= $successBookingId ? ' is-active' : '' ?>">
      <span class="step-circle">2</span>
      <span class="step-label">Payment</span>
    </div>
    <span class="step-line"></span>
    <div class="step">
      <span class="step-circle">3</span>
      <span class="step-label">Complete</span>
    </div>
  </div>

  <!-- PAGE TITLE -->
  <div class="container page-title">
    <h1>Booking Details</h1>
    <p>Please fill in your details to continue with your reservations.</p>
  </div>

  <?php if ($successBookingId): ?>
  <section class="booking-section">
    <div class="container">
      <div class="summary-card" style="max-width:620px;margin:0 auto;">
        <h2 class="summary-title">Booking Received</h2>
        <p style="font-family:'Raleway',sans-serif;font-weight:600;color:#000;margin-bottom:20px;">
          Thanks, <?= htmlspecialchars($successBooking['full_name']) ?> — your booking reference is
          <strong>#<?= $successBookingId ?></strong>. It is currently <strong><?= htmlspecialchars($successBooking['booking_status']) ?></strong>
          pending deposit payment of RM <?= number_format((float) $successBooking['deposit_amount'], 2) ?>.
        </p>
        <a href="mybooking.php?ref=<?= $successBookingId ?>&phone=<?= urlencode($successBooking['phone']) ?>" class="proceed-btn" style="max-width:none;margin-bottom:14px;">View / Print Receipt</a>
        <a href="villa.php" class="proceed-btn" style="max-width:none;background:transparent;color:var(--brown-price);border:1px solid var(--brown-price);">Back to Packages</a>
      </div>
    </div>
  </section>
  <?php else: ?>

  <!-- BOOKING FORM -->
  <section class="booking-section">
    <div class="container">
      <?php if ($errors): ?>
        <div style="background:#fdecea;border:1px solid #f5c2c0;color:#9a3226;border-radius:8px;padding:16px 20px;margin-bottom:28px;font-family:'Raleway',sans-serif;font-weight:600;">
          <ul style="margin-left:18px;">
            <?php foreach ($errors as $error): ?>
              <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form id="booking-form" method="post" novalidate>
        <?= csrf_field() ?>
        <div class="booking-grid">

          <div class="booking-form">
            <div class="form-field">
              <label for="full-name">Full Name</label>
              <input type="text" id="full-name" name="full_name" placeholder="Enter your full name" value="<?= htmlspecialchars($old['full_name']) ?>" required>
            </div>

            <div class="form-field">
              <label for="phone">Phone Number</label>
              <input type="tel" id="phone" name="phone" placeholder="Enter your phone number" value="<?= htmlspecialchars($old['phone']) ?>" required>
            </div>

            <div class="form-field">
              <label for="plate">Car Plate Number</label>
              <input type="text" id="plate" name="plate_num" placeholder="Enter your car plate number" value="<?= htmlspecialchars($old['plate_num']) ?>">
            </div>

            <div class="form-row">
              <div class="form-field">
                <label for="check-in">Check-in</label>
                <input type="date" id="check-in" name="check_in" value="<?= htmlspecialchars($old['check_in']) ?>" required>
              </div>
              <div class="form-field">
                <label for="check-out">Check-out</label>
                <input type="date" id="check-out" name="check_out" value="<?= htmlspecialchars($old['check_out']) ?>" required>
              </div>
            </div>

            <div class="form-row">
              <div class="form-field">
                <label for="guests">Number of guests</label>
                <input type="number" id="guests" name="total_guest" min="1" value="<?= htmlspecialchars($old['total_guest']) ?>" required>
              </div>
              <div class="form-field">
                <label for="accommodation">Accommodation</label>
                <select id="accommodation" name="accommodation_id" required>
                  <option value="">Select a package</option>
                  <?php foreach ($accommodations as $acc): ?>
                    <option
                      value="<?= (int) $acc['accommodation_id'] ?>"
                      data-name="<?= htmlspecialchars($acc['accommodation_name']) ?>"
                      data-type="<?= htmlspecialchars($acc['accommodation_type']) ?>"
                      data-price="<?= (float) $acc['price'] ?>"
                      data-price-weekend="<?= $acc['price_weekend'] !== null ? (float) $acc['price_weekend'] : (float) $acc['price'] ?>"
                      data-capacity="<?= (int) $acc['capacity'] ?>"
                      <?= $old['accommodation_id'] !== '' && (int) $old['accommodation_id'] === (int) $acc['accommodation_id'] ? 'selected' : '' ?>
                    ><?= htmlspecialchars($acc['accommodation_name']) ?> — RM <?= number_format((float) $acc['price'], 2) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="form-field">
              <label for="request">Special Request</label>
              <textarea id="request" name="special_request" rows="4" placeholder="Any special request? (optional)"><?= htmlspecialchars($old['special_request']) ?></textarea>
            </div>
          </div>

          <aside class="summary-card" aria-live="polite">
            <h2 class="summary-title">Booking Summary</h2>

            <div class="summary-package">
              <div class="summary-thumb">
                <span class="summary-badge" id="summary-type">Villa</span>
              </div>
              <div>
                <p class="summary-package-name" id="summary-name">Select a package</p>
                <p class="summary-package-cap" id="summary-capacity">—</p>
              </div>
            </div>

            <div class="summary-rows">
              <div class="summary-row"><span>Full Name</span><span id="s-name">—</span></div>
              <div class="summary-row"><span>Phone Number</span><span id="s-phone">—</span></div>
              <div class="summary-row"><span>Car Plate Number</span><span id="s-plate">—</span></div>
              <div class="summary-row"><span>Check-in</span><span id="s-checkin">—</span></div>
              <div class="summary-row"><span>Check-out</span><span id="s-checkout">—</span></div>
              <div class="summary-row"><span>Number of guests</span><span id="s-guests">1</span></div>
              <div class="summary-row"><span>Nights</span><span id="s-nights">1</span></div>
              <div class="summary-row"><span>Rate</span><span id="s-price">RM 0.00</span></div>
              <div class="summary-row"><span>Deposit (30%)</span><span id="s-deposit">RM 0.00</span></div>
              <div class="summary-row total"><span>Total Price</span><span id="s-total">RM 0.00</span></div>
            </div>
          </aside>

        </div>

        <button type="submit" class="proceed-btn">Proceed to Payment</button>
      </form>
    </div>
  </section>
  <?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php if (!$successBookingId): ?>
<script>
  const form = document.getElementById('booking-form');
  const accommodationSelect = document.getElementById('accommodation');

  // Mirrors compute_stay_price() in includes/helpers.php — Friday & Saturday
  // nights are priced at the weekend rate, everything else at the weekday rate.
  function computeStay(checkInStr, checkOutStr, weekdayPrice, weekendPrice){
    let weekdayNights = 0, weekendNights = 0;
    if (checkInStr && checkOutStr) {
      const cursor = new Date(checkInStr + 'T00:00:00');
      const end = new Date(checkOutStr + 'T00:00:00');
      while (cursor < end) {
        const day = cursor.getDay(); // 0=Sun .. 5=Fri, 6=Sat
        (day === 5 || day === 6) ? weekendNights++ : weekdayNights++;
        cursor.setDate(cursor.getDate() + 1);
      }
    }
    if (weekdayNights + weekendNights === 0) weekdayNights = 1;
    return {
      weekdayNights,
      weekendNights,
      total: weekdayNights * weekdayPrice + weekendNights * weekendPrice,
    };
  }

  function updateSummary(){
    const opt = accommodationSelect.selectedOptions[0];
    const hasPackage = Boolean(opt && opt.value);
    const weekdayPrice = hasPackage ? Number(opt.dataset.price) : 0;
    const weekendPrice = hasPackage ? Number(opt.dataset.priceWeekend) : 0;
    const capacity = hasPackage ? opt.dataset.capacity : null;

    document.getElementById('summary-type').textContent = hasPackage ? opt.dataset.type : 'Villa';
    document.getElementById('summary-name').textContent = hasPackage ? opt.dataset.name : 'Select a package';
    document.getElementById('summary-capacity').textContent = capacity ? `Max ${capacity} guests` : '—';

    document.getElementById('s-name').textContent = document.getElementById('full-name').value || '—';
    document.getElementById('s-phone').textContent = document.getElementById('phone').value || '—';
    document.getElementById('s-plate').textContent = document.getElementById('plate').value || '—';

    const checkIn = document.getElementById('check-in').value;
    const checkOut = document.getElementById('check-out').value;
    document.getElementById('s-checkin').textContent = checkIn || '—';
    document.getElementById('s-checkout').textContent = checkOut || '—';
    document.getElementById('s-guests').textContent = document.getElementById('guests').value || 1;

    const stay = computeStay(checkIn, checkOut, weekdayPrice, weekendPrice);
    const totalNights = stay.weekdayNights + stay.weekendNights;
    const deposit = stay.total * 0.3;

    document.getElementById('s-nights').textContent = totalNights;

    let priceLabel = `RM ${weekdayPrice.toFixed(2)} / night`;
    if (weekendPrice !== weekdayPrice && stay.weekendNights > 0) {
      priceLabel = `RM ${weekdayPrice.toFixed(2)} weekday, RM ${weekendPrice.toFixed(2)} weekend`;
    }
    document.getElementById('s-price').textContent = priceLabel;
    document.getElementById('s-deposit').textContent = `RM ${deposit.toFixed(2)}`;
    document.getElementById('s-total').textContent = `RM ${stay.total.toFixed(2)}`;
  }

  const params = new URLSearchParams(window.location.search);
  const preselectName = params.get('accommodation');
  const preselectType = params.get('type');
  if (preselectName) {
    const match = [...accommodationSelect.options].find(o => o.dataset.name === preselectName);
    if (match) accommodationSelect.value = match.value;
  } else if (preselectType) {
    const match = [...accommodationSelect.options].find(o => o.dataset.type === preselectType);
    if (match) accommodationSelect.value = match.value;
  }

  form.addEventListener('input', updateSummary);
  updateSummary();
</script>
<?php endif; ?>
