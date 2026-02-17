<?php
$payload = is_array($payload ?? null) ? $payload : [];
$platform = (string)($platform ?? 'web');

$stepsAndroid = is_array($payload['steps_android'] ?? null) ? $payload['steps_android'] : [];
$stepsIos = is_array($payload['steps_ios'] ?? null) ? $payload['steps_ios'] : [];
$stepsGeneric = is_array($payload['steps_generic'] ?? null) ? $payload['steps_generic'] : [];
$troubleshooting = is_array($payload['troubleshooting'] ?? null) ? $payload['troubleshooting'] : [];

$primarySteps = $platform === 'ios' ? $stepsIos : ($platform === 'android' ? $stepsAndroid : $stepsGeneric);
if ($primarySteps === []) {
    $primarySteps = $stepsGeneric;
}

ob_start();
?>
<section class="section">
  <div class="container surface panel">
    <h1>Get Started</h1>
    <p class="muted">Install, authenticate, pay for premium, and verify credits quickly.</p>

    <div class="grid-2">
      <div class="card">
        <h2>Your steps (<?= htmlspecialchars($platform, ENT_QUOTES, 'UTF-8') ?>)</h2>
        <?php if ($primarySteps === []): ?>
          <p class="muted">No platform-specific setup steps published yet.</p>
        <?php else: ?>
          <div class="steps">
            <?php foreach ($primarySteps as $step): ?>
              <div class="step"><div><?= htmlspecialchars((string)$step, ENT_QUOTES, 'UTF-8') ?></div></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="card">
        <h2>Troubleshooting</h2>
        <?php if ($troubleshooting === []): ?>
          <p class="muted">No troubleshooting notes published yet.</p>
        <?php else: ?>
          <ul>
            <?php foreach ($troubleshooting as $line): ?>
              <li><?= htmlspecialchars((string)$line, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php
$content = (string)ob_get_clean();
$activePage = 'get_started';
require PLOO_BASE_PATH . '/src/Views/public/app/layout.php';
