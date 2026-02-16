<?php
$row = $row ?? [];

ob_start();
?>
<section class="card">
  <div class="card-head">
    <h3>Release Manifest Details</h3>
    <a href="/Pandipoodle/Ploofoodle/public/index.php?_route=/admin/releases">Back to list</a>
  </div>

  <?php if (empty($row)): ?>
    <p class="muted">Manifest not found.</p>
  <?php else: ?>
    <div class="grid-2">
      <div class="card">
        <p><strong>Platform:</strong> <?= htmlspecialchars((string)$row['platform'], ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Channel:</strong> <?= htmlspecialchars((string)$row['channel'], ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Status:</strong> <span class="badge <?= htmlspecialchars((string)$row['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$row['status'], ENT_QUOTES, 'UTF-8') ?></span></p>
        <p><strong>Latest:</strong> <?= htmlspecialchars((string)$row['latest_version'], ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Min Supported:</strong> <?= htmlspecialchars((string)$row['min_supported_version'], ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Mode:</strong> <span class="badge <?= htmlspecialchars((string)$row['update_mode'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$row['update_mode'], ENT_QUOTES, 'UTF-8') ?></span></p>
      </div>
      <div class="card">
        <p><strong>Rollout:</strong> <?= (int)($row['rollout_percent'] ?? 0) ?>%</p>
        <p><strong>ETag:</strong> <code><?= htmlspecialchars((string)($row['etag'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code></p>
        <p><strong>Published At:</strong> <?= htmlspecialchars((string)($row['published_at'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Updated At:</strong> <?= htmlspecialchars((string)($row['updated_at'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Download URL:</strong><br><a href="<?= htmlspecialchars((string)($row['download_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noreferrer">Open</a></p>
      </div>
    </div>
  <?php endif; ?>
</section>
<?php
$content = (string)ob_get_clean();
$pageTitle = 'View Release';
$activeNav = 'releases';
require PLOO_BASE_PATH . '/src/Views/layout.php';
