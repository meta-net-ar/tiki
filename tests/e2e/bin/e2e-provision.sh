#!/usr/bin/env bash
#
# Provision a dedicated, disposable Tiki instance for the e2e suite.
#
#   1. writes db/local.php for the test database (refuses to overwrite an
#      existing one unless --force is passed — protects dev instances)
#   2. clean-installs the Tiki schema (console.php database:install)
#   3. sets the admin password and test-friendly preferences
#
# Config comes from tests/e2e/.env or the environment (env wins):
#   TIKI_E2E_DB_HOST (default 127.0.0.1)
#   TIKI_E2E_DB_NAME (default tiki_e2e)
#   TIKI_E2E_DB_USER (default tiki_e2e)
#   TIKI_E2E_DB_PASS (default tiki_e2e)
#   TIKI_ADMIN_PASS  (default admin1234)
#
# After provisioning, run the suite in self-contained mode — Playwright will
# start PHP's built-in web server automatically (see playwright.config.ts):
#   TIKI_E2E_WEBSERVER=1 npx playwright test
#
set -euo pipefail

E2E_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TIKI_ROOT="$(cd "$E2E_DIR/../.." && pwd)"

info() { printf '\033[1;34m[e2e provision]\033[0m %s\n' "$1"; }
fail() { printf '\033[1;31m[e2e provision]\033[0m %s\n' "$1" >&2; exit 1; }

# Load tests/e2e/.env when present (already-set environment variables win).
if [[ -f "$E2E_DIR/.env" ]]; then
    while IFS='=' read -r key value; do
        [[ "$key" =~ ^[A-Z0-9_]+$ ]] || continue
        [[ -z "${!key:-}" ]] && export "$key=$value"
    done < <(grep -E '^[A-Z0-9_]+=' "$E2E_DIR/.env")
fi

DB_HOST="${TIKI_E2E_DB_HOST:-127.0.0.1}"
DB_NAME="${TIKI_E2E_DB_NAME:-tiki_e2e}"
DB_USER="${TIKI_E2E_DB_USER:-tiki_e2e}"
DB_PASS="${TIKI_E2E_DB_PASS:-tiki_e2e}"
ADMIN_PASS="${TIKI_ADMIN_PASS:-admin1234}"

cd "$TIKI_ROOT"

[[ -f vendor_bundled/vendor/autoload.php ]] \
    || fail "vendor_bundled is not installed — run: composer install -d vendor_bundled"

if [[ -f db/local.php && "${1:-}" != "--force" ]]; then
    fail "db/local.php already exists — this script is for disposable instances \
(fresh checkout, worktree, CI). Re-run with --force to overwrite it."
fi

info "Creating database ${DB_NAME} on ${DB_HOST} (best-effort)…"
php -r '
    try {
        (new PDO("mysql:host={$argv[1]}", $argv[2], $argv[3]))
            ->exec("CREATE DATABASE IF NOT EXISTS `{$argv[4]}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    } catch (Exception $e) {
        fwrite(STDERR, "note: could not create database ({$e->getMessage()}) — assuming it already exists\n");
    }' "$DB_HOST" "$DB_USER" "$DB_PASS" "$DB_NAME"

info "Writing db/local.php…"
php console.php database:configure "$DB_USER" "$DB_PASS" "$DB_NAME" --host="$DB_HOST"

info "Installing Tiki schema (this takes a minute)…"
php console.php database:install --force -n

# Fixtures run through fixtures/bootstrap.php, which takes the script path relative to
# fixtures/ plus an optional base64-encoded JSON payload. PHP does the encoding so the
# password needs no shell or JSON quoting of its own.
info "Setting admin password…"
php "$E2E_DIR/fixtures/bootstrap.php" provision_admin.php \
    "$(php -r 'echo base64_encode(json_encode(["password" => $argv[1]]));' "$ADMIN_PASS")"

info "Applying test-friendly defaults…"
php "$E2E_DIR/fixtures/bootstrap.php" provision_defaults.php

info "Done. Run the suite with: TIKI_E2E_WEBSERVER=1 npx playwright test"
