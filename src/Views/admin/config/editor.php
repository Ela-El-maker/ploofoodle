<?php
$draftPayload = [];
if (is_array($draft) && isset($draft['payload'])) {
    $decoded = json_decode((string)$draft['payload'], true);
    $draftPayload = is_array($decoded) ? $decoded : [];
}
if ($draftPayload === [] && is_array($published) && isset($published['config'])) {
    $draftPayload = (array)$published['config'];
}

$featureFlags = (isset($draftPayload['feature_flags']) && is_array($draftPayload['feature_flags']))
    ? $draftPayload['feature_flags']
    : [];
$tuning = (isset($draftPayload['tuning']) && is_array($draftPayload['tuning']))
    ? $draftPayload['tuning']
    : [];
$welcomeSlides = (isset($draftPayload['welcome_slides']) && is_array($draftPayload['welcome_slides']))
    ? $draftPayload['welcome_slides']
    : [];
$supportLinks = (isset($draftPayload['support_links']) && is_array($draftPayload['support_links']))
    ? $draftPayload['support_links']
    : [];

$envLabel = (string)($draftPayload['env_label'] ?? 'prod');
$cacheTtl = (int)($draftPayload['cache_ttl_seconds'] ?? ($draft['cache_ttl_seconds'] ?? ($published['cache_ttl_seconds'] ?? 3600)));
$cacheTtl = max(60, min(86400, $cacheTtl));

$knownFlags = [
    'new_checkout' => 'New checkout flow',
    'show_profile_badge' => 'Show profile badge',
];

$knownTuning = [
    'payment_poll_max_seconds' => 'Payment poll max seconds',
    'retry_backoff_base_ms' => 'Retry backoff base (ms)',
];

$unknownTopLevel = array_values(array_diff(
    array_keys($draftPayload),
    ['feature_flags', 'tuning', 'welcome_slides', 'support_links', 'env_label', 'cache_ttl_seconds']
));

$payloadJson = json_encode($draftPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
$initialJson = json_encode($draftPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

ob_start();
?>
<section class="card">
  <div class="card-head">
    <h3>Bootstrap Config Editor</h3>
    <span class="badge draft">Draft / Published workflow</span>
  </div>

  <div class="warning">
    This editor only publishes <strong>public mobile config</strong>. Unknown top-level keys are blocked. Never store secrets here.
  </div>

  <?php if ($unknownTopLevel !== []): ?>
    <div class="warning" style="margin-top:10px">
      Unknown top-level keys detected in current draft:
      <code><?= htmlspecialchars(implode(', ', $unknownTopLevel), ENT_QUOTES, 'UTF-8') ?></code>
    </div>
  <?php endif; ?>

  <form id="bootstrap-config-form" method="post" action="<?= htmlspecialchars(ploo_route_url('/admin/config'), ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars((string)$csrfToken, ENT_QUOTES, 'UTF-8') ?>" />

    <div class="form-grid">
      <div class="field">
        <label>Platform</label>
        <select name="platform">
          <?php foreach ($allowedPlatforms as $item): ?>
            <option value="<?= htmlspecialchars((string)$item, ENT_QUOTES, 'UTF-8') ?>" <?= $item === $platform ? 'selected' : '' ?>><?= htmlspecialchars((string)$item, ENT_QUOTES, 'UTF-8') ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Channel</label>
        <select name="channel">
          <?php foreach ($allowedChannels as $item): ?>
            <option value="<?= htmlspecialchars((string)$item, ENT_QUOTES, 'UTF-8') ?>" <?= $item === $channel ? 'selected' : '' ?>><?= htmlspecialchars((string)$item, ENT_QUOTES, 'UTF-8') ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label>Schema version</label><input type="number" name="schema_version" min="1" value="<?= htmlspecialchars((string)($draft['schema_version'] ?? 1), ENT_QUOTES, 'UTF-8') ?>" /></div>
      <div class="field"><label>Cache TTL seconds</label><input id="cfg-cache-ttl" type="number" name="cache_ttl_seconds" min="60" max="86400" value="<?= htmlspecialchars((string)$cacheTtl, ENT_QUOTES, 'UTF-8') ?>" /></div>
      <div class="field"><label>Environment label</label><input id="cfg-env-label" type="text" value="<?= htmlspecialchars($envLabel, ENT_QUOTES, 'UTF-8') ?>" placeholder="prod" /></div>
    </div>

    <section class="config-section">
      <h4>Feature Flags</h4>
      <p class="muted">Toggle app features without shipping a new build.</p>
      <div class="toggle-grid">
        <?php foreach ($knownFlags as $key => $label): ?>
          <label class="toggle-item">
            <input type="checkbox" data-flag-key="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" <?= !empty($featureFlags[$key]) ? 'checked' : '' ?> />
            <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
          </label>
        <?php endforeach; ?>
      </div>

      <div class="kv-section">
        <div class="card-head" style="margin:10px 0 6px"><h5>Custom Flags</h5></div>
        <div id="custom-flags-list" class="kv-list"></div>
        <button type="button" class="btn secondary" onclick="addKvRow('custom-flags-list', 'flag_key', 'flag_value')">Add flag</button>
      </div>
    </section>

    <section class="config-section">
      <h4>Tuning</h4>
      <p class="muted">Runtime knobs (timeouts, polling, retry behavior).</p>
      <div class="form-grid">
        <?php foreach ($knownTuning as $key => $label): ?>
          <div class="field">
            <label><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></label>
            <input type="number" data-tuning-key="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars((string)($tuning[$key] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
          </div>
        <?php endforeach; ?>
      </div>

      <div class="kv-section">
        <div class="card-head" style="margin:10px 0 6px"><h5>Custom Tuning</h5></div>
        <div id="custom-tuning-list" class="kv-list"></div>
        <button type="button" class="btn secondary" onclick="addKvRow('custom-tuning-list', 'tuning_key', 'tuning_value')">Add tuning item</button>
      </div>
    </section>

    <section class="config-section">
      <div class="card-head"><h4>Welcome Slides</h4></div>
      <p class="muted">Slides shown during onboarding (title + body required).</p>
      <div id="welcome-slides-list" class="row-list"></div>
      <button type="button" class="btn secondary" onclick="addSlideRow()">Add slide</button>
    </section>

    <section class="config-section">
      <div class="card-head"><h4>Support Links</h4></div>
      <p class="muted">Help, terms, status and other user-facing links.</p>
      <div id="support-links-list" class="kv-list"></div>
      <button type="button" class="btn secondary" onclick="addKvRow('support-links-list', 'link_key', 'link_value')">Add link</button>
    </section>

    <details class="config-section">
      <summary><strong>Advanced</strong> raw JSON override (optional)</summary>
      <p class="muted">Leave empty to use structured form values. If provided, valid JSON here overrides form values.</p>
      <textarea id="cfg-raw-override" placeholder="{\"feature_flags\":{...}}"></textarea>
    </details>

    <input type="hidden" id="payload-json" name="payload_json" value="<?= htmlspecialchars((string)$payloadJson, ENT_QUOTES, 'UTF-8') ?>" />

    <details class="config-section" open>
      <summary><strong>Payload Preview</strong></summary>
      <pre id="payload-preview" class="code-block"></pre>
    </details>

    <p class="muted">Allowlist keys: feature_flags, tuning, welcome_slides, support_links, env_label, cache_ttl_seconds</p>

    <div class="btn-row">
      <button class="btn secondary" type="submit" formaction="<?= htmlspecialchars(ploo_route_url('/admin/config/save-draft'), ENT_QUOTES, 'UTF-8') ?>">Save Draft</button>
      <button class="btn primary" type="submit" formaction="<?= htmlspecialchars(ploo_route_url('/admin/config/publish'), ENT_QUOTES, 'UTF-8') ?>" onclick="return confirm('Publish this config to mobile clients?');">Publish</button>
      <button class="btn danger" type="submit" formaction="<?= htmlspecialchars(ploo_route_url('/admin/config/reset-draft'), ENT_QUOTES, 'UTF-8') ?>" onclick="return confirm('Reset draft to published values?');">Reset Draft</button>
      <button class="btn danger" type="submit" formaction="<?= htmlspecialchars(ploo_route_url('/admin/config/delete-draft'), ENT_QUOTES, 'UTF-8') ?>" onclick="return confirm('Delete current draft?');">Delete Draft</button>
    </div>
  </form>
</section>

<section class="card">
  <div class="card-head"><h3>Current Published Config</h3><span class="badge published">Published</span></div>
  <?php if (is_array($published)): ?>
    <p><strong>ETag:</strong> <code><?= htmlspecialchars((string)$published['etag'], ENT_QUOTES, 'UTF-8') ?></code></p>
    <p><strong>Updated At:</strong> <?= htmlspecialchars((string)$published['updated_at'], ENT_QUOTES, 'UTF-8') ?></p>
    <pre class="code-block"><?= htmlspecialchars((string)json_encode($published['config'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?></pre>
  <?php else: ?>
    <p class="muted">No published config found for this platform/channel.</p>
  <?php endif; ?>
</section>

<section class="card">
  <div class="card-head"><h3>Config History (Skeleton)</h3></div>
  <p class="muted">Phase 2: show last N published versions and rollback action.</p>
</section>

<script>
(function () {
  const initialPayload = <?= $initialJson ?: '{}' ?>;
  const knownFlagKeys = <?= json_encode(array_keys($knownFlags), JSON_UNESCAPED_SLASHES) ?>;
  const knownTuningKeys = <?= json_encode(array_keys($knownTuning), JSON_UNESCAPED_SLASHES) ?>;

  const form = document.getElementById('bootstrap-config-form');
  const previewEl = document.getElementById('payload-preview');
  const payloadInput = document.getElementById('payload-json');

  function parseScalar(value) {
    const raw = String(value ?? '').trim();
    if (raw === '') return '';
    if (raw === 'true') return true;
    if (raw === 'false') return false;
    if (/^-?\d+(\.\d+)?$/.test(raw)) return Number(raw);
    return raw;
  }

  function createRowHtml(fields, values = {}) {
    const cells = fields.map((f) => {
      const v = (values[f] ?? '').toString().replace(/"/g, '&quot;');
      return `<input data-col="${f}" placeholder="${f}" value="${v}" />`;
    }).join('');

    return `<div class="row-item">${cells}<button type="button" class="btn danger" onclick="this.parentElement.remove(); refreshPayloadPreview();">Remove</button></div>`;
  }

  window.addKvRow = function (containerId, keyField, valueField, rowValues = {}) {
    const container = document.getElementById(containerId);
    if (!container) return;
    container.insertAdjacentHTML('beforeend', createRowHtml([keyField, valueField], rowValues));
    refreshPayloadPreview();
  };

  window.addSlideRow = function (values = {}) {
    const container = document.getElementById('welcome-slides-list');
    if (!container) return;
    container.insertAdjacentHTML('beforeend', createRowHtml(['id', 'title', 'body', 'icon'], values));
    refreshPayloadPreview();
  };

  function readKv(containerId, keyField, valueField) {
    const out = {};
    const container = document.getElementById(containerId);
    if (!container) return out;
    container.querySelectorAll('.row-item').forEach((row) => {
      const key = (row.querySelector(`[data-col="${keyField}"]`)?.value || '').trim();
      const value = row.querySelector(`[data-col="${valueField}"]`)?.value;
      if (key !== '') out[key] = parseScalar(value);
    });
    return out;
  }

  function readSlides() {
    const list = [];
    const container = document.getElementById('welcome-slides-list');
    if (!container) return list;
    container.querySelectorAll('.row-item').forEach((row) => {
      const id = (row.querySelector('[data-col="id"]')?.value || '').trim();
      const title = (row.querySelector('[data-col="title"]')?.value || '').trim();
      const body = (row.querySelector('[data-col="body"]')?.value || '').trim();
      const icon = (row.querySelector('[data-col="icon"]')?.value || '').trim();
      if (title !== '' && body !== '') {
        const slide = { id: id || title.toLowerCase().replace(/\s+/g, '_'), title, body };
        if (icon !== '') slide.icon = icon;
        list.push(slide);
      }
    });
    return list;
  }

  function collectPayloadFromForm() {
    const payload = {
      feature_flags: {},
      tuning: {},
      welcome_slides: readSlides(),
      support_links: readKv('support-links-list', 'link_key', 'link_value'),
      env_label: (document.getElementById('cfg-env-label')?.value || '').trim(),
      cache_ttl_seconds: parseInt(document.getElementById('cfg-cache-ttl')?.value || '3600', 10) || 3600,
    };

    document.querySelectorAll('[data-flag-key]').forEach((input) => {
      payload.feature_flags[input.getAttribute('data-flag-key')] = !!input.checked;
    });

    Object.assign(payload.feature_flags, readKv('custom-flags-list', 'flag_key', 'flag_value'));

    document.querySelectorAll('[data-tuning-key]').forEach((input) => {
      const key = input.getAttribute('data-tuning-key');
      const parsed = parseScalar(input.value);
      if (parsed !== '') payload.tuning[key] = parsed;
    });

    Object.assign(payload.tuning, readKv('custom-tuning-list', 'tuning_key', 'tuning_value'));

    return payload;
  }

  function loadInitialPayload() {
    const payload = initialPayload || {};
    if (typeof payload.env_label === 'string') {
      const env = document.getElementById('cfg-env-label');
      if (env) env.value = payload.env_label;
    }
    if (payload.cache_ttl_seconds != null) {
      const ttl = document.getElementById('cfg-cache-ttl');
      if (ttl) ttl.value = String(payload.cache_ttl_seconds);
    }

    const flags = (payload.feature_flags && typeof payload.feature_flags === 'object') ? payload.feature_flags : {};
    Object.keys(flags).forEach((k) => {
      const known = document.querySelector(`[data-flag-key="${k}"]`);
      if (known) {
        known.checked = !!flags[k];
      } else {
        addKvRow('custom-flags-list', 'flag_key', 'flag_value', { flag_key: k, flag_value: String(flags[k]) });
      }
    });

    const tuning = (payload.tuning && typeof payload.tuning === 'object') ? payload.tuning : {};
    Object.keys(tuning).forEach((k) => {
      const known = document.querySelector(`[data-tuning-key="${k}"]`);
      if (known) {
        known.value = String(tuning[k]);
      } else {
        addKvRow('custom-tuning-list', 'tuning_key', 'tuning_value', { tuning_key: k, tuning_value: String(tuning[k]) });
      }
    });

    const slides = Array.isArray(payload.welcome_slides) ? payload.welcome_slides : [];
    if (slides.length === 0) {
      addSlideRow();
    } else {
      slides.forEach((s) => addSlideRow({ id: s.id || '', title: s.title || '', body: s.body || '', icon: s.icon || '' }));
    }

    const links = (payload.support_links && typeof payload.support_links === 'object') ? payload.support_links : {};
    Object.keys(links).forEach((k) => {
      addKvRow('support-links-list', 'link_key', 'link_value', { link_key: k, link_value: String(links[k]) });
    });
  }

  window.refreshPayloadPreview = function () {
    const payload = collectPayloadFromForm();
    previewEl.textContent = JSON.stringify(payload, null, 2);
  };

  form.addEventListener('input', refreshPayloadPreview);

  form.addEventListener('submit', function (event) {
    const override = (document.getElementById('cfg-raw-override')?.value || '').trim();
    if (override !== '') {
      try {
        JSON.parse(override);
        payloadInput.value = override;
      } catch (e) {
        event.preventDefault();
        alert('Advanced raw JSON override is invalid. Please fix it or clear it.');
        return;
      }
      return;
    }

    const payload = collectPayloadFromForm();
    payloadInput.value = JSON.stringify(payload, null, 2);
  });

  loadInitialPayload();
  refreshPayloadPreview();
})();
</script>
<?php
$content = (string)ob_get_clean();
$pageTitle = 'Bootstrap Config';
$activeNav = 'config';
require PLOO_BASE_PATH . '/src/Views/layout.php';
