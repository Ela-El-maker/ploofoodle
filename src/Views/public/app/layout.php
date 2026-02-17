<?php
/** @var string $pageTitle */
/** @var string $content */
/** @var string $activePage */
$activePage = $activePage ?? '';
$base = ploo_base_path();
$appAsset = $base . '/app/assets';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="description" content="Official Up-Skill app page for downloads, release notes, setup guides, and support." />
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | Up-Skill App</title>
  <link rel="stylesheet" href="<?= htmlspecialchars($appAsset . '/styles.css', ENT_QUOTES, 'UTF-8') ?>" />
</head>
<body data-page="<?= htmlspecialchars($activePage, ENT_QUOTES, 'UTF-8') ?>">
  <a class="skip-link" href="#main-content">Skip to main content</a>
  <header class="site-header">
    <div class="container nav-wrap">
      <a class="brand" href="<?= htmlspecialchars(ploo_route_url('/app'), ENT_QUOTES, 'UTF-8') ?>">Up-Skill App</a>
      <nav aria-label="Primary">
        <ul class="nav-links">
          <li><a href="<?= htmlspecialchars(ploo_route_url('/app'), ENT_QUOTES, 'UTF-8') ?>" <?= $activePage === 'landing' ? 'aria-current="page"' : '' ?>>Download</a></li>
          <li><a href="<?= htmlspecialchars(ploo_route_url('/app/releases'), ENT_QUOTES, 'UTF-8') ?>" <?= $activePage === 'releases' ? 'aria-current="page"' : '' ?>>Releases</a></li>
          <li><a href="<?= htmlspecialchars(ploo_route_url('/app/get-started'), ENT_QUOTES, 'UTF-8') ?>" <?= $activePage === 'get_started' ? 'aria-current="page"' : '' ?>>Get Started</a></li>
          <li><a href="<?= htmlspecialchars(ploo_route_url('/app/support'), ENT_QUOTES, 'UTF-8') ?>" <?= $activePage === 'support' ? 'aria-current="page"' : '' ?>>Support</a></li>
        </ul>
      </nav>
      <div class="nav-actions">
        <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars(ploo_route_url('/app/open'), ENT_QUOTES, 'UTF-8') ?>">Open App Link</a>
      </div>
    </div>
  </header>

  <main id="main-content">
    <?= $content ?>
  </main>

  <footer class="section">
    <div class="container muted">
      <p>Need help? Visit <a href="<?= htmlspecialchars(ploo_route_url('/app/support'), ENT_QUOTES, 'UTF-8') ?>">Support</a>.</p>
    </div>
  </footer>
</body>
</html>
