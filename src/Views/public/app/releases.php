<?php
$rows = is_array($rows ?? null) ? $rows : [];
$latest = is_array($latest ?? null) ? $latest : null;
$history = is_array($history ?? null) ? $history : [];
$platform = (string)($platform ?? '');
$channel = (string)($channel ?? 'stable');
$search = (string)($search ?? '');

$fmtDate = static function (string $value): string {
  if ($value === '') return 'Unknown date';
  $ts = strtotime($value);
  if ($ts === false) return $value;
  return gmdate('Y-m-d', $ts);
};
$fmtVersion = static function (string $value): string {
  $v = trim($value);
  if ($v === '') return 'v0.0.0';
  return str_starts_with(strtolower($v), 'v') ? $v : ('v' . $v);
};

ob_start();
?>
<section class="section">
  <div class="container surface panel">
    <h1>Release Hub</h1>
    <p class="muted">Track app versions, highlights, and known issues.</p>

    <form class="grid-3" method="get" action="<?= htmlspecialchars(ploo_route_url('/app/releases'), ENT_QUOTES, 'UTF-8') ?>" style="margin-bottom:16px">
      <input type="hidden" name="_route" value="/app/releases" />
      <select name="platform">
        <option value="">All platforms</option>
        <?php foreach (['android', 'ios', 'web'] as $p): ?>
          <option value="<?= htmlspecialchars($p, ENT_QUOTES, 'UTF-8') ?>" <?= $platform === $p ? 'selected' : '' ?>><?= htmlspecialchars($p, ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
      <select name="channel">
        <?php foreach (['stable', 'beta', 'internal'] as $c): ?>
          <option value="<?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?>" <?= $channel === $c ? 'selected' : '' ?>><?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
      <input type="text" name="q" placeholder="Search version..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" />
      <button class="btn btn-secondary" type="submit">Filter</button>
    </form>

    <?php if ($rows === []): ?>
      <p class="muted">No published release records found.</p>
    <?php else: ?>
      <?php if (is_array($latest)): ?>
        <section class="section" style="padding-top:8px">
          <h2>Latest release</h2>
          <div class="surface panel">
            <article class="card" aria-label="Release <?= htmlspecialchars((string)$latest['latest_version'], ENT_QUOTES, 'UTF-8') ?>">
              <div class="badges">
                <span class="badge latest">Latest</span>
                <span class="badge"><?= htmlspecialchars($fmtVersion((string)$latest['latest_version']), ENT_QUOTES, 'UTF-8') ?></span>
                <span class="badge"><?= htmlspecialchars($fmtDate((string)($latest['updated_at'] ?? '')), ENT_QUOTES, 'UTF-8') ?></span>
                <span class="badge"><?= htmlspecialchars((string)$latest['platform'], ENT_QUOTES, 'UTF-8') ?>/<?= htmlspecialchars((string)$latest['channel'], ENT_QUOTES, 'UTF-8') ?></span>
              </div>
              <h3><?= htmlspecialchars($fmtVersion((string)$latest['latest_version']), ENT_QUOTES, 'UTF-8') ?> release notes</h3>
              <ul>
                <li>Update mode: <?= htmlspecialchars((string)$latest['update_mode'], ENT_QUOTES, 'UTF-8') ?></li>
                <li>Source: <?= htmlspecialchars((string)($latest['update_source'] ?? 'web'), ENT_QUOTES, 'UTF-8') ?></li>
                <li>Rollout: <?= (int)($latest['rollout_percent'] ?? 100) ?>%</li>
                <li>Minimum supported version: <?= htmlspecialchars((string)$latest['min_supported_version'], ENT_QUOTES, 'UTF-8') ?></li>
              </ul>
              <p class="muted"><strong>Known issues:</strong> None reported.</p>
              <div class="cta-row cta-row-compact">
                <a class="btn btn-secondary btn-sm" href="<?= htmlspecialchars(ploo_route_url('/app/releases/notes', ['id' => (int)$latest['id']]), ENT_QUOTES, 'UTF-8') ?>">View notes</a>
              </div>
            </article>
          </div>
        </section>
      <?php endif; ?>

      <section class="section" style="padding-top:0">
        <h2>Release history</h2>
        <div class="grid-2">
          <?php if ($history === []): ?>
            <p class="muted">No additional release history yet.</p>
          <?php else: ?>
            <?php foreach ($history as $row): ?>
              <article class="card" aria-label="Release <?= htmlspecialchars((string)$row['latest_version'], ENT_QUOTES, 'UTF-8') ?>">
                <div class="badges">
                  <span class="badge"><?= htmlspecialchars($fmtVersion((string)$row['latest_version']), ENT_QUOTES, 'UTF-8') ?></span>
                  <span class="badge"><?= htmlspecialchars($fmtDate((string)($row['updated_at'] ?? '')), ENT_QUOTES, 'UTF-8') ?></span>
                  <span class="badge"><?= htmlspecialchars((string)$row['platform'], ENT_QUOTES, 'UTF-8') ?>/<?= htmlspecialchars((string)$row['channel'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <h3><?= htmlspecialchars($fmtVersion((string)$row['latest_version']), ENT_QUOTES, 'UTF-8') ?> release notes</h3>
                <ul>
                  <li>Update mode: <?= htmlspecialchars((string)$row['update_mode'], ENT_QUOTES, 'UTF-8') ?></li>
                  <li>Source: <?= htmlspecialchars((string)($row['update_source'] ?? 'web'), ENT_QUOTES, 'UTF-8') ?></li>
                  <li>Rollout: <?= (int)($row['rollout_percent'] ?? 100) ?>%</li>
                </ul>
                <div class="cta-row cta-row-compact">
                  <a class="btn btn-secondary btn-sm" href="<?= htmlspecialchars(ploo_route_url('/app/releases/notes', ['id' => (int)$row['id']]), ENT_QUOTES, 'UTF-8') ?>">View notes</a>
                </div>
              </article>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </section>
    <?php endif; ?>
  </div>
</section>
<?php
$content = (string)ob_get_clean();
$activePage = 'releases';
require PLOO_BASE_PATH . '/src/Views/public/app/layout.php';
