<?php
/** @var string $pageTitle */
/** @var string $content */
/** @var string $activeNav */
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | Ploofoodle</title>
  <link rel="stylesheet" href="/Pandipoodle/Ploofoodle/public/assets/admin.css" />
</head>
<body>
  <div class="admin-shell">
    <?php require PLOO_BASE_PATH . '/src/Views/partials/sidebar.php'; ?>
    <div class="main">
      <?php require PLOO_BASE_PATH . '/src/Views/partials/topbar.php'; ?>
      <main class="content">
        <?php if (is_array($flash ?? null)): ?>
          <div class="flash <?= (($flash['type'] ?? 'success') === 'error') ? 'error' : 'success' ?>">
            <?= htmlspecialchars((string)($flash['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
          </div>
        <?php endif; ?>
        <?= $content ?>
      </main>
    </div>
  </div>
</body>
</html>
