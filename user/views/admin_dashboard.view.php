<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard — Casadive Villa</title>
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
        <a href="manage_account.php?id=<?= (int) $user['user_id'] ?>" class="btn btn-outline">My Account</a>
        <a href="../index.php" class="btn btn-outline">View Site</a>
        <a href="logout.php" class="btn btn-primary">Logout</a>
      </div>
    </div>
  </header>

  <main class="dash-main">
    <div class="container">
      <h1 class="dash-heading">Admin Dashboard</h1>
      <p class="dash-subheading">Overview of bookings, accommodations, and staff accounts.</p>

      <?php if ($updated): ?>
        <p class="flash">Booking status updated.</p>
      <?php endif; ?>
      <?php if ($accountCreated): ?>
        <p class="flash">Account created.</p>
      <?php endif; ?>
      <?php if ($accountSaved): ?>
        <p class="flash">Account updated.</p>
      <?php endif; ?>
      <?php if ($accountDeleted): ?>
        <p class="flash">Account deleted.</p>
      <?php endif; ?>
      <?php if ($accCreated): ?>
        <p class="flash">Accommodation created.</p>
      <?php endif; ?>
      <?php if ($accSaved): ?>
        <p class="flash">Accommodation updated.</p>
      <?php endif; ?>
      <?php if ($accDeleted): ?>
        <p class="flash">Accommodation deleted.</p>
      <?php endif; ?>
      <?php if ($reviewDeleted): ?>
        <p class="flash">Review deleted.</p>
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
          <p class="stat-label">Confirmed Revenue</p>
          <p class="stat-value">RM <?= number_format($stats['revenue'], 2) ?></p>
        </div>
        <div class="stat-tile">
          <p class="stat-label">Staff &amp; Admin Accounts</p>
          <p class="stat-value"><?= $stats['total_users'] ?></p>
        </div>
      </div>

      <section class="dash-section">
        <h2>Recent Bookings</h2>
        <p class="notice-info">Before clicking a Notification button below, make sure the browser you're using is logged into WhatsApp Web as the official Casadive Villa number — the message opens pre-filled, but you still need to tap Send yourself.</p>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Guest</th>
              <th>Phone</th>
              <th>Accommodation</th>
              <th>Check-in</th>
              <th>Check-out</th>
              <th>Guests</th>
              <th>Total (RM)</th>
              <th>Deposit (RM)</th>
              <th>Status</th>
              <th>Payment</th>
              <th>Update</th>
              <th>Notification</th>
              <th>Receipt</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$bookings): ?>
              <tr class="empty-row"><td colspan="14">No bookings yet.</td></tr>
            <?php else: foreach ($bookings as $b):
              // "Due" maksudnya check-in esok — itu waktu yang sepatutnya reminder dihantar
              $reminderDue = $b['check_in'] === date('Y-m-d', strtotime('+1 day'));

              // setiap baris dapat SATU je butang notification, ikut status booking sekarang
              // (ada status yang takde butang langsung, contoh checked_in)
              $notifType = null;
              $notifLabel = null;
              $notifClass = 'btn-primary';

              if ($b['booking_status'] === 'confirmed' && $reminderDue) {
                  $notifType = 'check_in';
                  $notifLabel = 'Send Check-In Reminder';
                  $notifClass = 'btn-primary';

              } elseif ($b['booking_status'] === 'pending') {
                  $notifType = 'pending';
                  $notifLabel = 'Payment pending';
                  $notifClass = 'btn-primary';

              } elseif ($b['booking_status'] === 'confirmed') {
                  $notifType = 'booking_confirmation';
                  $notifLabel = 'Send Booking';
                  $notifClass = 'btn-primary';

              } elseif ($b['booking_status'] === 'checked_out') {
                  $notifType = 'check_out';
                  $notifLabel = 'Send Thank You';
                  $notifClass = 'btn-primary';

              } elseif ($b['booking_status'] === 'cancelled') {
                  $notifType = 'cancelled';
                  $notifLabel = 'Send Cancelled';
                  $notifClass = 'btn-primary';
              }

              // dah hantar ke belum mesej ni untuk booking ni? kalau dah, butang tunjuk "Sent" —
              // tapi tetap boleh klik lagi kalau nak hantar semula
              $alreadySentAt = $notifType ? ($sentLookup[$b['booking_id']][$notifType] ?? null) : null;
              ?>
              <tr<?= $reminderDue ? ' class="tr-due"' : '' ?>>
                <td><?= htmlspecialchars(format_booking_ref((int) $b['booking_id'])) ?></td>
                <td><?= htmlspecialchars($b['full_name']) ?></td>
                <td><?= htmlspecialchars($b['phone']) ?></td>
                <td><?= htmlspecialchars($b['accommodations'] ?? '—') ?></td>
                <td><?= htmlspecialchars($b['check_in']) ?></td>
                <td><?= htmlspecialchars($b['check_out']) ?></td>
                <td><?= (int) $b['total_guest'] ?></td>
                <td><?= number_format((float) $b['total_amount'], 2) ?></td>
                <td><?= number_format((float) $b['deposit_amount'], 2) ?></td>
                <td><span class="status-badge status-<?= htmlspecialchars($b['booking_status']) ?>"><?= htmlspecialchars(format_status($b['booking_status'])) ?></span></td>
                <td>
                  <?php if (payment_needs_refund($b['booking_status'], $b['latest_payment_status'])): ?>
                    <span class="status-badge status-refund_due">Refund Due</span>
                    <a href="staff_dashboard.php?refund_booking=<?= (int) $b['booking_id'] ?>#record-payment" class="btn btn-sm btn-outline" style="margin-left:6px;">Refund</a>
                  <?php elseif ($b['latest_payment_status']): ?>
                    <?= htmlspecialchars(ucfirst($b['latest_payment_status'])) ?>
                  <?php else: ?>
                    <span class="text-muted">No record</span>
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

                <td>
                <?php if ($notifType): ?>
                  <a href="notification.php?booking_id=<?= (int) $b['booking_id'] ?>&type=<?= $notifType ?>" class="btn btn-sm <?= $alreadySentAt ? 'btn-sent' : $notifClass ?>" <?= $alreadySentAt ? 'title="Sent on ' . htmlspecialchars($alreadySentAt) . ' — click to resend"' : '' ?>>
                    <?= $alreadySentAt ? '&check; Sent' : $notifLabel ?>
                  </a>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
                </td>

                <td><a href="booking_receipt.php?id=<?= (int) $b['booking_id'] ?>" class="btn btn-sm btn-outline">View</a></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </section>

      <section class="dash-section">
        <h2 class="section-heading">
          Accommodations
          <a href="manage_accommodation.php" class="btn btn-md btn-primary">+ Add Accommodation</a>
        </h2>
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Type</th>
              <th>Price (RM)</th>
              <th>Capacity</th>
              <th>Status</th>
              <th>Manage</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($accommodations as $a): ?>
              <tr>
                <td>#<?= (int) $a['accommodation_id'] ?></td>
                <td><?= htmlspecialchars($a['accommodation_name']) ?></td>
                <td><?= htmlspecialchars($a['accommodation_type']) ?></td>
                <td><?= number_format((float) $a['price'], 2) ?></td>
                <td><?= (int) $a['capacity'] ?></td>
                <td><span class="status-badge status-<?= htmlspecialchars($a['status']) ?>"><?= htmlspecialchars($a['status']) ?></span></td>
                <td>
                  <div class="status-form">
                    <a href="manage_accommodation.php?id=<?= (int) $a['accommodation_id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                    <form method="post" action="manage_accommodation.php" onsubmit="return confirm('Delete this accommodation? This cannot be undone.');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="accommodation_id" value="<?= (int) $a['accommodation_id'] ?>">
                      <button type="submit">Delete</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </section>

      <section class="dash-section">
        <h2 class="section-heading">Guest Reviews</h2>
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Guest</th>
              <th>Shown Publicly As</th>
              <th>Booking</th>
              <th>Rating</th>
              <th>Comment</th>
              <th>Photo</th>
              <th>Date</th>
              <th>Manage</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$reviews): ?>
              <tr class="empty-row"><td colspan="9">No reviews yet.</td></tr>
            <?php else: foreach ($reviews as $r): ?>
              <tr>
                <td>#<?= (int) $r['review_id'] ?></td>
                <td><?= $r['full_name'] !== null ? htmlspecialchars($r['full_name']) : '<span class="text-muted">Unverified</span>' ?></td>
                <td><?= $r['display_name'] ? htmlspecialchars($r['display_name']) : '<span class="text-muted">Anonymous</span>' ?></td>
                <td><?= $r['booking_id'] !== null ? htmlspecialchars(format_booking_ref((int) $r['booking_id'])) : '<span class="text-muted">—</span>' ?></td>
                <td><?= (int) $r['rating'] ?> &#9733;</td>
                <td><?= htmlspecialchars($r['comment'] ?: '—') ?></td>
                <td>
                  <?php if ($r['image_path']): ?>
                    <a href="../<?= htmlspecialchars($r['image_path']) ?>" target="_blank" rel="noopener">
                      <img src="../<?= htmlspecialchars($r['image_path']) ?>" alt="Review photo" style="width:56px;height:56px;object-fit:cover;border-radius:6px;">
                    </a>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars(date('d M Y', strtotime($r['review_date']))) ?></td>
                <td>
                  <form method="post" onsubmit="return confirm('Delete this review? This cannot be undone.');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete_review">
                    <input type="hidden" name="review_id" value="<?= (int) $r['review_id'] ?>">
                    <button type="submit">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </section>

      <section class="dash-section">
        <h2 class="section-heading">
          Staff &amp; Admin Accounts
          <a href="manage_account.php" class="btn btn-md btn-primary">+ Add Staff Account</a>
        </h2>
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Username</th>
              <th>Full Name</th>
              <th>Email</th>
              <th>Role</th>
              <th>Status</th>
              <th>Created</th>
              <th>Manage</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
              <tr>
                <td>#<?= (int) $u['user_id'] ?></td>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars($u['fullname']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= htmlspecialchars($u['role']) ?></td>
                <td><span class="status-badge status-<?= htmlspecialchars($u['status']) ?>"><?= htmlspecialchars($u['status']) ?></span></td>
                <td><?= htmlspecialchars($u['created_at']) ?></td>
                <td>
                  <div class="status-form">
                    <a href="manage_account.php?id=<?= (int) $u['user_id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                    <?php if ((int) $u['user_id'] !== (int) $user['user_id']): ?>
                      <form method="post" action="manage_account.php" onsubmit="return confirm('Delete this account? This cannot be undone.');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="user_id" value="<?= (int) $u['user_id'] ?>">
                        <button type="submit">Delete</button>
                      </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </section>
    </div>
  </main>

</body>
</html>
