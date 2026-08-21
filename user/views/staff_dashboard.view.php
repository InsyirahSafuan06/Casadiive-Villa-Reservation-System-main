<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Dashboard — Casadive Villa</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600;700&family=Mulish:wght@700;800&family=Poppins:wght@400;500;600&family=Raleway:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style/dashboard.css">
</head>
<body>

  <header class="dash-topbar">
    <div class="container">
      <div class="brand">Casadive Villa</div>
      <div class="dash-user">
        <span class="who">Hi, <strong><?= htmlspecialchars($user['fullname']) ?></strong><span class="role-badge"><?= htmlspecialchars($user['role']) ?></span></span>
        <a href="../index.php" class="btn btn-outline">View Site</a>
        <a href="logout.php" class="btn btn-primary">Logout</a>
      </div>
    </div>
  </header>

  <main class="dash-main">
    <div class="container">
      <h1 class="dash-heading">Staff Dashboard</h1>
      <p class="dash-subheading">Manage bookings and daily check-ins / check-outs.</p>

      <?php if ($updated): ?>
        <p class="flash">Booking status updated.</p>
      <?php endif; ?>
      <?php if ($accUpdated): ?>
        <p class="flash">Accommodation status updated.</p>
      <?php endif; ?>
      <?php if ($paymentRecorded): ?>
        <p class="flash">Payment recorded.</p>
      <?php endif; ?>

      <div class="stat-grid">
        <div class="stat-tile">
          <p class="stat-label">Total Bookings</p>
          <p class="stat-value"><?= $stats['total_bookings'] ?></p>
        </div>
        <div class="stat-tile">
          <p class="stat-label">Pending Bookings</p>
          <p class="stat-value"><?= $stats['pending_bookings'] ?></p>
        </div>
        <div class="stat-tile">
          <p class="stat-label">Check-ins Today</p>
          <p class="stat-value"><?= $stats['checkins_today'] ?></p>
        </div>
        <div class="stat-tile">
          <p class="stat-label">Check-outs Today</p>
          <p class="stat-value"><?= $stats['checkouts_today'] ?></p>
        </div>
      </div>

      <section class="dash-section">
        <h2>Bookings</h2>
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Guest</th>
              <th>Phone</th>
              <th>Plate No.</th>
              <th>Accommodation</th>
              <th>Check-in</th>
              <th>Check-out</th>
              <th>Guests</th>
              <th>Total (RM)</th>
              <th>Status</th>
              <th>Payment</th>
              <th>Update</th>
              <th>Receipt</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$bookings): ?>
              <tr class="empty-row"><td colspan="13">No bookings yet.</td></tr>
            <?php else: foreach ($bookings as $b): ?>
              <tr>
                <td>#<?= (int) $b['booking_id'] ?></td>
                <td><?= htmlspecialchars($b['full_name']) ?></td>
                <td><?= htmlspecialchars($b['phone']) ?></td>
                <td><?= htmlspecialchars($b['plate_num'] ?? '—') ?></td>
                <td><?= htmlspecialchars($b['accommodations'] ?? '—') ?></td>
                <td><?= htmlspecialchars($b['check_in']) ?></td>
                <td><?= htmlspecialchars($b['check_out']) ?></td>
                <td><?= (int) $b['total_guest'] ?></td>
                <td><?= number_format((float) $b['total_amount'], 2) ?></td>
                <td><span class="status-badge status-<?= htmlspecialchars($b['booking_status']) ?>"><?= htmlspecialchars(format_status($b['booking_status'])) ?></span></td>
                <td>
                  <?php if (payment_needs_refund($b['booking_status'], $b['latest_payment_status'])): ?>
                    <span class="status-badge status-refund_due">Refund Due</span>
                    <a href="#record-payment" class="btn btn-outline" style="padding:4px 10px;font-size:12px;margin-left:6px;">Refund</a>
                  <?php elseif ($b['latest_payment_status']): ?>
                    <?= htmlspecialchars(ucfirst($b['latest_payment_status'])) ?>
                  <?php else: ?>
                    <span style="color:#999;">No record</span>
                  <?php endif; ?>
                </td>
                <td>
                  <form class="status-form" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="booking_id" value="<?= (int) $b['booking_id'] ?>">
                    <select name="booking_status">
                      <?php foreach ($validStatuses as $status): ?>
                        <option value="<?= $status ?>" <?= $status === $b['booking_status'] ? 'selected' : '' ?>><?= format_status($status) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <button type="submit">Save</button>
                  </form>
                </td>
                <td><a href="booking_receipt.php?id=<?= (int) $b['booking_id'] ?>" class="btn btn-outline" style="padding:6px 14px;font-size:13px;">View</a></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </section>

      <section class="dash-section">
        <h2>Accommodations — Availability &amp; Maintenance</h2>
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Type</th>
              <th>Capacity</th>
              <th>Status</th>
              <th>Update</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($accommodations as $a): ?>
              <tr>
                <td>#<?= (int) $a['accommodation_id'] ?></td>
                <td><?= htmlspecialchars($a['accommodation_name']) ?></td>
                <td><?= htmlspecialchars($a['accommodation_type']) ?></td>
                <td><?= (int) $a['capacity'] ?></td>
                <td><span class="status-badge status-<?= htmlspecialchars($a['status']) ?>"><?= htmlspecialchars(ucfirst($a['status'])) ?></span></td>
                <td>
                  <form class="status-form" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_acc_status">
                    <input type="hidden" name="accommodation_id" value="<?= (int) $a['accommodation_id'] ?>">
                    <select name="acc_status">
                      <?php foreach ($validAccStatuses as $status): ?>
                        <option value="<?= $status ?>" <?= $status === $a['status'] ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <button type="submit">Save</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </section>

      <section class="dash-section" id="record-payment">
        <h2>Record a Payment</h2>
        <?php if ($refundBookingId): ?>
          <p class="notice-info">Recording a refund for booking #<?= $refundBookingId ?> — set Amount to the deposit refunded and Status to "Refunded".</p>
        <?php endif; ?>
        <form method="post" style="display:flex;flex-wrap:wrap;gap:16px;align-items:flex-end;margin-bottom:30px;font-family:'Raleway',sans-serif;">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="record_payment">
          <div>
            <label style="display:block;font-weight:600;font-size:13px;color:var(--brown-price);margin-bottom:6px;">Booking</label>
            <select name="booking_id" style="padding:10px 14px;border:1px solid var(--border);border-radius:6px;font-family:inherit;" required>
              <option value="">Select a booking</option>
              <?php foreach ($bookings as $b): ?>
                <option value="<?= (int) $b['booking_id'] ?>" <?= $refundBookingId === (int) $b['booking_id'] ? 'selected' : '' ?>>#<?= (int) $b['booking_id'] ?> — <?= htmlspecialchars($b['full_name']) ?> (RM <?= number_format((float) $b['deposit_amount'], 2) ?> deposit due)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label style="display:block;font-weight:600;font-size:13px;color:var(--brown-price);margin-bottom:6px;">Amount (RM)</label>
            <input type="number" name="deposit_paid" min="0" step="0.01" style="padding:10px 14px;border:1px solid var(--border);border-radius:6px;font-family:inherit;width:140px;" required>
          </div>
          <div>
            <label style="display:block;font-weight:600;font-size:13px;color:var(--brown-price);margin-bottom:6px;">Status</label>
            <select name="payment_status" style="padding:10px 14px;border:1px solid var(--border);border-radius:6px;font-family:inherit;">
              <?php foreach ($validPaymentStatuses as $status): ?>
                <option value="<?= $status ?>" <?= $refundBookingId && $status === 'refunded' ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div style="flex:1;min-width:180px;">
            <label style="display:block;font-weight:600;font-size:13px;color:var(--brown-price);margin-bottom:6px;">Receipt / Note</label>
            <input type="text" name="receipt" placeholder="e.g. Cash received, receipt #045" style="width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:6px;font-family:inherit;">
          </div>
          <button type="submit" class="btn btn-primary">Record Payment</button>
        </form>

        <h2>Recent Payments</h2>
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Booking</th>
              <th>Guest</th>
              <th>Amount (RM)</th>
              <th>Date</th>
              <th>Status</th>
              <th>Note</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$recentPayments): ?>
              <tr class="empty-row"><td colspan="7">No payments recorded yet.</td></tr>
            <?php else: foreach ($recentPayments as $p): ?>
              <tr>
                <td>#<?= (int) $p['payment_id'] ?></td>
                <td>#<?= (int) $p['booking_id'] ?></td>
                <td><?= htmlspecialchars($p['full_name']) ?></td>
                <td><?= number_format((float) $p['deposit_paid'], 2) ?></td>
                <td><?= htmlspecialchars($p['payment_date']) ?></td>
                <td><span class="status-badge status-<?= htmlspecialchars($p['payment_status']) ?>"><?= htmlspecialchars(ucfirst($p['payment_status'])) ?></span></td>
                <td><?= htmlspecialchars($p['receipt'] ?? '—') ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </section>
    </div>
  </main>

</body>
</html>
