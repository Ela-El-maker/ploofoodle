<?php
$draftPayload = [];
if (is_array($draft) && isset($draft['payload'])) {
    $decoded = json_decode((string)$draft['payload'], true);
    $draftPayload = is_array($decoded) ? $decoded : [];
}
if ($draftPayload === [] && is_array($published) && isset($published['payload'])) {
    $decodedPublished = json_decode((string)$published['payload'], true);
    $draftPayload = is_array($decodedPublished) ? $decodedPublished : [];
}

$pageKey = (string)($pageKey ?? 'app_landing');
$cacheTtl = (int)($draft['cache_ttl_seconds'] ?? ($published['cache_ttl_seconds'] ?? 3600));
$cacheTtl = max(60, min(86400, $cacheTtl));
$schemaVersion = (int)($draft['schema_version'] ?? ($published['schema_version'] ?? 1));
$schemaVersion = max(1, $schemaVersion);
$payloadJson = json_encode($draftPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
$initialJson = json_encode($draftPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

ob_start();
?>
<section class="card">
  <div class="card-head">
    <h3><?= htmlspecialchars((string)$title, ENT_QUOTES, 'UTF-8') ?></h3>
    <span class="badge draft">Draft / Published workflow</span>
  </div>
  <div class="front-tabs">
    <a class="front-tab <?= $pageKey === 'app_landing' ? 'active' : '' ?>" href="<?= htmlspecialchars(ploo_route_url('/admin/front-landing', ['platform' => (string)$platform, 'channel' => (string)$channel]), ENT_QUOTES, 'UTF-8') ?>">Landing</a>
    <a class="front-tab <?= $pageKey === 'app_get_started' ? 'active' : '' ?>" href="<?= htmlspecialchars(ploo_route_url('/admin/front-landing/get-started', ['platform' => (string)$platform, 'channel' => (string)$channel]), ENT_QUOTES, 'UTF-8') ?>">Get Started</a>
    <a class="front-tab <?= $pageKey === 'app_support' ? 'active' : '' ?>" href="<?= htmlspecialchars(ploo_route_url('/admin/front-landing/support', ['platform' => (string)$platform, 'channel' => (string)$channel]), ENT_QUOTES, 'UTF-8') ?>">Support</a>
  </div>
  <p class="muted"><?= htmlspecialchars((string)$description, ENT_QUOTES, 'UTF-8') ?></p>

  <div class="warning" style="margin-bottom:12px">
    Only public website content belongs here. Unknown keys are blocked on publish. Never store secrets in payload.
  </div>
  <p class="muted">
    Public preview:
    <?php if ($pageKey === 'app_landing'): ?>
      <a href="<?= htmlspecialchars(ploo_route_url('/app', ['platform' => (string)$platform, 'channel' => (string)$channel]), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Open /app</a>
    <?php elseif ($pageKey === 'app_get_started'): ?>
      <a href="<?= htmlspecialchars(ploo_route_url('/app/get-started', ['platform' => (string)$platform, 'channel' => (string)$channel]), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Open /app/get-started</a>
    <?php else: ?>
      <a href="<?= htmlspecialchars(ploo_route_url('/app/support', ['platform' => (string)$platform, 'channel' => (string)$channel]), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Open /app/support</a>
    <?php endif; ?>
  </p>

  <form id="front-content-form" method="post" action="<?= htmlspecialchars(ploo_route_url('/admin/front-landing/save'), ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars((string)$csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
    <input type="hidden" name="page_key" value="<?= htmlspecialchars($pageKey, ENT_QUOTES, 'UTF-8') ?>" />
    <input type="hidden" id="front-payload-json" name="payload_json" value="<?= htmlspecialchars((string)$payloadJson, ENT_QUOTES, 'UTF-8') ?>" />

    <div class="form-grid">
      <div class="field">
        <label>Platform</label>
        <select name="platform">
          <?php foreach (($allowedPlatforms ?? ['all']) as $item): ?>
            <option value="<?= htmlspecialchars((string)$item, ENT_QUOTES, 'UTF-8') ?>" <?= (string)$item === (string)$platform ? 'selected' : '' ?>><?= htmlspecialchars((string)$item, ENT_QUOTES, 'UTF-8') ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Channel</label>
        <select name="channel">
          <?php foreach (($allowedChannels ?? ['stable']) as $item): ?>
            <option value="<?= htmlspecialchars((string)$item, ENT_QUOTES, 'UTF-8') ?>" <?= (string)$item === (string)$channel ? 'selected' : '' ?>><?= htmlspecialchars((string)$item, ENT_QUOTES, 'UTF-8') ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label>Schema version</label><input type="number" min="1" name="schema_version" value="<?= htmlspecialchars((string)$schemaVersion, ENT_QUOTES, 'UTF-8') ?>" /></div>
      <div class="field"><label>Cache TTL seconds</label><input id="front-cache-ttl" type="number" min="60" max="86400" name="cache_ttl_seconds" value="<?= htmlspecialchars((string)$cacheTtl, ENT_QUOTES, 'UTF-8') ?>" /></div>
    </div>

    <?php if ($pageKey === 'app_landing'): ?>
      <section class="config-section">
        <h4>Hero</h4>
        <div class="form-grid">
          <div class="field"><label>Hero title</label><input id="hero-title" type="text" value="<?= htmlspecialchars((string)($draftPayload['hero_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" /></div>
          <div class="field"><label>Hero subtitle</label><input id="hero-subtitle" type="text" value="<?= htmlspecialchars((string)($draftPayload['hero_subtitle'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" /></div>
          <div class="field"><label>Primary CTA label</label><input id="primary-cta" type="text" value="<?= htmlspecialchars((string)($draftPayload['primary_cta_label'] ?? 'Download App'), ENT_QUOTES, 'UTF-8') ?>" /></div>
          <div class="field"><label>Secondary CTA label</label><input id="secondary-cta" type="text" value="<?= htmlspecialchars((string)($draftPayload['secondary_cta_label'] ?? 'View Releases'), ENT_QUOTES, 'UTF-8') ?>" /></div>
        </div>
        <label class="toggle-item" style="margin-top:10px">
          <input id="show-apk-fallback" type="checkbox" <?= !empty($draftPayload['show_apk_fallback']) ? 'checked' : '' ?> />
          <span>Show APK fallback note on public page</span>
        </label>
      </section>

      <section class="config-section">
        <h4>Store Labels</h4>
        <div class="form-grid">
          <div class="field"><label>Android label</label><input id="store-android" type="text" value="<?= htmlspecialchars((string)($draftPayload['store_labels']['android'] ?? 'Get it on Google Play'), ENT_QUOTES, 'UTF-8') ?>" /></div>
          <div class="field"><label>iOS label</label><input id="store-ios" type="text" value="<?= htmlspecialchars((string)($draftPayload['store_labels']['ios'] ?? 'Download on App Store'), ENT_QUOTES, 'UTF-8') ?>" /></div>
        </div>
      </section>

      <section class="config-section">
        <h4>Support Links</h4>
        <div class="form-grid">
          <div class="field"><label>Help URL</label><input id="help-url" type="url" value="<?= htmlspecialchars((string)($draftPayload['support_links']['help'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" /></div>
          <div class="field"><label>Terms URL</label><input id="terms-url" type="url" value="<?= htmlspecialchars((string)($draftPayload['support_links']['terms'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" /></div>
          <div class="field"><label>Privacy URL</label><input id="privacy-url" type="url" value="<?= htmlspecialchars((string)($draftPayload['support_links']['privacy'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" /></div>
          <div class="field"><label>Contact URL / mailto</label><input id="contact-url" type="text" value="<?= htmlspecialchars((string)($draftPayload['support_links']['contact'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" /></div>
        </div>
      </section>

      <section class="config-section">
        <h4>FAQ Items</h4>
        <div id="faq-list" class="row-list"></div>
        <button type="button" class="btn secondary" onclick="addFaqRow()">Add FAQ</button>
      </section>
    <?php elseif ($pageKey === 'app_get_started'): ?>
      <section class="config-section">
        <h4>Installation and First Use Steps</h4>
        <div class="form-grid">
          <div class="field"><label>Android steps (one per line)</label><textarea id="steps-android"></textarea></div>
          <div class="field"><label>iOS steps (one per line)</label><textarea id="steps-ios"></textarea></div>
          <div class="field"><label>Generic steps (one per line)</label><textarea id="steps-generic"></textarea></div>
          <div class="field"><label>Troubleshooting (one per line)</label><textarea id="troubleshooting"></textarea></div>
        </div>
      </section>
    <?php else: ?>
      <section class="config-section">
        <h4>Support Contacts</h4>
        <div class="form-grid">
          <div class="field"><label>Contact email</label><input id="contact-email" type="email" value="<?= htmlspecialchars((string)($draftPayload['contact_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" /></div>
          <div class="field"><label>Contact phone</label><input id="contact-phone" type="text" value="<?= htmlspecialchars((string)($draftPayload['contact_phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" /></div>
          <div class="field"><label>Status URL</label><input id="status-url" type="url" value="<?= htmlspecialchars((string)($draftPayload['status_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" /></div>
          <div class="field"><label>Terms URL</label><input id="terms-url" type="url" value="<?= htmlspecialchars((string)($draftPayload['terms_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" /></div>
          <div class="field"><label>Privacy URL</label><input id="privacy-url" type="url" value="<?= htmlspecialchars((string)($draftPayload['privacy_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" /></div>
        </div>
      </section>
      <section class="config-section">
        <h4>FAQ Items</h4>
        <div id="faq-list" class="row-list"></div>
        <button type="button" class="btn secondary" onclick="addFaqRow()">Add FAQ</button>
      </section>
    <?php endif; ?>

    <details class="config-section" open>
      <summary><strong>Payload Preview</strong></summary>
      <pre id="front-payload-preview" class="code-block"></pre>
    </details>

    <p class="muted">Allowlist keys for this page: <?= htmlspecialchars(implode(', ', (array)$allowedKeys), ENT_QUOTES, 'UTF-8') ?></p>

    <div class="btn-row">
      <button class="btn secondary" type="submit" name="action" value="save_draft">Save Draft</button>
      <button class="btn primary" type="submit" name="action" value="publish" onclick="return confirm('Publish this content to public pages?');">Publish</button>
      <button class="btn danger" type="submit" name="action" value="reset_draft" onclick="return confirm('Reset draft from published content?');">Reset Draft</button>
      <button class="btn danger" type="submit" name="action" value="delete_draft" onclick="return confirm('Delete current draft?');">Delete Draft</button>
    </div>
  </form>
</section>

<section class="card">
  <div class="card-head"><h3>Current Published Content</h3><span class="badge published">Published</span></div>
  <?php if (is_array($published)): ?>
    <p><strong>ETag:</strong> <code><?= htmlspecialchars((string)$published['etag'], ENT_QUOTES, 'UTF-8') ?></code></p>
    <p><strong>Updated At:</strong> <?= htmlspecialchars((string)$published['updated_at'], ENT_QUOTES, 'UTF-8') ?></p>
    <pre class="code-block"><?php
      $publishedPayload = json_decode((string)$published['payload'], true);
      echo htmlspecialchars((string)json_encode($publishedPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
    ?></pre>
  <?php else: ?>
    <p class="muted">No published content found for this page/platform/channel.</p>
  <?php endif; ?>
</section>

<script>
(function () {
  const pageKey = <?= json_encode($pageKey, JSON_UNESCAPED_SLASHES) ?>;
  const initialPayload = <?= $initialJson ?: '{}' ?>;
  const previewEl = document.getElementById('front-payload-preview');
  const payloadInput = document.getElementById('front-payload-json');
  const form = document.getElementById('front-content-form');

  function splitLines(value) {
    return String(value || '').split(/\n+/).map((x) => x.trim()).filter(Boolean);
  }

  function addFaqRow(values = {}) {
    const container = document.getElementById('faq-list');
    if (!container) return;
    const q = String(values.q || '').replace(/"/g, '&quot;');
    const a = String(values.a || '').replace(/"/g, '&quot;');
    container.insertAdjacentHTML('beforeend',
      `<div class="row-item"><input data-col="q" placeholder="Question" value="${q}" /><input data-col="a" placeholder="Answer" value="${a}" /><button type="button" class="btn danger" onclick="this.parentElement.remove();refreshFrontPayload();">Remove</button></div>`
    );
  }
  window.addFaqRow = addFaqRow;

  function readFaq() {
    const list = [];
    const container = document.getElementById('faq-list');
    if (!container) return list;
    container.querySelectorAll('.row-item').forEach((row) => {
      const q = (row.querySelector('[data-col="q"]')?.value || '').trim();
      const a = (row.querySelector('[data-col="a"]')?.value || '').trim();
      if (q && a) list.push({ q, a });
    });
    return list;
  }

  function collectPayload() {
    if (pageKey === 'app_landing') {
      return {
        hero_title: (document.getElementById('hero-title')?.value || '').trim(),
        hero_subtitle: (document.getElementById('hero-subtitle')?.value || '').trim(),
        primary_cta_label: (document.getElementById('primary-cta')?.value || '').trim(),
        secondary_cta_label: (document.getElementById('secondary-cta')?.value || '').trim(),
        store_labels: {
          android: (document.getElementById('store-android')?.value || '').trim(),
          ios: (document.getElementById('store-ios')?.value || '').trim(),
        },
        support_links: {
          help: (document.getElementById('help-url')?.value || '').trim(),
          terms: (document.getElementById('terms-url')?.value || '').trim(),
          privacy: (document.getElementById('privacy-url')?.value || '').trim(),
          contact: (document.getElementById('contact-url')?.value || '').trim(),
        },
        faq_items: readFaq(),
        show_apk_fallback: !!document.getElementById('show-apk-fallback')?.checked,
      };
    }

    if (pageKey === 'app_get_started') {
      return {
        steps_android: splitLines(document.getElementById('steps-android')?.value),
        steps_ios: splitLines(document.getElementById('steps-ios')?.value),
        steps_generic: splitLines(document.getElementById('steps-generic')?.value),
        troubleshooting: splitLines(document.getElementById('troubleshooting')?.value),
      };
    }

    return {
      contact_email: (document.getElementById('contact-email')?.value || '').trim(),
      contact_phone: (document.getElementById('contact-phone')?.value || '').trim(),
      status_url: (document.getElementById('status-url')?.value || '').trim(),
      terms_url: (document.getElementById('terms-url')?.value || '').trim(),
      privacy_url: (document.getElementById('privacy-url')?.value || '').trim(),
      faq_items: readFaq(),
    };
  }

  function loadInitial() {
    if (pageKey === 'app_landing') {
      const p = initialPayload || {};
      const set = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.value = value || '';
      };
      set('hero-title', p.hero_title);
      set('hero-subtitle', p.hero_subtitle);
      set('primary-cta', p.primary_cta_label || 'Download App');
      set('secondary-cta', p.secondary_cta_label || 'View Releases');
      set('store-android', p.store_labels?.android || 'Get it on Google Play');
      set('store-ios', p.store_labels?.ios || 'Download on App Store');
      set('help-url', p.support_links?.help);
      set('terms-url', p.support_links?.terms);
      set('privacy-url', p.support_links?.privacy);
      set('contact-url', p.support_links?.contact);
      const apk = document.getElementById('show-apk-fallback');
      if (apk) apk.checked = !!p.show_apk_fallback;
      (Array.isArray(p.faq_items) ? p.faq_items : []).forEach(addFaqRow);
    } else if (pageKey === 'app_get_started') {
      const p = initialPayload || {};
      const setArea = (id, list) => {
        const el = document.getElementById(id);
        if (el) el.value = Array.isArray(list) ? list.join('\n') : '';
      };
      setArea('steps-android', p.steps_android);
      setArea('steps-ios', p.steps_ios);
      setArea('steps-generic', p.steps_generic);
      setArea('troubleshooting', p.troubleshooting);
    } else {
      const p = initialPayload || {};
      const set = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.value = value || '';
      };
      set('contact-email', p.contact_email);
      set('contact-phone', p.contact_phone);
      set('status-url', p.status_url);
      set('terms-url', p.terms_url);
      set('privacy-url', p.privacy_url);
      (Array.isArray(p.faq_items) ? p.faq_items : []).forEach(addFaqRow);
    }

    const faq = document.getElementById('faq-list');
    if (faq && faq.children.length === 0 && pageKey !== 'app_get_started') {
      addFaqRow();
    }
  }

  window.refreshFrontPayload = function () {
    const payload = collectPayload();
    previewEl.textContent = JSON.stringify(payload, null, 2);
  };

  form?.addEventListener('input', refreshFrontPayload);
  form?.addEventListener('submit', function () {
    payloadInput.value = JSON.stringify(collectPayload(), null, 2);
  });

  loadInitial();
  refreshFrontPayload();
})();
</script>
<?php
$content = (string)ob_get_clean();
$pageTitle = 'Front Landing Page';
$activeNav = 'front_landing';
require PLOO_BASE_PATH . '/src/Views/layout.php';
