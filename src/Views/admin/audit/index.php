<?php
$rows = $rows ?? [];
$filters = $filters ?? ['actor' => '', 'entity_type' => '', 'from' => '', 'to' => ''];

ob_start();
?>
<section class="card">
  <div class="card-head"><h3>Audit Log</h3></div>

  <form method="get" action="/Pandipoodle/Ploofoodle/public/index.php" class="filters">
    <input type="hidden" name="_route" value="/admin/audit" />
    <input type="text" name="actor" value="<?= htmlspecialchars((string)$filters['actor'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Actor" />
    <select name="entity_type">
      <option value="">All entities</option>
      <option value="config_bundle" <?= $filters['entity_type'] === 'config_bundle' ? 'selected' : '' ?>>config_bundle</option>
      <option value="update_manifest" <?= $filters['entity_type'] === 'update_manifest' ? 'selected' : '' ?>>update_manifest</option>
      <option value="auth" <?= $filters['entity_type'] === 'auth' ? 'selected' : '' ?>>auth</option>
    </select>
    <input type="date" name="from" value="<?= htmlspecialchars((string)$filters['from'], ENT_QUOTES, 'UTF-8') ?>" />
    <input type="date" name="to" value="<?= htmlspecialchars((string)$filters['to'], ENT_QUOTES, 'UTF-8') ?>" />
    <button class="btn secondary" type="submit">Filter</button>
  </form>

  <div class="table-wrap">
    <table>
      <thead><tr><th>Actor</th><th>Action</th><th>Entity</th><th>Entity ID</th><th>Timestamp</th><th>IP</th><th>View</th></tr></thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="7" class="muted">No audit rows found.</td></tr>
      <?php else: foreach ($rows as $row): ?>
        <tr>
          <td><?= htmlspecialchars((string)$row['actor_username'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)$row['action'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)$row['entity_type'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)($row['entity_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)$row['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)($row['ip_address'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><a href="/Pandipoodle/Ploofoodle/public/index.php?_route=/admin/audit/view&id=<?= (int)$row['id'] ?>">View</a></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</section>
<?php
$content = (string)ob_get_clean();
$pageTitle = 'Audit Log';
$activeNav = 'audit';
require PLOO_BASE_PATH . '/src/Views/layout.php';
