# Ploofoodle

Minimal PHP admin system for managing mobile bootstrap config and update metadata.

## Scope
- Uses the same MySQL DB credentials as Plonkadoodle.
- Writes only to `admin_*` tables.
- Does not modify Plonkadoodle code/endpoints.
- Public read endpoints:
  - `GET {base_path}/index.php?_route=/mobile/bootstrap&platform=android&channel=stable`
  - `GET {base_path}/index.php?_route=/mobile/update&platform=android&channel=stable`
  - `GET {base_path}/index.php?_route=/health`

`{base_path}` is auto-detected from `SCRIPT_NAME` (works for local/live), or can be overridden via `PLOOFOODLE_BASE_PATH`.

## Admin routes
- `GET {base_path}/index.php?_route=/auth/login`
- `POST {base_path}/index.php?_route=/auth/login`
- `POST {base_path}/index.php?_route=/auth/logout`
- `GET {base_path}/index.php?_route=/admin`
- `GET {base_path}/index.php?_route=/admin/config`
- `POST {base_path}/index.php?_route=/admin/config`
- `GET {base_path}/index.php?_route=/admin/releases`
- `POST {base_path}/index.php?_route=/admin/releases`

## Public endpoint behavior
- Draft/publish model: only `status='published'` rows are served.
- Deterministic ETag for cache validation.
- Supports `If-None-Match` and returns `304 Not Modified`.
- `Cache-Control`: `public, max-age=3600, stale-while-revalidate=86400` (bounded by `cache_ttl_seconds`).

## Bootstrap payload allowlist gate
On publish, only these top-level keys are allowed:
- `feature_flags`
- `tuning`
- `welcome_slides`
- `support_links`
- `env_label`
- `cache_ttl_seconds`

Unknown keys are rejected before publish.

## Shared DB configuration
`src/bootstrap.php` loads DB env from:
- `PLOOFOODLE_ENV_FILE` (if set), otherwise
- `../Plonkadoodle/.env`

Used env vars:
- `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_CHARSET`
- `PLOOFOODLE_BASE_PATH` (optional explicit URL base path override)

## Admin authentication
- Session + CSRF.
- Primary auth source: `admin_users` table.
- Seed admin fallback is disabled by default and only allowed in local/dev/test when explicitly enabled:
  - `PLOOFOODLE_ALLOW_SEED_LOGIN=true`
- Seed credentials (used only if fallback is enabled):
  - `PLOOFOODLE_ADMIN_USER`
  - `PLOOFOODLE_ADMIN_PASS`

## SQL files
Apply manually (in order):
1. `sql/001_admin_users.sql`
2. `sql/002_admin_config_bundle.sql`
3. `sql/003_admin_update_manifest.sql`
4. `sql/004_admin_audit_log.sql`

## Notes
- `min_supported_version` here is metadata for app UI/update messaging.
- Plonkadoodle remains authoritative for hard upgrade gate (`426`).
