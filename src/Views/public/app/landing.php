<?php
$payload = is_array($payload ?? null) ? $payload : [];
$platform = (string)($platform ?? 'web');
$channel = (string)($channel ?? 'stable');

$heroTitle = (string)($payload['hero_title'] ?? 'Get Up-Skill on your device');
$heroSubtitle = (string)($payload['hero_subtitle'] ?? 'Install the app and manage your premium access from one place.');
$primaryLabel = (string)($payload['primary_cta_label'] ?? 'Download App');
$secondaryLabel = (string)($payload['secondary_cta_label'] ?? 'View Release Notes');
$storeLabels = is_array($payload['store_labels'] ?? null) ? $payload['store_labels'] : [];
$supportLinks = is_array($payload['support_links'] ?? null) ? $payload['support_links'] : [];
$faqItems = is_array($payload['faq_items'] ?? null) ? $payload['faq_items'] : [];
$showApkFallback = (bool)($payload['show_apk_fallback'] ?? false);

$openUrl = ploo_route_url('/app/open', ['platform' => $platform, 'channel' => $channel]);
$releasesUrl = ploo_route_url('/app/releases', ['channel' => $channel]);
$playStoreUrl = null;
$appStoreUrl = null;
if (is_array($androidManifest)) {
    $androidSource = strtolower((string)($androidManifest['update_source'] ?? ''));
    if ($androidSource === 'play') {
        $playStoreUrl = (string)($androidManifest['distribution_url'] ?: $androidManifest['download_url']);
    }
}
if (is_array($iosManifest)) {
    $iosSource = strtolower((string)($iosManifest['update_source'] ?? ''));
    if ($iosSource === 'appstore') {
        $appStoreUrl = (string)($iosManifest['distribution_url'] ?: $iosManifest['download_url']);
    }
}

$androidVersion = (string)($androidManifest['latest_version'] ?? 'n/a');
$iosVersion = (string)($iosManifest['latest_version'] ?? 'n/a');
$androidUpdated = (string)($androidManifest['updated_at'] ?? '');
$iosUpdated = (string)($iosManifest['updated_at'] ?? '');

ob_start();
?>
<section class="hero">
  <div class="container hero-grid">
    <div>
      <h1><?= htmlspecialchars($heroTitle, ENT_QUOTES, 'UTF-8') ?></h1>
      <p class="muted"><?= htmlspecialchars($heroSubtitle, ENT_QUOTES, 'UTF-8') ?></p>
      <div class="badges">
        <span class="badge">Channel: <?= htmlspecialchars($channel, ENT_QUOTES, 'UTF-8') ?></span>
        <span class="badge">Detected: <?= htmlspecialchars($platform, ENT_QUOTES, 'UTF-8') ?></span>
      </div>
      <div class="cta-row">
        <a class="btn btn-primary store-btn is-primary" href="<?= htmlspecialchars($openUrl, ENT_QUOTES, 'UTF-8') ?>" data-platform="<?= htmlspecialchars($platform, ENT_QUOTES, 'UTF-8') ?>">
          <?= htmlspecialchars($primaryLabel, ENT_QUOTES, 'UTF-8') ?>
        </a>
        <a class="btn btn-secondary" href="<?= htmlspecialchars($releasesUrl, ENT_QUOTES, 'UTF-8') ?>">
          <?= htmlspecialchars($secondaryLabel, ENT_QUOTES, 'UTF-8') ?>
        </a>
      </div>
      <div class="cta-row cta-row-compact">
        <?php if (!empty($playStoreUrl)): ?>
          <a class="btn btn-secondary" href="<?= htmlspecialchars((string)$playStoreUrl, ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars((string)($storeLabels['android'] ?? 'Google Play'), ENT_QUOTES, 'UTF-8') ?>
          </a>
        <?php endif; ?>
        <?php if (!empty($appStoreUrl)): ?>
          <a class="btn btn-secondary" href="<?= htmlspecialchars((string)$appStoreUrl, ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars((string)($storeLabels['ios'] ?? 'App Store'), ENT_QUOTES, 'UTF-8') ?>
          </a>
        <?php endif; ?>
      </div>
      <p class="muted" style="margin-top:10px">
        Android: <?= htmlspecialchars((string)($storeLabels['android'] ?? 'Google Play'), ENT_QUOTES, 'UTF-8') ?> ·
        iOS: <?= htmlspecialchars((string)($storeLabels['ios'] ?? 'App Store'), ENT_QUOTES, 'UTF-8') ?>
      </p>
      <?php if ($showApkFallback): ?>
        <p class="muted">If store install is unavailable, you can still use release links and support guidance below.</p>
      <?php endif; ?>
    </div>

    <div class="surface panel">
      <h2>Latest versions</h2>
      <div class="grid-2">
        <div class="card">
          <h3>Android</h3>
          <p><strong><?= htmlspecialchars($androidVersion, ENT_QUOTES, 'UTF-8') ?></strong></p>
          <p class="muted">Updated: <?= htmlspecialchars($androidUpdated ?: 'n/a', ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div class="card">
          <h3>iOS</h3>
          <p><strong><?= htmlspecialchars($iosVersion, ENT_QUOTES, 'UTF-8') ?></strong></p>
          <p class="muted">Updated: <?= htmlspecialchars($iosUpdated ?: 'n/a', ENT_QUOTES, 'UTF-8') ?></p>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container grid-3">
    <article class="card">
      <h3>Install</h3>
      <p class="muted">Use the smart download link to open the right store for your device.</p>
      <a href="<?= htmlspecialchars($openUrl, ENT_QUOTES, 'UTF-8') ?>">Open smart download</a>
    </article>
    <article class="card">
      <h3>What’s new</h3>
      <p class="muted">Track the latest releases and compatibility updates.</p>
      <a href="<?= htmlspecialchars($releasesUrl, ENT_QUOTES, 'UTF-8') ?>">Browse releases</a>
    </article>
    <article class="card">
      <h3>Need help?</h3>
      <p class="muted">Guides and support resources are available in one place.</p>
      <a href="<?= htmlspecialchars(ploo_route_url('/app/support'), ENT_QUOTES, 'UTF-8') ?>">Open support</a>
    </article>
  </div>
</section>

<?php if ($faqItems !== []): ?>
<section class="section">
  <div class="container surface panel">
    <h2>FAQ</h2>
    <ul class="list-clean">
      <?php foreach ($faqItems as $item): ?>
        <li class="card" style="margin-bottom:10px">
          <strong><?= htmlspecialchars((string)($item['q'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
          <p class="muted"><?= htmlspecialchars((string)($item['a'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
<?php endif; ?>

<section class="section">
  <div class="container muted">
    <?php
      $helpUrl = (string)($supportLinks['help'] ?? ploo_route_url('/app/support'));
      $termsUrl = (string)($supportLinks['terms'] ?? '');
      $privacyUrl = (string)($supportLinks['privacy'] ?? '');
      $contactUrl = (string)($supportLinks['contact'] ?? '');
    ?>
    <p>
      Help: <a href="<?= htmlspecialchars($helpUrl, ENT_QUOTES, 'UTF-8') ?>">Support</a> ·
      Terms:
      <?php if ($termsUrl !== ''): ?>
        <a href="<?= htmlspecialchars($termsUrl, ENT_QUOTES, 'UTF-8') ?>">Terms</a>
      <?php else: ?>
        <span class="muted">Not configured</span>
      <?php endif; ?>
      · Privacy:
      <?php if ($privacyUrl !== ''): ?>
        <a href="<?= htmlspecialchars($privacyUrl, ENT_QUOTES, 'UTF-8') ?>">Privacy</a>
      <?php else: ?>
        <span class="muted">Not configured</span>
      <?php endif; ?>
      · Contact:
      <?php if ($contactUrl !== ''): ?>
        <a href="<?= htmlspecialchars($contactUrl, ENT_QUOTES, 'UTF-8') ?>">Contact</a>
      <?php else: ?>
        <span class="muted">Not configured</span>
      <?php endif; ?>
    </p>
  </div>
</section>
<?php
$content = (string)ob_get_clean();
$activePage = 'landing';
require PLOO_BASE_PATH . '/src/Views/public/app/layout.php';
