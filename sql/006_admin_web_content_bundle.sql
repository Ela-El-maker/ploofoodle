CREATE TABLE IF NOT EXISTS admin_web_content_bundle (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  page_key VARCHAR(64) NOT NULL,
  platform VARCHAR(20) NOT NULL DEFAULT 'all',
  channel VARCHAR(30) NOT NULL DEFAULT 'stable',
  status ENUM('draft','published') NOT NULL DEFAULT 'draft',
  schema_version INT UNSIGNED NOT NULL DEFAULT 1,
  payload JSON NOT NULL,
  cache_ttl_seconds INT UNSIGNED NOT NULL DEFAULT 3600,
  etag VARCHAR(64) NOT NULL,
  updated_by BIGINT UNSIGNED NULL,
  published_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_awcb_page_platform_channel_status (page_key, platform, channel, status),
  KEY idx_awcb_updated_at (updated_at),
  UNIQUE KEY uq_awcb_page_platform_channel_status (page_key, platform, channel, status),
  CONSTRAINT fk_awcb_updated_by FOREIGN KEY (updated_by) REFERENCES admin_users(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
