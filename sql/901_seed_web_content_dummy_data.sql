-- Seed for public app pages managed by Ploofoodle
INSERT INTO admin_web_content_bundle
(page_key, platform, channel, status, schema_version, payload, cache_ttl_seconds, etag, updated_by, published_at)
VALUES
(
  'app_landing',
  'all',
  'stable',
  'published',
  1,
  JSON_OBJECT(
    'hero_title', 'Get Up-Skill on your device',
    'hero_subtitle', 'Install the app, access premium content, and manage credits securely.',
    'primary_cta_label', 'Download App',
    'secondary_cta_label', 'View Release Notes',
    'store_labels', JSON_OBJECT('android', 'Get it on Google Play', 'ios', 'Download on App Store'),
    'support_links', JSON_OBJECT(
      'help', 'https://up-skill.felixeladi.co.ke/Ploofoodle/public/index.php?_route=/app/support',
      'terms', 'https://up-skill.felixeladi.co.ke/Ploofoodle/public/app/terms.html',
      'privacy', 'https://up-skill.felixeladi.co.ke/Ploofoodle/public/app/privacy.html',
      'contact', 'mailto:support@felixeladi.co.ke'
    ),
    'faq_items', JSON_ARRAY(
      JSON_OBJECT('q', 'How do I install the app?', 'a', 'Use Play Store or App Store links on this page.'),
      JSON_OBJECT('q', 'How do updates work?', 'a', 'The app checks the latest published release metadata and prompts you safely.'),
      JSON_OBJECT('q', 'Can I still use APK installs?', 'a', 'Only if your admin enables an APK distribution source.')
    ),
    'show_apk_fallback', true
  ),
  3600,
  SHA2('seed|app_landing|stable|published', 256),
  1,
  NOW()
),
(
  'app_get_started',
  'all',
  'stable',
  'published',
  1,
  JSON_OBJECT(
    'steps_android', JSON_ARRAY(
      'Open the Play Store link and install Up-Skill.',
      'Open the app and complete sign in.',
      'Choose a plan and complete payment to unlock premium.'
    ),
    'steps_ios', JSON_ARRAY(
      'Open the App Store link and install Up-Skill.',
      'Open the app and complete sign in.',
      'Choose a plan and complete payment to unlock premium.'
    ),
    'steps_generic', JSON_ARRAY(
      'Scan QR/session code from the website.',
      'Complete payment and wait for confirmation.',
      'Access premium content with updated balance.'
    ),
    'troubleshooting', JSON_ARRAY(
      'If payment stays pending, wait up to 2 minutes and retry status refresh.',
      'If update link fails, use the /app page and choose your store manually.'
    )
  ),
  3600,
  SHA2('seed|app_get_started|stable|published', 256),
  1,
  NOW()
),
(
  'app_support',
  'all',
  'stable',
  'published',
  1,
  JSON_OBJECT(
    'contact_email', 'support@felixeladi.co.ke',
    'contact_phone', '+254700000000',
    'status_url', 'https://up-skill.felixeladi.co.ke/Ploofoodle/public/index.php?_route=/app/releases',
    'terms_url', 'https://up-skill.felixeladi.co.ke/Ploofoodle/public/app/terms.html',
    'privacy_url', 'https://up-skill.felixeladi.co.ke/Ploofoodle/public/app/privacy.html',
    'faq_items', JSON_ARRAY(
      JSON_OBJECT('q', 'Can I use the app offline?', 'a', 'Cached profile data is shown, but payment and sync need internet.'),
      JSON_OBJECT('q', 'Where can I find release notes?', 'a', 'Open the releases page from the app landing hub.')
    )
  ),
  3600,
  SHA2('seed|app_support|stable|published', 256),
  1,
  NOW()
)
ON DUPLICATE KEY UPDATE
  schema_version = VALUES(schema_version),
  payload = VALUES(payload),
  cache_ttl_seconds = VALUES(cache_ttl_seconds),
  etag = VALUES(etag),
  updated_at = CURRENT_TIMESTAMP,
  published_at = VALUES(published_at);
