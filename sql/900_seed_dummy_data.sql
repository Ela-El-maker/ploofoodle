USE `up-skill`;

-- 1) admin_users
INSERT INTO admin_users (username, password_hash, role, is_active, last_login_at)
VALUES
  ('admin', '$2y$10$uQ6j1jV0cCl9oFf6mA0kQO5xG6s9C0m7s2v8D/3q4Qn6RzQe2DgHi', 'admin', 1, NOW()),
  ('viewer_demo', '$2y$10$uQ6j1jV0cCl9oFf6mA0kQO5xG6s9C0m7s2v8D/3q4Qn6RzQe2DgHi', 'viewer', 1, NULL)
ON DUPLICATE KEY UPDATE
  role = VALUES(role),
  is_active = VALUES(is_active);

SET @admin_id := (SELECT id FROM admin_users WHERE username = 'admin' LIMIT 1);
SET @viewer_id := (SELECT id FROM admin_users WHERE username = 'viewer_demo' LIMIT 1);

-- 2) admin_config_bundle (draft + published for multiple platform/channel)
INSERT INTO admin_config_bundle
(platform, channel, bundle_key, status, schema_version, payload, cache_ttl_seconds, etag, updated_by, published_at)
VALUES
(
  'android','stable','mobile_bootstrap','draft',1,
  JSON_OBJECT(
    'feature_flags', JSON_OBJECT('new_checkout', true, 'show_profile_badge', true),
    'tuning', JSON_OBJECT('payment_poll_max_seconds', 90, 'retry_backoff_base_ms', 1200),
    'welcome_slides', JSON_ARRAY(
      JSON_OBJECT('id','pay','title','Pay for premium access','body','Securely purchase premium access in-app.'),
      JSON_OBJECT('id','balance','title','Track credits','body','See credits and updates in real time.'),
      JSON_OBJECT('id','profile','title','Manage account','body','View account status and support links quickly.')
    ),
    'support_links', JSON_OBJECT('help','https://example.com/help','terms','https://example.com/terms','status','https://example.com/status'),
    'env_label', 'prod',
    'cache_ttl_seconds', 3600
  ),
  3600,
  SHA2(CONCAT('android','stable','draft','v1','dummy-config'),256),
  @admin_id,
  NULL
),
(
  'android','stable','mobile_bootstrap','published',1,
  JSON_OBJECT(
    'feature_flags', JSON_OBJECT('new_checkout', true, 'show_profile_badge', true),
    'tuning', JSON_OBJECT('payment_poll_max_seconds', 90, 'retry_backoff_base_ms', 1200),
    'welcome_slides', JSON_ARRAY(
      JSON_OBJECT('id','pay','title','Pay for premium access','body','Securely purchase premium access in-app.'),
      JSON_OBJECT('id','balance','title','Track credits','body','See credits and updates in real time.'),
      JSON_OBJECT('id','profile','title','Manage account','body','View account status and support links quickly.')
    ),
    'support_links', JSON_OBJECT('help','https://example.com/help','terms','https://example.com/terms','status','https://example.com/status'),
    'env_label', 'prod',
    'cache_ttl_seconds', 3600
  ),
  3600,
  SHA2(CONCAT('android','stable','published','v1','dummy-config'),256),
  @admin_id,
  NOW()
),
(
  'ios','stable','mobile_bootstrap','published',1,
  JSON_OBJECT(
    'feature_flags', JSON_OBJECT('new_checkout', false, 'show_profile_badge', true),
    'tuning', JSON_OBJECT('payment_poll_max_seconds', 90, 'retry_backoff_base_ms', 1200),
    'welcome_slides', JSON_ARRAY(
      JSON_OBJECT('id','pay','title','Upgrade your access','body','Purchase premium access from supported payment methods.')
    ),
    'support_links', JSON_OBJECT('help','https://example.com/help-ios','terms','https://example.com/terms'),
    'env_label', 'prod',
    'cache_ttl_seconds', 3600
  ),
  3600,
  SHA2(CONCAT('ios','stable','published','v1','dummy-config'),256),
  @admin_id,
  NOW()
),
(
  'android','beta','mobile_bootstrap','published',1,
  JSON_OBJECT(
    'feature_flags', JSON_OBJECT('new_checkout', true, 'beta_banner', true),
    'tuning', JSON_OBJECT('payment_poll_max_seconds', 120),
    'welcome_slides', JSON_ARRAY(
      JSON_OBJECT('id','beta','title','Beta channel','body','You are seeing beta configuration.')
    ),
    'support_links', JSON_OBJECT('help','https://example.com/help-beta'),
    'env_label', 'beta',
    'cache_ttl_seconds', 900
  ),
  900,
  SHA2(CONCAT('android','beta','published','v1','dummy-config'),256),
  @admin_id,
  NOW()
)
ON DUPLICATE KEY UPDATE
  schema_version = VALUES(schema_version),
  payload = VALUES(payload),
  cache_ttl_seconds = VALUES(cache_ttl_seconds),
  etag = VALUES(etag),
  updated_by = VALUES(updated_by),
  published_at = VALUES(published_at);

-- 3) admin_update_manifest (draft + published)
INSERT INTO admin_update_manifest
(platform, channel, status, schema_version, latest_version, min_supported_version, update_mode, download_url, release_notes_url, sha256, rollout_percent, cache_ttl_seconds, etag, updated_by, published_at)
VALUES
(
  'android','stable','draft',1,
  '1.2.4','1.1.0','soft',
  'https://github.com/example/pimpodoodle/releases/download/v1.2.4/pimpodoodle-android.apk',
  'https://github.com/example/pimpodoodle/releases/tag/v1.2.4',
  NULL,
  100,
  3600,
  SHA2(CONCAT('android','stable','draft','1.2.4','1.1.0'),256),
  @admin_id,
  NULL
),
(
  'android','stable','published',1,
  '1.2.3','1.1.0','soft',
  'https://github.com/example/pimpodoodle/releases/download/v1.2.3/pimpodoodle-android.apk',
  'https://github.com/example/pimpodoodle/releases/tag/v1.2.3',
  NULL,
  80,
  3600,
  SHA2(CONCAT('android','stable','published','1.2.3','1.1.0'),256),
  @admin_id,
  NOW()
),
(
  'ios','stable','published',1,
  '1.2.1','1.1.0','soft',
  'https://apps.apple.com/app/id000000000',
  'https://example.com/release-notes/ios-1.2.1',
  NULL,
  100,
  3600,
  SHA2(CONCAT('ios','stable','published','1.2.1','1.1.0'),256),
  @admin_id,
  NOW()
),
(
  'android','beta','published',1,
  '1.3.0-beta.2','1.1.0','soft',
  'https://github.com/example/pimpodoodle/releases/download/v1.3.0-beta.2/pimpodoodle-android-beta.apk',
  'https://github.com/example/pimpodoodle/releases/tag/v1.3.0-beta.2',
  NULL,
  25,
  900,
  SHA2(CONCAT('android','beta','published','1.3.0-beta.2','1.1.0'),256),
  @admin_id,
  NOW()
)
ON DUPLICATE KEY UPDATE
  schema_version = VALUES(schema_version),
  latest_version = VALUES(latest_version),
  min_supported_version = VALUES(min_supported_version),
  update_mode = VALUES(update_mode),
  download_url = VALUES(download_url),
  release_notes_url = VALUES(release_notes_url),
  sha256 = VALUES(sha256),
  rollout_percent = VALUES(rollout_percent),
  cache_ttl_seconds = VALUES(cache_ttl_seconds),
  etag = VALUES(etag),
  updated_by = VALUES(updated_by),
  published_at = VALUES(published_at);

-- 4) admin_audit_log (append sample events)
INSERT INTO admin_audit_log
(actor_user_id, actor_username, actor_role, action, entity_type, entity_id, platform, channel, before_json, after_json, ip_address, user_agent)
VALUES
(@admin_id, 'admin', 'admin', 'seed', 'config_bundle', NULL, 'android', 'stable', NULL, JSON_OBJECT('status','published','bundle_key','mobile_bootstrap'), '127.0.0.1', 'seed-script/1.0'),
(@admin_id, 'admin', 'admin', 'publish', 'update_manifest', NULL, 'android', 'stable', NULL, JSON_OBJECT('latest_version','1.2.3','min_supported_version','1.1.0'), '127.0.0.1', 'seed-script/1.0'),
(@viewer_id, 'viewer_demo', 'viewer', 'view', 'config_bundle', NULL, 'android', 'stable', NULL, JSON_OBJECT('note','viewer activity sample'), '127.0.0.1', 'seed-script/1.0');
