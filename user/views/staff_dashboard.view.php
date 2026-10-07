<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Dashboard — Casadive Villa</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600;700&family=Mulish:wght@700;800&family=Poppins:wght@400;500;600&family=Raleway:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style/dashboard.css?v=<?= filemtime(__DIR__ . '/../style/dashboard.css') ?>">
</head>
<body>

  <div class="manager-shell">
    <aside class="manager-sidebar">
      <a href="staff_dashboard.php" class="manager-brand">
        <img src="../assets/images/logo.png" alt="Casadive Villa" class="manager-brand-logo">
        <small>Booking &amp; Management System</small>
      </a>
      <nav class="manager-nav" aria-label="Staff navigation">
        <a href="staff_dashboard.php" class="is-active" aria-current="page">Dashboard</a>
        <a href="#bookings">Bookings</a>
        <a href="#occupancy">Occupancy</a>
        <?php if ($user['role'] === 'staff'): ?>
          <a href="#my-tasks">My Tasks</a>
        <?php endif; ?>
        <a href="#accommodations">Availability</a>
        <a href="#record-payment">Payments</a>
        <a href="#gallery">Gallery</a>
        <?php if ($user['role'] === 'manager'): ?>
          <a href="manage_account.php?id=<?= (int) $user['user_id'] ?>">Settings</a>
        <?php endif; ?>
        <a href="logout.php" class="manager-nav-logout">Logout</a>
      </nav>
      <div class="manager-sidebar-footer">Casadive Villa Operations</div>
    </aside>

    <div class="manager-workspace">
      <header class="manager-header">
        <span class="manager-header-label">Staff workspace</span>
        <div class="dash-user">
          <span class="who">Hi, <strong><?= htmlspecialchars($user['fullname']) ?></strong><span class="role-badge"><?= htmlspecialchars($user['role']) ?></span></span>
          <?php if ($user['role'] === 'manager'): ?>
            <a href="manage_account.php?id=<?= (int) $user['user_id'] ?>" class="btn btn-outline">My Account</a>
          <?php endif; ?>
          <a href="../index.php" class="btn btn-outline">View Site</a>
        </div>
      </header>

  <main class="dash-main manager-main">
    <div class="container">
      <h1 class="dash-heading">Staff Dashboard</h1>
      <p class="dash-subheading">Manage bookings and daily check-ins / check-outs.</p>

      <?php # check flag $updated true ke tak untuk papar mesej flash 'Booking status updated' ?>
      <?php if ($updated): ?>
        <p class="flash">Booking status updated.</p>
      <?php endif; ?>
      <?php # check flag $accUpdated untuk papar mesej flash accommodation status dah diupdate ?>
      <?php if ($accUpdated): ?>
        <p class="flash">Accommodation status updated.</p>
      <?php endif; ?>
      <?php # check flag $paymentRecorded untuk papar mesej flash payment dah direkod ?>
      <?php if ($paymentRecorded): ?>
        <p class="flash">Payment recorded.</p>
      <?php endif; ?>
      <?php # check flag $galleryAdded untuk papar mesej flash gambar gallery dah ditambah ?>
      <?php if ($galleryAdded): ?>
        <p class="flash">Image added to gallery.</p>
      <?php endif; ?>
      <?php # check flag $galleryDeleted untuk papar mesej flash gambar gallery dah dibuang ?>
      <?php if ($galleryDeleted): ?>
        <p class="flash">Image removed from gallery.</p>
      <?php endif; ?>
      <?php if ($taskUpdated): ?>
        <p class="flash">Task status updated.</p>
      <?php endif; ?>
      <?php if ($taskError): ?>
        <p class="flash flash-error">Could not update that task. Refresh and try again.</p>
      <?php endif; ?>

      <div id="occupancy">
        <?php require __DIR__ . '/current_occupancy.view.php'; ?>
      </div>

      <?php if ($user['role'] === 'staff'): ?>
        <section class="dash-section" id="my-tasks">
          <h2>My Tasks</h2>
          <table>
            <thead>
              <tr><th>Task</th><th>Status</th><th>Update</th></tr>
            </thead>
            <tbody>
              <?php if (!$tasks): ?>
                <tr class="empty-row"><td colspan="3">No tasks assigned yet.</td></tr>
              <?php else: foreach ($tasks as $task): ?>
                <tr>
                  <td><?= htmlspecialchars($task['title']) ?></td>
                  <td><span class="status-badge status-<?= htmlspecialchars($task['status']) ?>"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $task['status']))) ?></span></td>
                  <td>
                    <form class="status-form" method="post">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="update_task_status">
                      <input type="hidden" name="task_id" value="<?= (int) $task['task_id'] ?>">
                      <select name="task_status">
                        <?php foreach ($validTaskStatuses as $status): ?>
                          <option value="<?= $status ?>" <?= $status === $task['status'] ? 'selected' : '' ?>><?= htmlspecialchars(ucwords(str_replace('_', ' ', $status))) ?></option>
                        <?php endforeach; ?>
                      </select>
                      <button type="submit">Save</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </section>
      <?php endif; ?>

      <section class="dash-section" id="bookings">
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
            <?php # check array $bookings kosong ke tak, kalau kosong papar 'No bookings yet' ?>
            <?php if (!$bookings): ?>
              <tr class="empty-row"><td colspan="13">No bookings yet.</td></tr>
            <?php else: /* loop setiap booking dalam $bookings untuk papar dalam table */ foreach ($bookings as $b): ?>
              <tr>
                <?php # calling function format_booking_ref() & htmlspecialchars() untuk papar booking ID dalam format rujukan ?>
                <td><?= htmlspecialchars(format_booking_ref((int) $b['booking_id'])) ?></td>
                <td><?= htmlspecialchars($b['full_name']) ?></td>
                <td><?= htmlspecialchars($b['phone']) ?></td>
                <td><?= htmlspecialchars($b['plate_num'] ?? '—') ?></td>
                <td><?= htmlspecialchars($b['accommodations'] ?? '—') ?></td>
                <?php # calling function date() & strtotime() untuk tukar format tarikh check-in ke d/m/Y ?>
                <td><?= htmlspecialchars(date('d/m/Y', strtotime($b['check_in']))) ?></td>
                <?php # calling function date() & strtotime() untuk tukar format tarikh check-out ke d/m/Y ?>
                <td><?= htmlspecialchars(date('d/m/Y', strtotime($b['check_out']))) ?></td>
                <td><?= (int) $b['total_guest'] ?></td>
                <td>
                  <?php # calling function number_format() untuk papar total amount dengan 2 titik perpuluhan ?>
                  <?= number_format((float) $b['total_amount'], 2) ?>
                  <?php # check ada addon bbq/mattress/discount untuk papar badge tambahan ?>
                  <?php if ($b['addon_bbq'] || $b['addon_mattress'] || (float) $b['discount_amount'] > 0): ?>
                    <div class="addon-badges">
                      <?php if ($b['addon_bbq']): ?><span class="addon-badge">BBQ</span><?php endif; ?>
                      <?php if ($b['addon_mattress']): ?><span class="addon-badge">Mattress</span><?php endif; ?>
                      <?php if ((float) $b['discount_amount'] > 0): ?><span class="addon-badge addon-badge-discount">-RM<?= number_format((float) $b['discount_amount'], 2) ?></span><?php endif; ?>
                    </div>
                  <?php endif; ?>
                </td>
                <?php # calling function format_status() & htmlspecialchars() untuk papar status booking dalam label senang faham ?>
                <td><span class="status-badge status-<?= htmlspecialchars($b['booking_status']) ?>"><?= htmlspecialchars(format_status($b['booking_status'])) ?></span></td>
                <td>
                  <?php # calling function payment_needs_refund() untuk check kalau booking ni perlu refund ?>
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
                    <?php # calling function csrf_field() untuk letak token keselamatan dalam form update status ?>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="booking_id" value="<?= (int) $b['booking_id'] ?>">
                    <select name="booking_status">
                      <?php # loop setiap status dalam $validStatuses untuk bina dropdown pilihan status booking ?>
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

      <section class="dash-section" id="accommodations">
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
            <?php # loop setiap accommodation dalam $accommodations untuk papar dalam table ?>
            <?php foreach ($accommodations as $a): ?>
              <tr>
                <td>#<?= (int) $a['accommodation_id'] ?></td>
                <td><?= htmlspecialchars($a['accommodation_name']) ?></td>
                <td><?= htmlspecialchars($a['accommodation_type']) ?></td>
                <td><?= (int) $a['capacity'] ?></td>
                <td><span class="status-badge status-<?= htmlspecialchars($a['status']) ?>"><?= htmlspecialchars(ucfirst($a['status'])) ?></span></td>
                <td>
                  <form class="status-form" method="post">
                    <?php # calling function csrf_field() untuk letak token keselamatan dalam form update acc status ?>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_acc_status">
                    <input type="hidden" name="accommodation_id" value="<?= (int) $a['accommodation_id'] ?>">
                    <select name="acc_status">
                      <?php # loop setiap status dalam $validAccStatuses untuk bina dropdown pilihan status accommodation ?>
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
        <?php # check ada $refundBookingId untuk papar notice bantu staff isi form refund ?>
        <?php if ($refundBookingId): ?>
          <p class="notice-info">Recording a refund for booking <?= htmlspecialchars(format_booking_ref((int) $refundBookingId)) ?> — set Amount to the deposit refunded and Status to "Refunded".</p>
        <?php endif; ?>
        <form method="post" style="display:flex;flex-wrap:wrap;gap:16px;align-items:flex-end;margin-bottom:30px;font-family:'Raleway',sans-serif;">
          <?php # calling function csrf_field() untuk letak token keselamatan dalam form record payment ?>
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="record_payment">
          <div>
            <label style="display:block;font-weight:600;font-size:13px;color:var(--brown-price);margin-bottom:6px;">Booking</label>
            <select name="booking_id" style="padding:10px 14px;border:1px solid var(--border);border-radius:6px;font-family:inherit;" required>
              <option value="">Select a booking</option>
              <?php # loop setiap booking dalam $bookings untuk bina dropdown pilihan booking nak rekod payment ?>
              <?php foreach ($bookings as $b): ?>
                <option value="<?= (int) $b['booking_id'] ?>" <?= $refundBookingId === (int) $b['booking_id'] ? 'selected' : '' ?>><?= htmlspecialchars(format_booking_ref((int) $b['booking_id'])) ?> — <?= htmlspecialchars($b['full_name']) ?> (RM <?= number_format((float) $b['deposit_amount'], 2) ?> deposit due)</option>
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
              <?php # loop setiap status dalam $validPaymentStatuses untuk bina dropdown pilihan status payment ?>
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
            <?php # check array $recentPayments kosong, kalau tak loop setiap payment untuk papar dalam table ?>
            <?php if (!$recentPayments): ?>
              <tr class="empty-row"><td colspan="7">No payments recorded yet.</td></tr>
            <?php else: foreach ($recentPayments as $p): ?>
              <tr>
                <td>#<?= (int) $p['payment_id'] ?></td>
                <?php # calling function format_booking_ref() untuk papar rujukan booking pada rekod payment ?>
                <td><?= htmlspecialchars(format_booking_ref((int) $p['booking_id'])) ?></td>
                <td><?= htmlspecialchars($p['full_name']) ?></td>
                <td><?= number_format((float) $p['deposit_paid'], 2) ?></td>
                <td><?= htmlspecialchars($p['payment_date']) ?></td>
                <td><span class="status-badge status-<?= htmlspecialchars($p['payment_status']) ?>"><?= htmlspecialchars(ucfirst($p['payment_status'])) ?></span></td>
                <?php # calling function str_starts_with() untuk check kalau receipt ni sebenarnya fail proof upload, papar link View Proof kalau ya ?>
                <td><?php if ($p['receipt'] && str_starts_with($p['receipt'], 'assets/uploads/payments/')): ?>
                  <a href="../<?= htmlspecialchars($p['receipt']) ?>" target="_blank" rel="noopener" class="btn btn-outline" style="padding:4px 10px;font-size:12px;">View Proof</a>
                <?php else: ?>
                  <?= htmlspecialchars($p['receipt'] ?? '—') ?>
                <?php endif; ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </section>

      <section class="dash-section" id="gallery">
        <h2 class="section-heading">Gallery</h2>
        <?php # check ada $galleryError untuk papar mesej ralat upload gambar ?>
        <?php if ($galleryError): ?>
          <p class="flash flash-error"><?= htmlspecialchars($galleryError) ?></p>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data" class="gallery-upload-form">
          <?php # calling function csrf_field() untuk letak token keselamatan dalam form upload gallery ?>
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="add_gallery_image">
          <input type="file" name="gallery_image" accept=".jpg,.jpeg,.png,.webp,image/*" required>
          <input type="text" name="caption" placeholder="Caption (optional)" maxlength="150">
          <button type="submit" class="btn btn-md btn-primary">+ Add Image</button>
        </form>
        <div class="gallery-grid">
          <?php # check array $galleryImages kosong, kalau tak loop setiap gambar untuk papar dalam grid ?>
          <?php if (!$galleryImages): ?>
            <p class="text-muted">No gallery images yet.</p>
          <?php else: foreach ($galleryImages as $g): ?>
            <div class="gallery-thumb">
              <img src="../<?= htmlspecialchars($g['image_path']) ?>" alt="<?= htmlspecialchars($g['caption'] ?: 'Gallery image') ?>">
              <form method="post" onsubmit="return confirm('Remove this image from the gallery?');">
                <?php # calling function csrf_field() untuk letak token keselamatan dalam form delete gallery image ?>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete_gallery_image">
                <input type="hidden" name="gallery_id" value="<?= (int) $g['gallery_id'] ?>">
                <button type="submit" class="gallery-thumb-delete" aria-label="Delete image">&times;</button>
              </form>
            </div>
          <?php endforeach; endif; ?>
        </div>
      </section>
    </div>
  </main>
    </div>
  </div>

</body>
</html>
