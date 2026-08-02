<?php
/**
 * Login page for staff and admin users.
 * This page handles authentication and redirects users to their dashboard after a successful login.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$existingUser = current_user();
if ($existingUser) {
    header('Location: ' . ($existingUser['role'] === 'admin' ? 'admin_dashboard.php' : 'staff_dashboard.php'));
    exit;
}

$error = null;
$oldUsername = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldUsername = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!csrf_verify()) {
        $error = 'Your session expired. Please try again.';
    } else {
        $user = $oldUsername !== '' && $password !== '' ? attempt_login($pdo, $oldUsername, $password) : false;

        if ($user) {
            header('Location: ' . ($user['role'] === 'admin' ? 'admin_dashboard.php' : 'staff_dashboard.php'));
            exit;
        }

        $error = 'Invalid username or password.';
    }
}

$base = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Sign In — Casadive Villa</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600;700&family=Poppins:wght@500;700&family=Raleway:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style/login.css">
</head>
<body>

  <!-- NAVBAR -->
  <header class="navbar">
    <div class="container">
      <div class="brand">Casadive Villa</div>
      <a href="../index.php" class="btn btn-primary">Back</a>
    </div>
  </header>

  <!-- LOGIN -->
  <section class="login-hero">
    <div class="login-card">
      <h1 class="login-title">Admin Sign In</h1>
      <p class="login-sub">Secure access to reservations, villas, campsite, and reports.</p>

      <?php if ($error): ?>
        <p style="background:#fdecea;border:1px solid #f5c2c0;color:#9a3226;border-radius:8px;padding:14px 18px;font-family:'Raleway',sans-serif;font-weight:600;margin-bottom:24px;">
          <?= htmlspecialchars($error) ?>
        </p>
      <?php endif; ?>

      <form id="login-form" method="post" novalidate>
        <?= csrf_field() ?>
        <div class="login-field">
          <label for="login-username">Username</label>
          <div class="login-input-wrap">
            <input id="login-username" name="username" type="text" placeholder="Enter your username" autocomplete="username" value="<?= htmlspecialchars($oldUsername) ?>" required>
          </div>
        </div>

        <div class="login-field">
          <label for="login-password">Password</label>
          <div class="login-input-wrap">
            <input id="login-password" name="password" type="password" placeholder="Enter your password" autocomplete="current-password" required>
            <button type="button" class="toggle-password" id="toggle-password" aria-label="Show password" aria-pressed="false">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </div>

        <button type="submit" class="login-submit">Sign In</button>
      </form>
    </div>
  </section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

  <script>
    const toggleBtn = document.getElementById('toggle-password');
    const passwordInput = document.getElementById('login-password');
    toggleBtn.addEventListener('click', () => {
      const isHidden = passwordInput.type === 'password';
      passwordInput.type = isHidden ? 'text' : 'password';
      toggleBtn.setAttribute('aria-pressed', String(isHidden));
      toggleBtn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
    });
  </script>

</body>
</html>
