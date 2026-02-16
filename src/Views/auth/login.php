<?php
$flash = $flash ?? null;
$csrfToken = $csrfToken ?? '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Ploofoodle Login</title>
  <link rel="stylesheet" href="/Pandipoodle/Ploofoodle/public/assets/admin.css" />
</head>
<body class="login-shell">
  <main class="login-wrap">
    <?php if (is_array($flash)): ?>
      <div class="flash <?= (($flash['type'] ?? 'success') === 'error') ? 'error' : 'success' ?>">
        <?= htmlspecialchars((string)($flash['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <section class="card login-card">
      <h1>Ploofoodle Admin</h1>
      <p class="muted">Sign in to manage public mobile config and releases.</p>

      <form method="post" action="/Pandipoodle/Ploofoodle/public/index.php?_route=/auth/login">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars((string)$csrfToken, ENT_QUOTES, 'UTF-8') ?>" />

        <div class="field">
          <label>Username</label>
          <input name="username" autocomplete="username" required />
        </div>

        <div class="field">
          <label>Password</label>
          <input name="password" type="password" autocomplete="current-password" required />
        </div>

        <div class="btn-row">
          <button class="btn primary" type="submit">Login</button>
        </div>
      </form>
    </section>
  </main>
</body>
</html>
