<?php
$u = $user ?? ['username' => 'guest', 'role' => 'viewer'];
$env = strtoupper((string)($_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? getenv('APP_ENV') ?? 'prod'));
?>
<header class="topbar">
  <div>
    <h1 class="topbar-title"><?= htmlspecialchars($pageTitle ?? 'Admin', ENT_QUOTES, 'UTF-8') ?></h1>
  </div>
  <div class="topbar-meta">
    <span class="badge env"><?= htmlspecialchars($env, ENT_QUOTES, 'UTF-8') ?></span>
    <span><?= htmlspecialchars((string)$u['username'], ENT_QUOTES, 'UTF-8') ?></span>
    <span>(<?= htmlspecialchars((string)$u['role'], ENT_QUOTES, 'UTF-8') ?>)</span>
    <form method="post" action="/Pandipoodle/Ploofoodle/public/index.php?_route=/auth/logout" style="display:inline">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars((string)$csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
      <button class="btn secondary" type="submit">Logout</button>
    </form>
  </div>
</header>
