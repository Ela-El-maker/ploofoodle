<?php
$row = is_array($row ?? null) ? $row : [];
$rawVersion = (string)($row['latest_version'] ?? '0.0.0');
$version = str_starts_with(strtolower(trim($rawVersion)), 'v') ? trim($rawVersion) : ('v' . trim($rawVersion));
$updatedAtRaw = (string)($row['updated_at'] ?? '');
$updatedAt = $updatedAtRaw !== '' && strtotime($updatedAtRaw) !== false
  ? gmdate('Y-m-d', strtotime($updatedAtRaw))
  : ($updatedAtRaw !== '' ? $updatedAtRaw : 'Unknown date');
$downloadUrl = trim((string)(($row['distribution_url'] ?? '') !== '' ? $row['distribution_url'] : ($row['download_url'] ?? '')));

ob_start();
?>
<section class="section">
  <div class="container">
    <p><a href="<?= htmlspecialchars(ploo_route_url('/app/releases', ['platform' => (string)($row['platform'] ?? ''), 'channel' => (string)($row['channel'] ?? 'stable')]), ENT_QUOTES, 'UTF-8') ?>">&larr; Back to releases</a></p>

    <div class="surface panel">
      <article class="card" aria-label="Release <?= htmlspecialchars($version, ENT_QUOTES, 'UTF-8') ?>">
        <div class="badges">
          <span class="badge latest">Release</span>
          <span class="badge"><?= htmlspecialchars($version, ENT_QUOTES, 'UTF-8') ?></span>
          <span class="badge"><?= htmlspecialchars($updatedAt, ENT_QUOTES, 'UTF-8') ?></span>
          <span class="badge"><?= htmlspecialchars((string)($row['platform'] ?? 'n/a'), ENT_QUOTES, 'UTF-8') ?>/<?= htmlspecialchars((string)($row['channel'] ?? 'n/a'), ENT_QUOTES, 'UTF-8') ?></span>
        </div>

        <h1><?= htmlspecialchars($version, ENT_QUOTES, 'UTF-8') ?> release notes</h1>
        <p class="muted">This page follows the same release template structure used on the Release Hub.</p>

        <div class="grid-2">
          <div class="card">
            <h3>Release metadata</h3>
            <ul>
              <li>Mode: <?= htmlspecialchars((string)($row['update_mode'] ?? 'soft'), ENT_QUOTES, 'UTF-8') ?></li>
              <li>Source: <?= htmlspecialchars((string)($row['update_source'] ?? 'web'), ENT_QUOTES, 'UTF-8') ?></li>
              <li>Rollout: <?= (int)($row['rollout_percent'] ?? 100) ?>%</li>
              <li>Min supported: <?= htmlspecialchars((string)($row['min_supported_version'] ?? 'n/a'), ENT_QUOTES, 'UTF-8') ?></li>
              <li>Cache TTL: <?= (int)($row['cache_ttl_seconds'] ?? 3600) ?>s</li>
            </ul>
          </div>

          <div class="card">
            <h3>Download</h3>
            <ul>
              <?php if ($downloadUrl !== ''): ?>
                <li><a href="<?= htmlspecialchars($downloadUrl, ENT_QUOTES, 'UTF-8') ?>">Open distribution link</a></li>
              <?php else: ?>
                <li class="muted">Distribution link not configured.</li>
              <?php endif; ?>
            </ul>
          </div>
        </div>

        <div class="card" style="margin-top:12px">
          <h3>Release summary</h3>
          <ul>
            <li>This release is managed and published from Ploofoodle.</li>
            <li>Users are routed through your own release hub instead of external release pages.</li>
            <li>Hard update enforcement remains controlled by backend `426 UPGRADE_REQUIRED` policy.</li>
          </ul>
        </div>

        <p class="muted"><strong>Known issues:</strong> None reported.</p>
      </article>
    </div>
  </div>
</section>
<?php
$content = (string)ob_get_clean();
$activePage = 'releases';
require PLOO_BASE_PATH . '/src/Views/public/app/layout.php';
