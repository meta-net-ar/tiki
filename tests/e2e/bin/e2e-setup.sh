#!/usr/bin/env bash
#
# Tiki E2E onboarding helper.
#
# Gets a new tester from a fresh clone to a green smoke run in one command:
#   1. ensures tests/e2e/.env exists (copies from .env.template if missing)
#   2. installs the Playwright dependency + Chromium browser if missing
#   3. runs the smoke suite (harness checks + live environment readiness)
#
# Environment prerequisites (test users, User Encryption feature) are seeded
# automatically by common/global-setup.ts on the first test run — no manual
# admin toggling required.
#
# Usage (from anywhere):
#   tests/e2e/bin/e2e-setup.sh              # setup + full smoke suite
#   tests/e2e/bin/e2e-setup.sh --no-run     # setup only, skip the smoke run
#
set -euo pipefail

E2E_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
REPO_ROOT="$(cd "$E2E_DIR/../.." && pwd)"
cd "$E2E_DIR"

RUN_SMOKE=1
[[ "${1:-}" == "--no-run" ]] && RUN_SMOKE=0

info()  { printf '\033[1;34m[e2e setup]\033[0m %s\n' "$1"; }
warn()  { printf '\033[1;33m[e2e setup]\033[0m %s\n' "$1"; }

# 1 — .env
if [[ -f .env ]]; then
    info ".env already present — leaving it untouched."
else
    cp .env.template .env
    warn "Created .env from .env.template."
    warn "Review tests/e2e/.env and adjust TIKI_BASE_URL / credentials for your setup before relying on it."
fi

# 2 — dependencies
# @playwright/test is a root devDependency, so install from the repo root: running
# `npm install` from a subdirectory triggers the root postinstall (patch-package)
# in a context where its bin is unavailable and fails on a fresh clone.
if ! node -e "require.resolve('@playwright/test')" >/dev/null 2>&1; then
    info "Installing npm dependencies…"
    (cd "$REPO_ROOT" && npm install)
else
    info "npm dependencies already installed."
fi

info "Ensuring Chromium is installed…"
npx playwright install chromium

# 3 — smoke run
if [[ "$RUN_SMOKE" -eq 1 ]]; then
    info "Running smoke suite…"
    npx playwright test suites/smoke/
    info "Smoke suite passed. You're ready to write and run tests."
else
    info "Setup complete (smoke run skipped). Run: npx playwright test suites/smoke/"
fi
