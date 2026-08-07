<?php
/**
 * Halaman log masuk untuk pengguna staf dan admin.
 * Halaman ini mengendalikan pengesahan dan ubah hala pengguna ke dashboard mereka selepas berjaya log masuk.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// dah login ke? kalau ya, takyah tunjuk borang, terus hantar ke dashboard yang betul
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
        // attempt_login() check username/password kat table `user`, kalau betul,
        // dia terus simpan pengguna tu dalam session untuk kita
        $user = $oldUsername !== '' && $password !== '' ? attempt_login($pdo, $oldUsername, $password) : false;

        if ($user) {
            header('Location: ' . ($user['role'] === 'admin' ? 'admin_dashboard.php' : 'staff_dashboard.php'));
            exit;
        }

        // sengaja buat mesej error kabur — tak cakap "username salah" ke "password salah"
        // supaya orang jahat tak boleh guna page ni untuk teka username mana yang wujud
        $error = 'Invalid username or password.';
    }
}

$base = '../'; // page ni dalam folder user/, naik satu tahap untuk pergi root
// tambah "?v=" + masa fail last edit, supaya browser tak simpan CSS lama dalam cache
$loginCssHref = 'style/login.css?v=' . (is_file(__DIR__ . '/style/login.css') ? filemtime(__DIR__ . '/style/login.css') : time());
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Sign In — Casadive Villa</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600;700&family=Poppins:wght@500;700&family=Raleway:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= htmlspecialchars($loginCssHref) ?>">
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
            </button>
          </div>
        </div>

        <button type="submit" class="login-submit">Sign In</button>
      </form>
    </div>
  </section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

  <script>
    // butang mata untuk toggle tunjuk/sorok password bila diklik
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
