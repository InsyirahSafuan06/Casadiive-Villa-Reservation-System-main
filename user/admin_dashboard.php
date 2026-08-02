<?php
/**
 * Admin dashboard page.
 * This file gives administrators a summary of bookings, accounts, and accommodation management.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login(['admin']);

$user = current_user();
$validStatuses = ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled'];
$updated = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
    $newStatus = $_POST['booking_status'] ?? '';

    if (csrf_verify() && $bookingId && in_array($newStatus, $validStatuses, true)) {
        $stmt = $pdo->prepare('UPDATE booking SET booking_status = :status WHERE booking_id = :id');
        $stmt->execute(['status' => $newStatus, 'id' => $bookingId]);
    }

    header('Location: admin_dashboard.php?updated=1');
    exit;
}
$updated = isset($_GET['updated']);
$accountCreated = isset($_GET['created']);
$accountSaved = isset($_GET['saved']);
$accountDeleted = isset($_GET['deleted']);
$accCreated = isset($_GET['acccreated']);
$accSaved = isset($_GET['accsaved']);
$accDeleted = isset($_GET['accdeleted']);

$stats = [
    'total_bookings' => (int) $pdo->query('SELECT COUNT(*) FROM booking')->fetchColumn(),
    'pending_bookings' => (int) $pdo->query("SELECT COUNT(*) FROM booking WHERE booking_status = 'pending'")->fetchColumn(),
    'revenue' => (float) $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM booking WHERE booking_status IN ('confirmed','checked_in','checked_out')")->fetchColumn(),
    'total_users' => (int) $pdo->query('SELECT COUNT(*) FROM user')->fetchColumn(),
];

$bookings = $pdo->query(
    "SELECT b.booking_id, c.full_name, c.phone, b.check_in, b.check_out, b.total_guest,
            b.total_amount, b.deposit_amount, b.booking_status,
            GROUP_CONCAT(a.accommodation_name SEPARATOR ', ') AS accommodations,
            (SELECT p.payment_status FROM payment p WHERE p.booking_id = b.booking_id ORDER BY p.payment_id DESC LIMIT 1) AS latest_payment_status
     FROM booking b
     JOIN customer c ON c.customer_id = b.customer_id
     LEFT JOIN booking_item bi ON bi.booking_id = b.booking_id
     LEFT JOIN accommodation a ON a.accommodation_id = bi.accommodation_id
     GROUP BY b.booking_id
     ORDER BY b.booking_id DESC
     LIMIT 50"
)->fetchAll();

$accommodations = $pdo->query(
    'SELECT accommodation_id, accommodation_name, accommodation_type, price, capacity, status
     FROM accommodation ORDER BY accommodation_type, accommodation_id'
)->fetchAll();

$users = $pdo->query(
    'SELECT user_id, username, fullname, email, role, status, created_at FROM user ORDER BY user_id'
)->fetchAll();
?>
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
        <table>
          <thead>
            <tr>
              <th>#</th>
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
                <td><?= htmlspecialchars($b['accommodations'] ?? '—') ?></td>
                <td><?= htmlspecialchars($b['check_in']) ?></td>
                <td><?= htmlspecialchars($b['check_out']) ?></td>
                <td><?= (int) $b['total_guest'] ?></td>
                <td><?= number_format((float) $b['total_amount'], 2) ?></td>
                <td><?= number_format((float) $b['deposit_amount'], 2) ?></td>
                <td><span class="status-badge status-<?= htmlspecialchars($b['booking_status']) ?>"><?= htmlspecialchars(format_status($b['booking_status'])) ?></span></td>
                <td><?= $b['latest_payment_status'] ? htmlspecialchars(ucfirst($b['latest_payment_status'])) : '<span style="color:#999;">No record</span>' ?></td>
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
        <h2 style="display:flex;align-items:center;justify-content:space-between;gap:16px;">
          Accommodations
          <a href="manage_accommodation.php" class="btn btn-primary" style="padding:10px 20px;font-size:13px;">+ Add Accommodation</a>
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
                    <a href="manage_accommodation.php?id=<?= (int) $a['accommodation_id'] ?>" class="btn btn-outline" style="padding:6px 14px;font-size:13px;">Edit</a>
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
        <h2 style="display:flex;align-items:center;justify-content:space-between;gap:16px;">
          Staff &amp; Admin Accounts
          <a href="manage_account.php" class="btn btn-primary" style="padding:10px 20px;font-size:13px;">+ Add Staff Account</a>
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
                    <a href="manage_account.php?id=<?= (int) $u['user_id'] ?>" class="btn btn-outline" style="padding:6px 14px;font-size:13px;">Edit</a>
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
