# Ploofoodle Go-Live Checklist

## 1) Deploy paths
- Upload `Ploofoodle/` to web root.
- Verify endpoint exists:
  - `https://<domain>/Ploofoodle/public/index.php?_route=/health`

## 2) Shared DB wiring
- Ensure Ploofoodle reads the same `.env` as Plonkadoodle:
  - Set `PLOOFOODLE_ENV_FILE=/absolute/path/to/Plonkadoodle/.env` (recommended), or
  - keep `Plonkadoodle/.env` one directory up from `Ploofoodle/`.

## 3) Apply SQL (once)
Run in order:
1. `sql/001_admin_users.sql`
2. `sql/002_admin_config_bundle.sql`
3. `sql/003_admin_update_manifest.sql`
4. `sql/004_admin_audit_log.sql`

## 4) Admin auth hardening
- Ensure fallback login is disabled in production:
  - `PLOOFOODLE_ALLOW_SEED_LOGIN=false`
- Use DB-backed `admin_users` credentials only.
- Confirm admin password hashes are strong (`password_hash`).

## 5) Publish required mobile records
For each target platform/channel (start with `android/stable`):
- Bootstrap Config: save draft, then publish.
- Release Manifest: save draft, then publish.

## 6) Public endpoint verification
- Bootstrap:
  - `GET /Ploofoodle/public/index.php?_route=/mobile/bootstrap&platform=android&channel=stable`
- Update:
  - `GET /Ploofoodle/public/index.php?_route=/mobile/update&platform=android&channel=stable`

Expected:
- HTTP `200`
- JSON `success:true`
- `ETag` header present
- `Cache-Control` header present

## 7) App runtime defines
Use release build defines:
```bash
--dart-define APP_BASE_URL=https://<domain>
--dart-define UPDATE_PROVIDER_MODE=ploofoodle
--dart-define PLOOFOODLE_UPDATE_PATH=/Ploofoodle/public/index.php
--dart-define APP_UPDATE_CHANNEL=stable
```

## 8) Smoke test
- Fresh install app.
- Confirm onboarding slides come from bootstrap config.
- Confirm settings environment label matches bootstrap `env_label`.
- Confirm update check reads published manifest.

## 9) Rollback readiness
- Keep previous release manifest row ready.
- Keep previous APK/release URL available.
- If needed, publish previous manifest immediately.
