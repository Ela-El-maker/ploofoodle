#!/usr/bin/env bash
set -euo pipefail

MODE="prod"
ENV_FILE="${1:-Plonkadoodle/.env}"
BASE_URL="${PLOO_BASE_URL:-http://localhost/Pandipoodle/Ploofoodle/public/index.php}"

if [[ "${1:-}" == "--mode" ]]; then
  MODE="${2:-prod}"
  ENV_FILE="${3:-Plonkadoodle/.env}"
fi

if [[ ! -f "$ENV_FILE" ]]; then
  echo "[FAIL] env file not found: $ENV_FILE"
  exit 1
fi

get_env() {
  local key="$1"
  local line
  line=$(grep -E "^${key}=" "$ENV_FILE" | tail -n1 || true)
  line="${line#*=}"
  line="${line%\"}"
  line="${line#\"}"
  printf '%s' "$line"
}

APP_ENV="$(get_env APP_ENV)"
DB_HOST="$(get_env DB_HOST)"
DB_PORT="$(get_env DB_PORT)"
DB_NAME="$(get_env DB_NAME)"
DB_USER="$(get_env DB_USER)"
DB_PASS="$(get_env DB_PASS)"
DARAJA_STUB="$(get_env DARAJA_STUB)"
DARAJA_ENV="$(get_env DARAJA_ENV)"
SEED_LOGIN="$(get_env PLOOFOODLE_ALLOW_SEED_LOGIN)"

DB_PORT="${DB_PORT:-3306}"

fails=0
warns=0

pass() { echo "[PASS] $1"; }
fail() { echo "[FAIL] $1"; fails=$((fails+1)); }
warn() { echo "[WARN] $1"; warns=$((warns+1)); }

check_http() {
  local path="$1"
  local expected="${2:-200}"
  local code
  code=$(curl -s -o /tmp/ploo_check_body.txt -w '%{http_code}' "${BASE_URL}?_route=${path}" || true)
  if [[ "$code" == "$expected" ]]; then
    pass "HTTP ${path} -> ${code}"
  else
    fail "HTTP ${path} -> ${code} (expected ${expected})"
  fi
}

sql_scalar() {
  local query="$1"
  MYSQL_PWD="$DB_PASS" mysql -u "$DB_USER" -h "$DB_HOST" -P "$DB_PORT" -N -B -e "$query"
}

echo "== Ploofoodle Live Readiness Check =="
echo "mode=$MODE env_file=$ENV_FILE base_url=$BASE_URL"

[[ -n "$DB_HOST" ]] && pass "DB_HOST is set" || fail "DB_HOST missing"
[[ -n "$DB_NAME" ]] && pass "DB_NAME is set" || fail "DB_NAME missing"
[[ -n "$DB_USER" ]] && pass "DB_USER is set" || fail "DB_USER missing"
[[ -n "$DB_PASS" ]] && pass "DB_PASS is set" || fail "DB_PASS missing"

if [[ "$MODE" == "prod" ]]; then
  [[ "$APP_ENV" == "prod" ]] && pass "APP_ENV=prod" || fail "APP_ENV should be prod (found: ${APP_ENV:-empty})"
  [[ "${SEED_LOGIN:-false}" == "false" || -z "${SEED_LOGIN:-}" ]] && pass "PLOOFOODLE_ALLOW_SEED_LOGIN disabled" || fail "PLOOFOODLE_ALLOW_SEED_LOGIN must be false in prod"
  [[ "$DARAJA_STUB" == "0" ]] && pass "DARAJA_STUB=0" || warn "DARAJA_STUB is not 0 (found: ${DARAJA_STUB:-empty})"
  [[ "$DARAJA_ENV" == "live" ]] && pass "DARAJA_ENV=live" || warn "DARAJA_ENV is not live (found: ${DARAJA_ENV:-empty})"
else
  pass "Non-prod mode checks enabled"
fi

check_http '/health' 200
check_http '/app' 200
check_http '/app/releases' 200
check_http '/app/get-started' 200
check_http '/app/support' 200
check_http '/mobile/bootstrap&platform=android&channel=stable' 200
check_http '/mobile/update&platform=android&channel=stable' 200

# DB schema + data sanity
schema_ok=$(sql_scalar "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_NAME}' AND table_name IN ('admin_users','admin_config_bundle','admin_update_manifest','admin_audit_log','admin_web_content_bundle');" 2>/dev/null || echo "0")
if [[ "$schema_ok" -ge 5 ]]; then
  pass "Admin tables exist"
else
  fail "Missing one or more admin_* tables"
fi

pub_manifest=$(sql_scalar "SELECT COUNT(*) FROM \`${DB_NAME}\`.admin_update_manifest WHERE status='published';" 2>/dev/null || echo "0")
[[ "$pub_manifest" -ge 1 ]] && pass "Published update manifests: $pub_manifest" || fail "No published update manifest rows"

pub_web=$(sql_scalar "SELECT COUNT(*) FROM \`${DB_NAME}\`.admin_web_content_bundle WHERE status='published';" 2>/dev/null || echo "0")
[[ "$pub_web" -ge 3 ]] && pass "Published web content bundles: $pub_web" || fail "Expected at least 3 published web bundles"

bad_links=$(sql_scalar "SELECT COUNT(*) FROM (SELECT COALESCE(download_url,'') AS u FROM \`${DB_NAME}\`.admin_update_manifest UNION ALL SELECT COALESCE(distribution_url,'') FROM \`${DB_NAME}\`.admin_update_manifest UNION ALL SELECT COALESCE(release_notes_url,'') FROM \`${DB_NAME}\`.admin_update_manifest) t WHERE u LIKE '%example.com%' OR u LIKE '%id000%' OR u LIKE '%com.example%';" 2>/dev/null || echo "999")
if [[ "$bad_links" -eq 0 ]]; then
  pass "No dead placeholder URLs in manifest data"
else
  fail "Found placeholder URLs in manifest data: $bad_links"
fi

echo "--"
if [[ "$fails" -eq 0 ]]; then
  echo "READY: PASS (warnings=$warns)"
  exit 0
fi

echo "READY: FAIL (fails=$fails, warnings=$warns)"
exit 1
