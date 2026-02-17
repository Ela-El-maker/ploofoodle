<?php
ob_start();
?>
<section class="section">
  <div class="container surface panel">
    <h1>Release not found</h1>
    <p class="muted">The release note you requested does not exist or is not published.</p>
    <div class="cta-row">
      <a class="btn btn-primary" href="<?= htmlspecialchars(ploo_route_url('/app/releases'), ENT_QUOTES, 'UTF-8') ?>">Back to Release Hub</a>
      <a class="btn btn-secondary" href="<?= htmlspecialchars(ploo_route_url('/app'), ENT_QUOTES, 'UTF-8') ?>">Go to App Home</a>
    </div>
  </div>
</section>
<?php
$content = (string)ob_get_clean();
$activePage = 'releases';
require PLOO_BASE_PATH . '/src/Views/public/app/layout.php';
