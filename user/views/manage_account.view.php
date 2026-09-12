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
          <div class="form-row-2">
            <div>
              <label style="display:block;font-weight:600;font-size:14px;color:var(--brown-price);margin-bottom:6px;">Role</label>
              <select name="role" style="width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:6px;font-family:inherit;">
                <?php foreach ($validRoles as $role): ?>
                  <option value="<?= $role ?>" <?= $old['role'] === $role ? 'selected' : '' ?>><?= ucfirst($role) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
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
