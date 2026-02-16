<?php
$row = $row ?? null;

ob_start();
?>
<section class="card">
  <div class="card-head">
    <h3>Audit Event Detail</h3>
    <a href="<?= htmlspecialchars(ploo_route_url('/admin/audit'), ENT_QUOTES, 'UTF-8') ?>">Back to audit list</a>
  </div>

  <?php if (!is_array($row)): ?>
    <p class="muted">Audit event not found.</p>
  <?php else: ?>
    <div class="grid-2">
      <div class="card">
        <p><strong>Actor:</strong> <?= htmlspecialchars((string)($row['actor_username'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Role:</strong> <?= htmlspecialchars((string)($row['actor_role'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Action:</strong> <?= htmlspecialchars((string)($row['action'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Entity:</strong> <?= htmlspecialchars((string)($row['entity_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Entity ID:</strong> <?= htmlspecialchars((string)($row['entity_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
      </div>
      <div class="card">
        <p><strong>Platform:</strong> <?= htmlspecialchars((string)($row['platform'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Channel:</strong> <?= htmlspecialchars((string)($row['channel'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>IP:</strong> <?= htmlspecialchars((string)($row['ip_address'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>User Agent:</strong> <?= htmlspecialchars((string)($row['user_agent'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Timestamp:</strong> <?= htmlspecialchars((string)($row['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
      </div>
    </div>

    <div class="grid-2">
      <section class="card">
        <div class="card-head"><h3>Before</h3></div>
        <pre class="code-block"><?= htmlspecialchars((string)json_encode(json_decode((string)($row['before_json'] ?? ''), true), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?></pre>
      </section>
      <section class="card">
        <div class="card-head"><h3>After</h3></div>
        <pre class="code-block"><?= htmlspecialchars((string)json_encode(json_decode((string)($row['after_json'] ?? ''), true), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?></pre>
      </section>
    </div>
  <?php endif; ?>
</section>
<?php
$content = (string)ob_get_clean();
$pageTitle = 'Audit Event';
$activeNav = 'audit';
require PLOO_BASE_PATH . '/src/Views/layout.php';
