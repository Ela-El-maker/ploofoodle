<?php
$draftPayload = [];
if (is_array($draft) && isset($draft['payload'])) {
    $decoded = json_decode((string)$draft['payload'], true);
    $draftPayload = is_array($decoded) ? $decoded : [];
}
if ($draftPayload === [] && is_array($published) && isset($published['config'])) {
    $draftPayload = (array)$published['config'];
}
$payloadJson = json_encode($draftPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

ob_start();
?>
<header>
  <strong>Ploofoodle - Bootstrap Config</strong>
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
      <h2 style="margin:0">Bootstrap Draft/Publish</h2>
      <a href="/Pandipoodle/Ploofoodle/public/index.php?_route=/admin/releases">Go to Releases</a>
    </div>
    <p><strong>User:</strong> <?= htmlspecialchars((string)$user['username'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string)$user['role'], ENT_QUOTES, 'UTF-8') ?>)</p>

    <form method="post" action="/Pandipoodle/Ploofoodle/public/index.php?_route=/admin/config">
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
        <div>
          <label>Schema version</label>
          <input type="number" name="schema_version" min="1" value="<?= htmlspecialchars((string)($draft['schema_version'] ?? 1), ENT_QUOTES, 'UTF-8') ?>" />
        </div>
        <div>
          <label>Cache TTL seconds</label>
          <input type="number" name="cache_ttl_seconds" min="60" max="86400" value="<?= htmlspecialchars((string)($draft['cache_ttl_seconds'] ?? $published['cache_ttl_seconds'] ?? 3600), ENT_QUOTES, 'UTF-8') ?>" />
        </div>
      </div>

      <label>payload_json (allowlist keys only)</label>
      <textarea name="payload_json" required><?= htmlspecialchars((string)$payloadJson, ENT_QUOTES, 'UTF-8') ?></textarea>
      <p><small>Allowed top-level keys: <code>feature_flags</code>, <code>tuning</code>, <code>welcome_slides</code>, <code>support_links</code>, <code>env_label</code>, <code>cache_ttl_seconds</code>.</small></p>

      <div class="actions">
        <button class="btn secondary" type="submit" name="action" value="save_draft">Save Draft</button>
        <button class="btn" type="submit" name="action" value="publish">Publish</button>
      </div>
    </form>
  </section>

  <section class="card">
    <h3 style="margin-top:0">Published Snapshot</h3>
    <?php if (is_array($published)): ?>
      <p><strong>ETag:</strong> <code><?= htmlspecialchars((string)$published['etag'], ENT_QUOTES, 'UTF-8') ?></code></p>
      <p><strong>Updated at:</strong> <?= htmlspecialchars((string)$published['updated_at'], ENT_QUOTES, 'UTF-8') ?></p>
      <pre style="white-space:pre-wrap"><?= htmlspecialchars((string)json_encode($published['config'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?></pre>
    <?php else: ?>
      <p>No published config for this platform/channel.</p>
    <?php endif; ?>
  </section>
</main>
<?php
$body = (string)ob_get_clean();
$title = 'Ploofoodle Admin Config';
require PLOO_BASE_PATH . '/src/Views/layout.php';
