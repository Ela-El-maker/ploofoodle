<?php
$publishedConfig = $publishedConfig ?? null;
$publishedManifests = $publishedManifests ?? [];
$recentAudit = $recentAudit ?? [];

ob_start();
?>
<div class="grid-3">
  <section class="card">
    <div class="card-head"><h3>Published Bootstrap</h3><span class="badge published">Published</span></div>
    <div class="stat"><?= htmlspecialchars((string)($publishedConfig['schema_version'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></div>
    <div class="stat-sub">Schema version</div>
    <p class="muted">Last updated: <?= htmlspecialchars((string)($publishedConfig['updated_at'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></p>
  </section>

  <section class="card">
    <div class="card-head"><h3>Published Manifests</h3><span class="badge soft">Multi-platform</span></div>
    <div class="stat"><?= count($publishedManifests) ?></div>
    <div class="stat-sub">Platform/channel combinations</div>
    <p class="muted">android/ios by channel</p>
  </section>

  <section class="card">
    <div class="card-head"><h3>Hard Gate Reminder</h3><span class="badge hard">Important</span></div>
    <p class="muted">Hard update gate is enforced by Plonkadoodle (`426 UPGRADE_REQUIRED`).</p>
  </section>
</div>

<section class="card">
  <div class="card-head"><h3>Quick Actions</h3></div>
  <div class="btn-row">
    <a class="btn primary" href="<?= htmlspecialchars(ploo_route_url('/admin/config'), ENT_QUOTES, 'UTF-8') ?>">Edit Bootstrap Config</a>
    <a class="btn secondary" href="<?= htmlspecialchars(ploo_route_url('/admin/releases'), ENT_QUOTES, 'UTF-8') ?>">Manage Releases</a>
    <a class="btn secondary" href="<?= htmlspecialchars(ploo_route_url('/admin/front-landing'), ENT_QUOTES, 'UTF-8') ?>">Front Landing Page</a>
    <a class="btn secondary" href="<?= htmlspecialchars(ploo_route_url('/admin/audit'), ENT_QUOTES, 'UTF-8') ?>">View Audit Log</a>
  </div>
</section>

<section class="card">
  <div class="card-head"><h3>Recent Activity</h3></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Time</th><th>Actor</th><th>Action</th><th>Entity</th><th>IP</th></tr></thead>
      <tbody>
        <?php if (empty($recentAudit)): ?>
          <tr><td colspan="5" class="muted">No recent activity.</td></tr>
        <?php else: foreach ($recentAudit as $item): ?>
          <tr>
            <td><?= htmlspecialchars((string)$item['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)$item['actor_username'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)$item['action'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)$item['entity_type'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)$item['ip_address'], ENT_QUOTES, 'UTF-8') ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</section>
<?php
$content = (string)ob_get_clean();
$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
require PLOO_BASE_PATH . '/src/Views/layout.php';
