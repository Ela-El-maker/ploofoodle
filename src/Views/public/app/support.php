<?php
$payload = is_array($payload ?? null) ? $payload : [];
$faqItems = is_array($payload['faq_items'] ?? null) ? $payload['faq_items'] : [];

ob_start();
?>
<section class="section">
  <div class="container surface panel">
    <h1>Support</h1>
    <p class="muted">Official support channels and quick answers.</p>

    <div class="grid-2">
      <div class="card">
        <h2>Contact</h2>
        <?php $email = (string)($payload['contact_email'] ?? ''); ?>
        <?php $phone = (string)($payload['contact_phone'] ?? ''); ?>
        <p><strong>Email:</strong>
          <?php if ($email !== ''): ?>
            <a href="mailto:<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></a>
          <?php else: ?>
            <span class="muted">Not published</span>
          <?php endif; ?>
        </p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($phone !== '' ? $phone : 'Not published', ENT_QUOTES, 'UTF-8') ?></p>
      </div>
      <div class="card">
        <h2>Links</h2>
        <ul>
          <?php $statusUrl = (string)($payload['status_url'] ?? ''); ?>
          <?php $termsUrl = (string)($payload['terms_url'] ?? ''); ?>
          <?php $privacyUrl = (string)($payload['privacy_url'] ?? ''); ?>
          <li>Status:
            <?php if ($statusUrl !== ''): ?><a href="<?= htmlspecialchars($statusUrl, ENT_QUOTES, 'UTF-8') ?>">Open status page</a><?php else: ?><span class="muted">Not configured</span><?php endif; ?>
          </li>
          <li>Terms:
            <?php if ($termsUrl !== ''): ?><a href="<?= htmlspecialchars($termsUrl, ENT_QUOTES, 'UTF-8') ?>">Open terms</a><?php else: ?><span class="muted">Not configured</span><?php endif; ?>
          </li>
          <li>Privacy:
            <?php if ($privacyUrl !== ''): ?><a href="<?= htmlspecialchars($privacyUrl, ENT_QUOTES, 'UTF-8') ?>">Open privacy</a><?php else: ?><span class="muted">Not configured</span><?php endif; ?>
          </li>
        </ul>
      </div>
    </div>

    <section class="section" style="padding-bottom:0">
      <h2>FAQ</h2>
      <?php if ($faqItems === []): ?>
        <p class="muted">No support FAQ published yet.</p>
      <?php else: ?>
        <ul class="list-clean">
          <?php foreach ($faqItems as $item): ?>
            <li class="card" style="margin-bottom:10px">
              <strong><?= htmlspecialchars((string)($item['q'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
              <p class="muted"><?= htmlspecialchars((string)($item['a'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </div>
</section>
<?php
$content = (string)ob_get_clean();
$activePage = 'support';
require PLOO_BASE_PATH . '/src/Views/public/app/layout.php';
