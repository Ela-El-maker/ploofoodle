<?php
$mode = $mode ?? 'create';
$row = $row ?? [];
$allowedPlatforms = $allowedPlatforms ?? ['android', 'ios', 'web'];
$allowedChannels = $allowedChannels ?? ['stable', 'beta', 'internal'];

ob_start();
?>
<section class="card">
  <div class="card-head">
    <h3><?= $mode === 'edit' ? 'Edit Release' : 'Create Release' ?></h3>
    <a href="/Pandipoodle/Ploofoodle/public/index.php?_route=/admin/releases">Back to list</a>
  </div>

  <form method="post" action="/Pandipoodle/Ploofoodle/public/index.php?_route=/admin/releases">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars((string)$csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
    <div class="form-grid">
      <div class="field">
        <label>Platform</label>
        <?php $platformValue = (string)($row['platform'] ?? 'android'); ?>
        <select name="platform" required>
          <?php foreach ($allowedPlatforms as $platform): ?>
            <option value="<?= htmlspecialchars((string)$platform, ENT_QUOTES, 'UTF-8') ?>" <?= $platformValue === $platform ? 'selected' : '' ?>>
              <?= htmlspecialchars((string)$platform, ENT_QUOTES, 'UTF-8') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Channel</label>
        <?php $channelValue = (string)($row['channel'] ?? 'stable'); ?>
        <select name="channel" required>
          <?php foreach ($allowedChannels as $channel): ?>
            <option value="<?= htmlspecialchars((string)$channel, ENT_QUOTES, 'UTF-8') ?>" <?= $channelValue === $channel ? 'selected' : '' ?>>
              <?= htmlspecialchars((string)$channel, ENT_QUOTES, 'UTF-8') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label>Latest version</label><input name="latest_version" value="<?= htmlspecialchars((string)($row['latest_version'] ?? '1.0.0'), ENT_QUOTES, 'UTF-8') ?>" required /></div>
      <div class="field"><label>Min supported version</label><input name="min_supported_version" value="<?= htmlspecialchars((string)($row['min_supported_version'] ?? '1.0.0'), ENT_QUOTES, 'UTF-8') ?>" required /></div>
      <div class="field"><label>Update mode</label>
        <select name="update_mode">
          <?php $modeVal = (string)($row['update_mode'] ?? 'soft'); ?>
          <option value="soft" <?= $modeVal === 'soft' ? 'selected' : '' ?>>soft</option>
          <option value="hard" <?= $modeVal === 'hard' ? 'selected' : '' ?>>hard</option>
        </select>
      </div>
      <div class="field"><label>Rollout %</label><input type="number" min="0" max="100" name="rollout_percent" value="<?= htmlspecialchars((string)($row['rollout_percent'] ?? 100), ENT_QUOTES, 'UTF-8') ?>" /></div>
      <div class="field"><label>Download URL</label><input name="download_url" value="<?= htmlspecialchars((string)($row['download_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required /></div>
      <div class="field"><label>Release notes URL</label><input name="release_notes_url" value="<?= htmlspecialchars((string)($row['release_notes_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" /></div>
      <div class="field"><label>SHA256</label><input name="sha256" value="<?= htmlspecialchars((string)($row['sha256'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" /></div>
      <div class="field"><label>Cache TTL seconds</label><input type="number" min="60" max="86400" name="cache_ttl_seconds" value="<?= htmlspecialchars((string)($row['cache_ttl_seconds'] ?? 3600), ENT_QUOTES, 'UTF-8') ?>" /></div>
    </div>

    <?php if (!empty($row['status'])): ?>
      <p class="muted">Current status: <span class="badge <?= htmlspecialchars((string)$row['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$row['status'], ENT_QUOTES, 'UTF-8') ?></span> · Last updated: <?= htmlspecialchars((string)($row['updated_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <div class="btn-row">
      <button class="btn secondary" type="submit" name="action" value="save_draft">Save Draft</button>
      <button class="btn primary" type="submit" name="action" value="publish">Publish Now</button>
      <a class="btn danger" href="/Pandipoodle/Ploofoodle/public/index.php?_route=/admin/releases">Cancel</a>
    </div>
  </form>
</section>
<?php
$content = (string)ob_get_clean();
$pageTitle = $mode === 'edit' ? 'Edit Release' : 'Create Release';
$activeNav = 'releases';
require PLOO_BASE_PATH . '/src/Views/layout.php';
