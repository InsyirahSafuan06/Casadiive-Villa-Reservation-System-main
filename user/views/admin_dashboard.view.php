<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manager Dashboard — Casadive Villa</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600;700&family=Mulish:wght@700;800&family=Poppins:wght@400;500;600&family=Raleway:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style/dashboard.css?v=<?= filemtime(__DIR__ . '/../style/dashboard.css') ?>">
</head>
<body>
  <div class="manager-shell">
    <aside class="manager-sidebar">
      <a href="admin_dashboard.php" class="manager-brand">
        <span>Casadive Villa</span>
        <small>Booking &amp; Management System</small>
      </a>
      <nav class="manager-nav" aria-label="Manager navigation">
        <a href="admin_dashboard.php" class="is-active" aria-current="page">Dashboard</a>
        <a href="#bookings">Bookings</a>
        <a href="manage_accommodation.php">Accommodation</a>
        <a href="#staff-accounts">Staff</a>
        <a href="manage_account.php?id=<?= (int) $user['user_id'] ?>">Settings</a>
        <a href="logout.php" class="manager-nav-logout">Logout</a>
      </nav>
      <div class="manager-sidebar-footer">Casadive Villa Operations</div>
    </aside>

    <div class="manager-workspace">
      <header class="manager-header">
        <span class="manager-header-label">Manager workspace</span>
        <div class="dash-user">
          <span class="who">Hi, <strong><?= htmlspecialchars($user['fullname']) ?></strong><span class="role-badge"><?= htmlspecialchars($user['role']) ?></span></span>
          <a href="manage_account.php?id=<?= (int) $user['user_id'] ?>" class="btn btn-outline">My Account</a>
          <a href="../index.php" class="btn btn-outline">View Site</a>
        </div>
      </header>

  <main class="dash-main manager-main">
    <div class="container">
      <h1 class="dash-heading">Manager Dashboard</h1>
      <p class="dash-subheading">Overview of bookings, accommodations, and staff accounts.</p>

      <?php # check flag $updated true ke tak untuk papar mesej flash 'Booking status updated' ?>
      <?php if ($updated): ?>
        <p class="flash">Booking status updated.</p>
      <?php endif; ?>
      <?php # check flag $accountCreated untuk papar mesej flash akaun staff baru dicipta ?>
      <?php if ($accountCreated): ?>
        <p class="flash">Account created.</p>
      <?php endif; ?>
      <?php # check flag $accountSaved untuk papar mesej flash akaun staff dah diupdate ?>
      <?php if ($accountSaved): ?>
        <p class="flash">Account updated.</p>
      <?php endif; ?>
      <?php # check flag $accountDeleted untuk papar mesej flash akaun staff dah dipadam ?>
      <?php if ($accountDeleted): ?>
        <p class="flash">Account deleted.</p>
      <?php endif; ?>
      <?php # check flag $accCreated untuk papar mesej flash accommodation baru dicipta ?>
      <?php if ($accCreated): ?>
        <p class="flash">Accommodation created.</p>
      <?php endif; ?>
      <?php # check flag $accSaved untuk papar mesej flash accommodation dah diupdate ?>
      <?php if ($accSaved): ?>
        <p class="flash">Accommodation updated.</p>
      <?php endif; ?>
      <?php # check flag $accDeleted untuk papar mesej flash accommodation dah dipadam ?>
      <?php if ($accDeleted): ?>
        <p class="flash">Accommodation deleted.</p>
      <?php endif; ?>
      <?php # check flag $reviewDeleted untuk papar mesej flash review dah dipadam ?>
      <?php if ($reviewDeleted): ?>
        <p class="flash">Review deleted.</p>
      <?php endif; ?>
      <?php # check flag $galleryAdded untuk papar mesej flash gambar gallery dah ditambah ?>
      <?php if ($galleryAdded): ?>
        <p class="flash">Image added to gallery.</p>
      <?php endif; ?>
      <?php # check flag $galleryDeleted untuk papar mesej flash gambar gallery dah dibuang ?>
      <?php if ($galleryDeleted): ?>
        <p class="flash">Image removed from gallery.</p>
      <?php endif; ?>
      <?php # check flag $refunded untuk papar mesej flash refund dah direkod ?>
      <?php if ($refunded): ?>
        <p class="flash">Refund recorded.</p>
      <?php endif; ?>
      <?php if ($taskCreated): ?>
        <p class="flash">Task assigned.</p>
      <?php endif; ?>
      <?php if ($taskDeleted): ?>
        <p class="flash">Task deleted.</p>
      <?php endif; ?>
      <?php if ($taskError): ?>
        <p class="flash flash-error">Could not save that task. Check the title and active staff assignment, then try again.</p>
      <?php endif; ?>

      <div class="stat-grid">
        <div class="stat-tile"><p class="stat-label">Active Staff</p><p class="stat-value"><?= count($activeStaff) ?></p></div>
        <div class="stat-tile"><p class="stat-label">Tasks</p><p class="stat-value"><?= count($tasks) ?></p></div>
        <div class="stat-tile"><p class="stat-label">Done</p><p class="stat-value"><?= $taskDone ?></p></div>
        <div class="stat-tile"><p class="stat-label">Open</p><p class="stat-value"><?= $taskOpen ?></p></div>
      </div>

      <?php require __DIR__ . '/current_occupancy.view.php'; ?>

      <section class="dash-section">
        <h2>Staff Tasks</h2>
        <?php if ($activeStaff): ?>
          <form method="post" class="task-create-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add_task">
            <div>
              <label for="task-title">Task</label>
              <input id="task-title" type="text" name="title" maxlength="255" required>
            </div>
            <div>
              <label for="task-assignee">Assign to</label>
              <select id="task-assignee" name="assigned_to" required>
                <option value="">Select active staff</option>
                <?php foreach ($activeStaff as $staffMember): ?>
                  <option value="<?= (int) $staffMember['user_id'] ?>"><?= htmlspecialchars($staffMember['fullname']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <button type="submit" class="btn btn-primary">Assign Task</button>
          </form>
        <?php else: ?>
          <p class="notice-info">There are no active staff accounts to assign tasks to.</p>
        <?php endif; ?>

        <table>
          <thead>
            <tr><th>Task</th><th>Staff</th><th>Status</th><th>Created</th><th>Action</th></tr>
          </thead>
          <tbody>
            <?php if (!$tasks): ?>
              <tr class="empty-row"><td colspan="5">No tasks assigned yet.</td></tr>
            <?php else: foreach ($tasks as $task): ?>
              <tr>
                <td><?= htmlspecialchars($task['title']) ?></td>
                <td><?= htmlspecialchars($task['staff_name'] ?? 'Unassigned') ?></td>
                <td><span class="status-badge status-<?= htmlspecialchars($task['status']) ?>"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $task['status']))) ?></span></td>
                <td><?= htmlspecialchars($task['created_at']) ?></td>
                <td>
                  <form method="post" onsubmit="return confirm('Delete this task?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete_task">
                    <input type="hidden" name="task_id" value="<?= (int) $task['task_id'] ?>">
                    <button type="submit">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </section>

      <section class="dash-section" id="bookings">
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
            <?php # check array $bookings kosong ke tak, kalau kosong papar 'No bookings yet' ?>
            <?php if (!$bookings): ?>
              <tr class="empty-row"><td colspan="14">No bookings yet.</td></tr>
            <?php else: /* loop setiap booking dalam $bookings untuk papar dalam table & tentukan notification button */ foreach ($bookings as $b):
              # calling function date() & strtotime() that assign to variable name $reminderDue untuk check kalau check-in esok (reminder due)
              $reminderDue = $b['check_in'] === date('Y-m-d', strtotime('+1 day'));

              # set default value notif type/label/class sebelum tentukan ikut status booking
              $notifType = null;
              $notifLabel = null;
              $notifClass = 'btn-primary';

              # check booking_status & $reminderDue untuk tentukan jenis notification (reminder, pending, confirmed, checked-out, cancelled) yang patut dihantar
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

              # ambil value dari $sentLookup guna ternary that assign to variable name $alreadySentAt untuk check bila notification jenis ni last kali dihantar
              $alreadySentAt = $notifType ? ($sentLookup[$b['booking_id']][$notifType] ?? null) : null;
              ?>
              <?php # ternary check $reminderDue untuk tambah class 'tr-due' kat row bila check-in esok ?>
              <tr<?= $reminderDue ? ' class="tr-due"' : '' ?>>
                <?php # calling function format_booking_ref() & htmlspecialchars() untuk papar booking ID dalam format rujukan ?>
                <td><?= htmlspecialchars(format_booking_ref((int) $b['booking_id'])) ?></td>
                <td><?= htmlspecialchars($b['full_name']) ?></td>
                <td><?= htmlspecialchars($b['phone']) ?></td>
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
                <?php # calling function number_format() untuk papar deposit amount dengan 2 titik perpuluhan ?>
                <td><?= number_format((float) $b['deposit_amount'], 2) ?></td>
                <?php # calling function format_status() & htmlspecialchars() untuk papar status booking dalam label senang faham ?>
                <td><span class="status-badge status-<?= htmlspecialchars($b['booking_status']) ?>"><?= htmlspecialchars(format_status($b['booking_status'])) ?></span></td>
                <td>
                  <?php # calling function payment_needs_refund() untuk check kalau booking ni perlu refund ?>
                  <?php if (payment_needs_refund($b['booking_status'], $b['latest_payment_status'])): ?>
                    <span class="status-badge status-refund_due">Refund Due</span>
                    <form class="refund-form" method="post">
                      <?php # calling function csrf_field() untuk letak token keselamatan dalam form refund ?>
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="process_refund">
                      <input type="hidden" name="booking_id" value="<?= (int) $b['booking_id'] ?>">
                      <?php # calling function number_format() untuk set default value refund amount dalam input ?>
                      <input type="number" name="refund_amount" min="0" step="0.01" value="<?= number_format((float) ($b['amount_paid'] ?? $b['deposit_amount']), 2, '.', '') ?>" required>
                      <button type="submit" class="btn btn-sm btn-outline">Mark Refunded</button>
                    </form>
                  <?php elseif ($b['latest_payment_status']): ?>
                    <?= htmlspecialchars(ucfirst($b['latest_payment_status'])) ?>
                  <?php else: ?>
                    <span class="text-muted">No record</span>
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

                <td>
                <?php # check ada $notifType untuk papar butang notification WhatsApp yang sesuai ?>
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

      <section class="dash-section" id="staff-accounts">
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
              <th>Weekday / Night (RM)</th>
              <th>Weekend / Night (RM)</th>
              <th>Capacity</th>
              <th>Status</th>
              <th>Manage</th>
            </tr>
          </thead>
          <tbody>
            <?php # loop setiap accommodation dalam $accommodations untuk papar dalam table ?>
            <?php foreach ($accommodations as $a): ?>
              <tr>
                <td>#<?= (int) $a['accommodation_id'] ?></td>
                <td><?= htmlspecialchars($a['accommodation_name']) ?></td>
                <td><?= htmlspecialchars($a['accommodation_type']) ?></td>
                <?php # calling function number_format() untuk papar harga accommodation dengan 2 titik perpuluhan ?>
                <td><?= number_format((float) $a['price'], 2) ?></td>
                <td><?= $a['price_weekend'] !== null ? number_format((float) $a['price_weekend'], 2) : '—' ?></td>
                <td><?= (int) $a['capacity'] ?></td>
                <td><span class="status-badge status-<?= htmlspecialchars($a['status']) ?>"><?= htmlspecialchars($a['status']) ?></span></td>
                <td>
                  <div class="status-form">
                    <a href="manage_accommodation.php?id=<?= (int) $a['accommodation_id'] ?>" class="btn btn-sm btn-outline">Edit Prices &amp; Rates</a>
                    <form method="post" action="manage_accommodation.php" onsubmit="return confirm('Delete this accommodation? This cannot be undone.');">
                      <?php # calling function csrf_field() untuk letak token keselamatan dalam form delete accommodation ?>
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
            <?php # check array $reviews kosong, kalau tak loop setiap review dalam $reviews untuk papar dalam table ?>
            <?php if (!$reviews): ?>
              <tr class="empty-row"><td colspan="9">No reviews yet.</td></tr>
            <?php else: foreach ($reviews as $r): ?>
              <tr>
                <td>#<?= (int) $r['review_id'] ?></td>
                <?php # check $r['full_name'] wujud ke tak, kalau tak papar 'Unverified' ?>
                <td><?= $r['full_name'] !== null ? htmlspecialchars($r['full_name']) : '<span class="text-muted">Unverified</span>' ?></td>
                <?php # check $r['display_name'] wujud ke tak, kalau tak papar 'Anonymous' ?>
                <td><?= $r['display_name'] ? htmlspecialchars($r['display_name']) : '<span class="text-muted">Anonymous</span>' ?></td>
                <?php # check $r['booking_id'] wujud ke tak, kalau ada calling function format_booking_ref() untuk papar rujukan booking ?>
                <td><?= $r['booking_id'] !== null ? htmlspecialchars(format_booking_ref((int) $r['booking_id'])) : '<span class="text-muted">—</span>' ?></td>
                <td><?= (int) $r['rating'] ?> &#9733;</td>
                <td><?= htmlspecialchars($r['comment'] ?: '—') ?></td>
                <td>
                  <?php # check review ada gambar ke tak untuk papar thumbnail ?>
                  <?php if ($r['image_path']): ?>
                    <a href="../<?= htmlspecialchars($r['image_path']) ?>" target="_blank" rel="noopener">
                      <img src="../<?= htmlspecialchars($r['image_path']) ?>" alt="Review photo" style="width:56px;height:56px;object-fit:cover;border-radius:6px;">
                    </a>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <?php # calling function date() & strtotime() untuk tukar format tarikh review ke d M Y ?>
                <td><?= htmlspecialchars(date('d M Y', strtotime($r['review_date']))) ?></td>
                <td>
                  <form method="post" onsubmit="return confirm('Delete this review? This cannot be undone.');">
                    <?php # calling function csrf_field() untuk letak token keselamatan dalam form delete review ?>
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
          Staff &amp; Manager Accounts
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
            <?php # loop setiap staff dalam $users untuk papar dalam table ?>
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
                    <?php # check akaun ni bukan akaun sendiri sblm papar butang delete, elak manager delete diri sendiri ?>
                    <?php if ((int) $u['user_id'] !== (int) $user['user_id']): ?>
                      <form method="post" action="manage_account.php" onsubmit="return confirm('Delete this account? This cannot be undone.');">
                        <?php # calling function csrf_field() untuk letak token keselamatan dalam form delete account ?>
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

      <section class="dash-section">
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
  </main>
    </div>
  </div>

</body>
</html>
