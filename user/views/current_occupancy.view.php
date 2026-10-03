<section class="occ" aria-labelledby="occ-title">
  <div class="occ-head">
    <h2 class="occ-title" id="occ-title">
      Occupied Accommodation
      <?php if ($occupancyDate === date('Y-m-d')): ?><span class="occ-live">Today</span><?php endif; ?>
    </h2>
    <form method="get" class="occ-date">
      <?php foreach ($_GET as $queryKey => $queryValue): ?>
        <?php if ($queryKey !== 'occ_date' && is_scalar($queryValue)): ?>
          <input type="hidden" name="<?= htmlspecialchars((string) $queryKey) ?>" value="<?= htmlspecialchars((string) $queryValue) ?>">
        <?php endif; ?>
      <?php endforeach; ?>
      <label for="occ-date">Date</label>
      <input id="occ-date" type="date" name="occ_date" value="<?= htmlspecialchars($occupancyDate) ?>" onchange="this.form.submit()" required>
    </form>
  </div>

  <div class="occ-summary">
    <span class="occ-pill"><b><?= count($occupancyRooms) ?></b> accommodation<?= count($occupancyRooms) === 1 ? '' : 's' ?></span>
    <span class="occ-pill"><b><?= count($occupancyBookings) ?></b> booking<?= count($occupancyBookings) === 1 ? '' : 's' ?></span>
    <span class="occ-pill">on <?= htmlspecialchars(date('d M Y', strtotime($occupancyDate))) ?></span>
  </div>

  <?php if (!$occupancyRooms): ?>
    <div class="occ-empty"><div aria-hidden="true">0</div>No guests are booked in an accommodation on this date.</div>
  <?php else: ?>
    <div class="occ-grid">
      <?php foreach ($occupancyRooms as $room): ?>
          <?php foreach ($room['bookings'] as $booking): ?>
            <article class="occ-room">
              <figure class="occ-photo">
                <?php if (!empty($room['image'])): ?>
                  <img src="<?= htmlspecialchars($room['image']) ?>" alt="<?= htmlspecialchars($room['name']) ?>" loading="lazy">
                <?php else: ?>
                  <div class="occ-photo-fallback" aria-hidden="true"><?= htmlspecialchars(strtoupper(substr((string) $room['name'], 0, 1))) ?></div>
                <?php endif; ?>
                <figcaption>Booking <?= htmlspecialchars(format_booking_ref((int) $booking['booking_id'])) ?></figcaption>
              </figure>
              <div class="occ-info">
                <p class="occ-room-type"><?= htmlspecialchars($room['type']) ?></p>
                <h3 class="occ-room-name"><?= htmlspecialchars($room['name']) ?></h3>
                <p class="occ-name"><?= htmlspecialchars($booking['full_name']) ?></p>
                <div class="occ-dates"><?= htmlspecialchars(date('d M Y', strtotime($booking['check_in']))) ?> &rarr; <?= htmlspecialchars(date('d M Y', strtotime($booking['check_out']))) ?></div>
                <div class="occ-meta">
                  <span><?= (int) $booking['total_guest'] ?> guest<?= (int) $booking['total_guest'] === 1 ? '' : 's' ?></span>
                  <span><?= htmlspecialchars($booking['phone']) ?></span>
                  <span><?= htmlspecialchars($booking['plate_num'] ?: 'No plate') ?></span>
                </div>
              </div>
              <div class="occ-side">
                <span class="occ-tag status"><?= htmlspecialchars(format_status($booking['booking_status'])) ?></span>
                <div class="occ-tags">
                  <?php if ($booking['check_in'] === $occupancyDate): ?><span class="occ-tag in">Check-in</span><?php endif; ?>
                  <?php if ($booking['check_out'] === $occupancyDate): ?><span class="occ-tag out">Check-out</span><?php endif; ?>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
