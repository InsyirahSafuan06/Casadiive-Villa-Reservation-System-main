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

<?php include __DIR__ . '/../../includes/footer.php'; ?>

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
