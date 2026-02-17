ALTER TABLE admin_update_manifest
  ADD COLUMN IF NOT EXISTS update_source ENUM('apk','play','appstore','web') NOT NULL DEFAULT 'apk' AFTER update_mode,
  ADD COLUMN IF NOT EXISTS distribution_url VARCHAR(500) NULL AFTER download_url;
