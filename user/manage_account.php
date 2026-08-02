<?php
/**
 * Account management page.
 * Admins can create, edit, or delete staff and admin accounts from this page.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login(['admin']);

$currentUser = current_user();
$validRoles = ['admin', 'staff'];
$validStatuses = ['active', 'inactive', 'suspended'];

$editId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: null;
$editing = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM user WHERE user_id = :id');
    $stmt->execute(['id' => $editId]);
    $editing = $stmt->fetch();
    if (!$editing) {
        header('Location: admin_dashboard.php');
        exit;
    }
}
$isSelfEdit = $editing && (int) $editing['user_id'] === (int) $currentUser['user_id'];

$errors = [];
$old = [
    'username' => $editing['username'] ?? '',
    'fullname' => $editing['fullname'] ?? '',
    'email' => $editing['email'] ?? '',
    'phone' => $editing['phone'] ?? '',
    'role' => $editing['role'] ?? 'staff',
    'status' => $editing['status'] ?? 'active',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please try again.';
    } elseif ($action === 'delete') {
        $targetId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $stmt = $pdo->prepare('SELECT * FROM user WHERE user_id = :id');
        $stmt->execute(['id' => $targetId]);
        $target = $stmt->fetch();

        if (!$target) {
            $errors[] = 'Account not found.';
        } elseif ($targetId === (int) $currentUser['user_id']) {
            $errors[] = 'You cannot delete your own account.';
        } elseif ($target['role'] === 'admin' && $target['status'] === 'active') {
            $activeAdmins = (int) $pdo->query("SELECT COUNT(*) FROM user WHERE role = 'admin' AND status = 'active'")->fetchColumn();
            if ($activeAdmins <= 1) {
                $errors[] = 'At least one active admin account must remain.';
            }
        }

        if (!$errors) {
            $pdo->prepare('DELETE FROM user WHERE user_id = :id')->execute(['id' => $targetId]);
            header('Location: admin_dashboard.php?deleted=1');
            exit;
        }
    } else {
        $old['username'] = trim((string) ($_POST['username'] ?? ''));
        $old['fullname'] = trim((string) ($_POST['fullname'] ?? ''));
        $old['email'] = trim((string) ($_POST['email'] ?? ''));
        $old['phone'] = trim((string) ($_POST['phone'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($isSelfEdit) {
            // Never trust the client for your own role/status — you can't promote,
            // demote, or deactivate yourself through this form even if the fields
            // were tampered with; another admin must do that instead.
            $old['role'] = $editing['role'];
            $old['status'] = $editing['status'];
        } else {
            $old['role'] = (string) ($_POST['role'] ?? '');
            $old['status'] = (string) ($_POST['status'] ?? '');
        }

        if ($old['username'] === '' || !preg_match('/^[a-zA-Z0-9_.]{3,50}$/', $old['username'])) {
            $errors[] = 'Username must be 3-50 characters (letters, numbers, dot, underscore only).';
        }
        if ($old['fullname'] === '') {
            $errors[] = 'Full name is required.';
        }
        if ($old['email'] === '' || !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        }
        if (!in_array($old['role'], $validRoles, true)) {
            $errors[] = 'Please select a valid role.';
        }
        if (!in_array($old['status'], $validStatuses, true)) {
            $errors[] = 'Please select a valid status.';
        }
        if (!$editing && strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($editing && $password !== '' && strlen($password) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        }

        if (!$errors) {
            $stmt = $pdo->prepare('SELECT user_id FROM user WHERE (username = :username OR email = :email) AND user_id != :self');
            $stmt->execute([
                'username' => $old['username'],
                'email' => $old['email'],
                'self' => $editing['user_id'] ?? 0,
            ]);
            if ($stmt->fetch()) {
                $errors[] = 'That username or email is already in use by another account.';
            }
        }

        if (!$errors && $editing && $editing['role'] === 'admin' && $editing['status'] === 'active') {
            $willStillBeActiveAdmin = $old['role'] === 'admin' && $old['status'] === 'active';
            if (!$willStillBeActiveAdmin) {
                $activeAdmins = (int) $pdo->query("SELECT COUNT(*) FROM user WHERE role = 'admin' AND status = 'active'")->fetchColumn();
                if ($activeAdmins <= 1) {
                    $errors[] = 'At least one active admin account must remain — change another admin first.';
                }
            }
        }

        if (!$errors) {
            if ($editing) {
                if ($password !== '') {
                    $stmt = $pdo->prepare(
                        'UPDATE user SET username = :username, fullname = :fullname, email = :email, phone = :phone,
                         role = :role, status = :status, password = :password WHERE user_id = :id'
                    );
                    $stmt->execute([
                        'username' => $old['username'],
                        'fullname' => $old['fullname'],
                        'email' => $old['email'],
                        'phone' => $old['phone'] !== '' ? $old['phone'] : null,
                        'role' => $old['role'],
                        'status' => $old['status'],
                        'password' => password_hash($password, PASSWORD_DEFAULT),
                        'id' => $editing['user_id'],
                    ]);
                } else {
                    $stmt = $pdo->prepare(
                        'UPDATE user SET username = :username, fullname = :fullname, email = :email, phone = :phone,
                         role = :role, status = :status WHERE user_id = :id'
                    );
                    $stmt->execute([
                        'username' => $old['username'],
                        'fullname' => $old['fullname'],
                        'email' => $old['email'],
                        'phone' => $old['phone'] !== '' ? $old['phone'] : null,
                        'role' => $old['role'],
                        'status' => $old['status'],
                        'id' => $editing['user_id'],
                    ]);
                }
                header('Location: admin_dashboard.php?saved=1');
                exit;
            }

            $stmt = $pdo->prepare(
                'INSERT INTO user (username, password, fullname, email, phone, role, status)
                 VALUES (:username, :password, :fullname, :email, :phone, :role, :status)'
            );
            $stmt->execute([
                'username' => $old['username'],
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'fullname' => $old['fullname'],
                'email' => $old['email'],
                'phone' => $old['phone'] !== '' ? $old['phone'] : null,
                'role' => $old['role'],
                'status' => $old['status'],
            ]);
            header('Location: admin_dashboard.php?created=1');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $editing ? 'Edit Account' : 'Add Staff Account' ?> — Casadive Villa</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600;700&family=Mulish:wght@700;800&family=Poppins:wght@400;500;600&family=Raleway:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style/dashboard.css">
</head>
<body>

  <header class="dash-topbar">
    <div class="container">
      <div class="brand">Casadive Villa</div>
      <div class="dash-user">
        <span class="who">Hi, <strong><?= htmlspecialchars($currentUser['fullname']) ?></strong><span class="role-badge"><?= htmlspecialchars($currentUser['role']) ?></span></span>
        <a href="admin_dashboard.php" class="btn btn-outline">Back to Dashboard</a>
        <a href="logout.php" class="btn btn-primary">Logout</a>
      </div>
    </div>
  </header>

  <main class="dash-main">
    <div class="container">
      <h1 class="dash-heading"><?= $editing ? 'Edit Account' : 'Add Staff Account' ?></h1>
      <p class="dash-subheading"><?= $editing ? 'Update account details, role, status, or reset the password.' : 'Create a login for a new staff member (or admin).' ?></p>

      <?php if ($errors): ?>
        <div style="background:#fdecea;border:1px solid #f5c2c0;color:#9a3226;border-radius:8px;padding:16px 20px;margin-bottom:28px;font-family:'Raleway',sans-serif;font-weight:600;max-width:560px;">
          <ul style="margin-left:18px;">
            <?php foreach ($errors as $error): ?>
              <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <section class="dash-section" style="max-width:560px;">
        <form method="post" style="display:flex;flex-direction:column;gap:18px;font-family:'Raleway',sans-serif;">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">

          <div>
            <label style="display:block;font-weight:600;font-size:14px;color:var(--brown-price);margin-bottom:6px;">Username</label>
            <input type="text" name="username" value="<?= htmlspecialchars($old['username']) ?>" style="width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:6px;font-family:inherit;" required>
          </div>

          <div>
            <label style="display:block;font-weight:600;font-size:14px;color:var(--brown-price);margin-bottom:6px;">Full Name</label>
            <input type="text" name="fullname" value="<?= htmlspecialchars($old['fullname']) ?>" style="width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:6px;font-family:inherit;" required>
          </div>

          <div>
            <label style="display:block;font-weight:600;font-size:14px;color:var(--brown-price);margin-bottom:6px;">Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($old['email']) ?>" style="width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:6px;font-family:inherit;" required>
          </div>

          <div>
            <label style="display:block;font-weight:600;font-size:14px;color:var(--brown-price);margin-bottom:6px;">Phone</label>
            <input type="tel" name="phone" value="<?= htmlspecialchars($old['phone']) ?>" style="width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:6px;font-family:inherit;">
          </div>

          <div>
            <label style="display:block;font-weight:600;font-size:14px;color:var(--brown-price);margin-bottom:6px;"><?= $editing ? 'New Password (leave blank to keep current)' : 'Password' ?></label>
            <input type="password" name="password" style="width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:6px;font-family:inherit;" autocomplete="new-password" <?= $editing ? '' : 'required' ?>>
          </div>

          <?php if ($isSelfEdit): ?>
            <p style="background:var(--sand-light,#FFF0D3);border-radius:8px;padding:12px 16px;font-size:13px;color:var(--brown-price);">
              You're editing your own account — role (<strong><?= htmlspecialchars(ucfirst($old['role'])) ?></strong>) and status
              (<strong><?= htmlspecialchars(ucfirst($old['status'])) ?></strong>) can't be changed here. Ask another admin if that's needed.
            </p>
          <?php else: ?>
          <div style="display:flex;gap:18px;">
            <div style="flex:1;">
              <label style="display:block;font-weight:600;font-size:14px;color:var(--brown-price);margin-bottom:6px;">Role</label>
              <select name="role" style="width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:6px;font-family:inherit;">
                <?php foreach ($validRoles as $role): ?>
                  <option value="<?= $role ?>" <?= $old['role'] === $role ? 'selected' : '' ?>><?= ucfirst($role) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div style="flex:1;">
              <label style="display:block;font-weight:600;font-size:14px;color:var(--brown-price);margin-bottom:6px;">Status</label>
              <select name="status" style="width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:6px;font-family:inherit;">
                <?php foreach ($validStatuses as $status): ?>
                  <option value="<?= $status ?>" <?= $old['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <?php endif; ?>

          <div style="display:flex;gap:12px;margin-top:8px;">
            <button type="submit" class="btn btn-primary" style="flex:1;"><?= $editing ? 'Save Changes' : 'Create Account' ?></button>
            <a href="admin_dashboard.php" class="btn btn-outline" style="flex:1;text-align:center;">Cancel</a>
          </div>
        </form>
      </section>
    </div>
  </main>

</body>
</html>
