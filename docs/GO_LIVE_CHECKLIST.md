# Ploofoodle — Go Live Checklist

## 1) Environment hardening
- `APP_ENV=prod`
- `PLOOFOODLE_ALLOW_SEED_LOGIN=false`
- Strong `DB_*` credentials, rotated and stored securely
- `DARAJA_STUB=0` for real payment environment
- `DARAJA_ENV=live` in production payment mode

## 2) Database readiness
- Apply SQL files in order:
  1. `sql/001_admin_users.sql`
  2. `sql/002_admin_config_bundle.sql`
  3. `sql/003_admin_update_manifest.sql`
  4. `sql/004_admin_audit_log.sql`
  5. `sql/005_admin_update_manifest_source.sql`
  6. `sql/006_admin_web_content_bundle.sql`
- Seed baseline content:
  - `sql/900_seed_dummy_data.sql`
  - `sql/901_seed_web_content_dummy_data.sql`

## 3) Public endpoints smoke
- `/app`
- `/app/releases`
- `/app/get-started`
- `/app/support`
- `/mobile/bootstrap?platform=android&channel=stable`
- `/mobile/update?platform=android&channel=stable`

## 4) Admin smoke
- Login with DB admin user (not seed login)
- Edit Front Landing draft and verify public pages do not change
- Publish and verify public pages update
- Create/edit release and verify `/app/releases` and `View notes`

## 5) Run automated readiness check
```bash
./Ploofoodle/scripts/go_live_readiness_check.sh --mode prod Plonkadoodle/.env
```

For local non-prod checks:
```bash
./Ploofoodle/scripts/go_live_readiness_check.sh --mode local Plonkadoodle/.env
```

## 6) Observability and rollback
- Keep DB backup/snapshot before release
- Confirm `admin_audit_log` is receiving writes
- Prepare rollback by keeping previous published release metadata row values
