<?php
$rows = $rows ?? [];
$filters = $filters ?? ['platform' => '', 'channel' => '', 'status' => '', 'q' => ''];
$allowedPlatforms = $allowedPlatforms ?? ['android', 'ios', 'web'];
$allowedChannels = $allowedChannels ?? ['stable', 'beta', 'internal'];

ob_start();
?>
<section class="card">
  <div class="card-head">
    <h3>Releases</h3>
    <a class="btn primary" href="<?= htmlspecialchars(ploo_route_url('/admin/releases/create'), ENT_QUOTES, 'UTF-8') ?>">Create Release</a>
  </div>

  <form method="get" action="<?= htmlspecialchars(ploo_route_url('/admin/releases'), ENT_QUOTES, 'UTF-8') ?>" class="filters">
    <input type="hidden" name="_route" value="/admin/releases" />
    <select name="platform">
      <option value="">All platforms</option>
      <?php foreach ($allowedPlatforms as $p): ?>
        <option value="<?= htmlspecialchars((string)$p, ENT_QUOTES, 'UTF-8') ?>" <?= ($filters['platform'] ?? '') === $p ? 'selected' : '' ?>><?= htmlspecialchars((string)$p, ENT_QUOTES, 'UTF-8') ?></option>
      <?php endforeach; ?>
    </select>
    <select name="channel">
      <option value="">All channels</option>
      <?php foreach ($allowedChannels as $c): ?>
        <option value="<?= htmlspecialchars((string)$c, ENT_QUOTES, 'UTF-8') ?>" <?= ($filters['channel'] ?? '') === $c ? 'selected' : '' ?>><?= htmlspecialchars((string)$c, ENT_QUOTES, 'UTF-8') ?></option>
      <?php endforeach; ?>
    </select>
    <select name="status">
      <option value="">All statuses</option>
      <option value="draft" <?= ($filters['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
      <option value="published" <?= ($filters['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
    </select>
    <input type="text" name="q" value="<?= htmlspecialchars((string)($filters['q'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Search version..." />
    <button class="btn secondary" type="submit">Filter</button>
  </form>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Platform</th><th>Channel</th><th>Latest</th><th>Min</th><th>Mode</th><th>Source</th><th>Rollout</th><th>Status</th><th>Updated</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="10" class="muted">No manifests found.</td></tr>
      <?php else: foreach ($rows as $r): ?>
        <tr>
          <td><?= htmlspecialchars((string)$r['platform'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)$r['channel'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)$r['latest_version'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)$r['min_supported_version'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><span class="badge <?= htmlspecialchars((string)$r['update_mode'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$r['update_mode'], ENT_QUOTES, 'UTF-8') ?></span></td>
          <td><span class="badge info"><?= htmlspecialchars((string)($r['update_source'] ?? 'apk'), ENT_QUOTES, 'UTF-8') ?></span></td>
          <td><?= (int)$r['rollout_percent'] ?>%</td>
          <td><span class="badge <?= htmlspecialchars((string)$r['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$r['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
          <td><?= htmlspecialchars((string)$r['updated_at'], ENT_QUOTES, 'UTF-8') ?></td>
          <td>
            <a href="<?= htmlspecialchars(ploo_route_url('/admin/releases/view', ['id' => (int)$r['id']]), ENT_QUOTES, 'UTF-8') ?>">View</a>
            |
            <a href="<?= htmlspecialchars(ploo_route_url('/admin/releases/edit', ['id' => (int)$r['id']]), ENT_QUOTES, 'UTF-8') ?>">Edit</a>
            <form method="post" action="<?= htmlspecialchars(ploo_route_url('/admin/releases/' . ($r['status'] === 'published' ? 'unpublish' : 'publish')), ENT_QUOTES, 'UTF-8') ?>" style="display:inline" onsubmit="return confirm('Apply action for this manifest?');">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars((string)$csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
              <button class="btn secondary" type="submit"><?= $r['status'] === 'published' ? 'Unpublish' : 'Publish' ?></button>
            </form>
            <form method="post" action="<?= htmlspecialchars(ploo_route_url('/admin/releases/delete'), ENT_QUOTES, 'UTF-8') ?>" style="display:inline" onsubmit="return confirm('Delete this manifest row?');">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars((string)$csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
              <button class="btn danger" type="submit">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</section>
<?php
$content = (string)ob_get_clean();
$pageTitle = 'Releases';
$activeNav = 'releases';
require PLOO_BASE_PATH . '/src/Views/layout.php';
