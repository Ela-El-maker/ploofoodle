<?php
$draftRow = is_array($draft) ? $draft : [];
$publishedRow = is_array($published) ? $published : [];
$manifest = is_array($publishedRow['manifest'] ?? null) ? $publishedRow['manifest'] : [];

ob_start();
?>
<header>
  <strong>Ploofoodle - Update Manifest</strong>
  <form method="post" action="/Pandipoodle/Ploofoodle/public/index.php?_route=/auth/logout" style="margin:0">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars((string)$csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
    <button class="btn secondary" type="submit">Logout</button>
  </form>
</header>
<main>
  <?php if (is_array($flash)): ?>
    <div class="flash <?= htmlspecialchars(($flash['type'] ?? 'success') === 'error' ? 'error' : 'success', ENT_QUOTES, 'UTF-8') ?>">
      <?= htmlspecialchars((string)($flash['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <section class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px">
      <h2 style="margin:0">Release Draft/Publish</h2>
      <a href="/Pandipoodle/Ploofoodle/public/index.php?_route=/admin/config">Go to Config</a>
    </div>

    <form method="post" action="/Pandipoodle/Ploofoodle/public/index.php?_route=/admin/releases">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars((string)$csrfToken, ENT_QUOTES, 'UTF-8') ?>" />

      <div class="row">
        <div>
          <label>Platform</label>
          <select name="platform">
            <?php foreach ($allowedPlatforms as $item): ?>
              <option value="<?= htmlspecialchars((string)$item, ENT_QUOTES, 'UTF-8') ?>" <?= $item === $platform ? 'selected' : '' ?>><?= htmlspecialchars((string)$item, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>Channel</label>
          <select name="channel">
            <?php foreach ($allowedChannels as $item): ?>
              <option value="<?= htmlspecialchars((string)$item, ENT_QUOTES, 'UTF-8') ?>" <?= $item === $channel ? 'selected' : '' ?>><?= htmlspecialchars((string)$item, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="row">
        <div><label>Schema version</label><input type="number" name="schema_version" min="1" value="<?= htmlspecialchars((string)($draftRow['schema_version'] ?? 1), ENT_QUOTES, 'UTF-8') ?>" /></div>
        <div><label>Cache TTL seconds</label><input type="number" name="cache_ttl_seconds" min="60" max="86400" value="<?= htmlspecialchars((string)($draftRow['cache_ttl_seconds'] ?? ($manifest['cache_ttl_seconds'] ?? 3600)), ENT_QUOTES, 'UTF-8') ?>" /></div>
      </div>

      <div class="row">
        <div><label>Latest version</label><input name="latest_version" required value="<?= htmlspecialchars((string)($draftRow['latest_version'] ?? ($manifest['latest_version'] ?? '1.0.0')), ENT_QUOTES, 'UTF-8') ?>" /></div>
        <div><label>Min supported version</label><input name="min_supported_version" required value="<?= htmlspecialchars((string)($draftRow['min_supported_version'] ?? ($manifest['min_supported_version'] ?? '1.0.0')), ENT_QUOTES, 'UTF-8') ?>" /></div>
      </div>

      <div class="row">
        <div>
          <label>Update mode</label>
          <?php $updateMode = (string)($draftRow['update_mode'] ?? ($manifest['update_mode'] ?? 'soft')); ?>
          <select name="update_mode">
            <option value="soft" <?= $updateMode === 'soft' ? 'selected' : '' ?>>soft</option>
            <option value="hard" <?= $updateMode === 'hard' ? 'selected' : '' ?>>hard</option>
          </select>
        </div>
        <div><label>Rollout percent</label><input type="number" name="rollout_percent" min="0" max="100" value="<?= htmlspecialchars((string)($draftRow['rollout_percent'] ?? ($manifest['rollout_percent'] ?? 100)), ENT_QUOTES, 'UTF-8') ?>" /></div>
      </div>

      <label>Download URL</label>
      <input name="download_url" required value="<?= htmlspecialchars((string)($draftRow['download_url'] ?? ($manifest['download_url'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" />

      <label style="margin-top:10px">Release notes URL</label>
      <input name="release_notes_url" value="<?= htmlspecialchars((string)($draftRow['release_notes_url'] ?? ($manifest['release_notes_url'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" />

      <label style="margin-top:10px">SHA256 (optional)</label>
      <input name="sha256" value="<?= htmlspecialchars((string)($draftRow['sha256'] ?? ($manifest['sha256'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" />

      <div class="actions">
        <button class="btn secondary" type="submit" name="action" value="save_draft">Save Draft</button>
        <button class="btn" type="submit" name="action" value="publish">Publish</button>
      </div>
    </form>
  </section>

  <section class="card">
    <h3 style="margin-top:0">Published Manifest</h3>
    <?php if (is_array($publishedRow)): ?>
      <p><strong>ETag:</strong> <code><?= htmlspecialchars((string)($publishedRow['etag'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code></p>
      <p><strong>Updated at:</strong> <?= htmlspecialchars((string)($publishedRow['updated_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
      <pre style="white-space:pre-wrap"><?= htmlspecialchars((string)json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?></pre>
    <?php else: ?>
      <p>No published manifest for this platform/channel.</p>
    <?php endif; ?>
  </section>
</main>
<?php
$body = (string)ob_get_clean();
$title = 'Ploofoodle Admin Releases';
require PLOO_BASE_PATH . '/src/Views/layout.php';
