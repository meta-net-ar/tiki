# Tiki — End-to-End Tests

Playwright test suite for Tiki Wiki CMS functional testing.

## Prerequisites

- A running Tiki instance (local or CI environment) — self-signed HTTPS certificates are accepted.
  **Or none at all**: see [Self-Contained Mode](#self-contained-mode-dedicated-test-db--built-in-web-server),
  which provisions a disposable instance with a dedicated test database and serves it with
  PHP's built-in web server (this is what CI uses).
- Node.js >= 20
- `@playwright/test` ^1.58.2 (installed via `npm install`)
- The Tiki database seeded with test users (automatic — see Setup below)

## Environment Variables

Copy `.env.template` to `.env` and adjust the values for your local setup:

```bash
cp .env.template .env
```

| Variable           | Default                      | Purpose                                                     |
|--------------------|------------------------------|-------------------------------------------------------------|
| `TIKI_BASE_URL`    | `http://localhost`           | Base URL of your Tiki instance                              |
| `TIKI_FIXTURE_CMD` | auto-detected                | PHP invocation prefix for fixture scripts (see below)       |
| `TIKI_ADMIN_USER`  | `admin`                      | Admin username for tests requiring admin access             |
| `TIKI_ADMIN_PASS`  | `admin1234`                  | Admin password                                              |
| `TIKI_TEST_USER`   | `Test1`                      | Registered (non-admin) username for permission tests        |
| `TIKI_TEST_PASS`   | `test1pass`                  | Registered user password                                    |
| `TIKI_E2E_WEBSERVER` | unset                      | `1` = self-contained mode: serve this checkout via `php -S` |
| `TIKI_E2E_PORT`    | `8686`                       | Port for the built-in web server (self-contained mode)      |
| `TIKI_E2E_DB_*`    | `tiki_e2e` / `127.0.0.1`     | Dedicated test DB host/name/user/pass (self-contained mode) |

Override `TIKI_FIXTURE_CMD` to match your environment — for example `php` when running plain PHP
from the Tiki root, or `docker exec my-container php /var/www/html` for a Docker setup.

Some test suites (`tc04`, `tc06`, `tc10`, `tc11`) run PHP fixture scripts to seed the database.

## First-Time Setup

The fastest path — one command that creates `.env` from the template (if missing), installs
the Playwright dependency and Chromium, then runs the smoke suite to confirm the environment
works:

```bash
tests/e2e/bin/e2e-setup.sh          # setup + smoke run
tests/e2e/bin/e2e-setup.sh --no-run # setup only
```

Or do it by hand:

```bash
npm install                    # from the Tiki root — @playwright/test is a root devDependency
npx playwright install chromium
```

## Automatic Environment Seeding

Before the whole session, `common/global-setup.ts` (wired via `globalSetup` in the config) runs
two fixtures once so no manual admin steps are needed:

1. `create_test_users.php` — ensures Test1 / Test2 exist with known passwords
2. `enable_encryption.php` — persists the **User Encryption** security feature (`feature_user_encryption='y'`) in the DB

This is best-effort: if `TIKI_FIXTURE_CMD` is misconfigured (or you run the harness-only
`smoke/tc00` spec with no live Tiki), the setup prints a clear warning and continues rather
than aborting the run. Suites that genuinely need the seeded state then fail with their own
descriptive errors — run `npx playwright test suites/smoke/tc01-environment.spec.ts` to
pinpoint what's missing.

## Test Users

Most test suites require two registered users and an admin with known passwords:

| User  | Password   | Role                        |
|-------|------------|-----------------------------|
| Test1 | test1pass  | Registered (primary tester) |
| Test2 | test2pass  | Registered (secondary)      |
| admin | admin1234  | Administrator               |

Test1/Test2 are created automatically by the global setup above. To create or reset them
manually, run from the Tiki root:

```bash
php tests/e2e/fixtures/bootstrap.php create_test_users.php
```

Every PHP fixture is run this way: `bootstrap.php` first, then the script path relative to
`tests/e2e/fixtures/`. Adapt the PHP invocation to your environment (`ddev exec php`,
`docker exec`, plain `php`, etc.).

## Running the Tests

Playwright and its config live in the repo-root `package.json`, so the simplest way to run
the suite is via the root npm scripts, from the **Tiki root**:

```bash
npm run test:e2e         # all suites
npm run test:e2e:smoke   # smoke suites only (framework + environment checks)
npm run test:e2e:report  # open the last HTML report
```

The config auto-loads `.env` from `tests/e2e/` if it exists (variables already set in the
environment are never overridden, so CI pipelines that inject vars directly are unaffected)
and runs both reporters simultaneously — `list` (terminal) and `html` (written to
`playwright-report/`).

For filtered runs, call Playwright directly from `tests/e2e/` (where `playwright.config.ts` lives):

```bash
cd tests/e2e

npx playwright test                                          # all suites
npx playwright test shared-secrets/                          # a single domain suite
npx playwright test wiki-pages/
npx playwright test shared-secrets/tc02-create-key.spec.ts   # a single spec file

# Point at a non-default instance
TIKI_BASE_URL=https://tiki.example.com npx playwright test
```

### Interactive and debug modes

```bash
# Open Playwright UI — browse, filter, and re-run tests interactively
TIKI_BASE_URL=https://tiki.example.com npx playwright test --ui

# Step through a failing test with Playwright Inspector
TIKI_BASE_URL=https://tiki.example.com npx playwright test --debug
```

### Viewing the HTML report

```bash
cd tests/e2e
npx playwright show-report
```

Opens an interactive report at `http://localhost:9323` with pass/fail per test, traces, screenshots, and error details.

### Overriding the reporter

To use only one reporter (overrides the config defaults):

```bash
npx playwright test --reporter=list   # terminal only
npx playwright test --reporter=html   # HTML only
```

## Directory Structure

```
tests/e2e/
├── bin/
│   ├── e2e-setup.sh           Onboarding helper: .env + deps + smoke run
│   └── e2e-provision.sh       Self-contained mode: dedicated test DB + clean install
├── common/                    Shared utilities for all domain suites
│   ├── auth.ts                login, logout, ADMIN, TEST_USER constants
│   ├── fixtures.ts            FIXTURE_CMD, useSuiteFixtures (convention-based setup/teardown)
│   ├── global-setup.ts        Runs once — seeds users + enables encryption
│   ├── test.ts                Custom test fixture (BASE_PATH subpath prefixing)
│   └── ui.ts                  activateBootstrapTab (async Bootstrap tab activation)
├── fixtures/                  PHP setup/teardown scripts for test data
│   ├── bootstrap.php          Runs every fixture: CLI guard, Tiki + admin, JSON in/out
│   ├── helpers.php            Shared PHP helpers (bootstrap, key/tracker ops, PDO)
│   ├── create_test_users.php  Global — ensures Test1/Test2 exist
│   ├── enable_encryption.php  Global — persists feature_user_encryption='y'
│   ├── clean_baseline.php     Maintenance — wipes all TC-prefixed keys/trackers
│   ├── provision_admin.php    Provisioning — sets admin password + pass_confirm
│   ├── provision_defaults.php Provisioning — test-friendly prefs on fresh installs
│   └── shared-secrets/        Domain-scoped fixture scripts
│       ├── tc04_{setup,teardown}.php
│       ├── tc06_{setup,teardown}.php
│       ├── tc10_{setup,teardown}.php
│       └── tc11_{setup,teardown}.php
├── suites/                    All runnable test domains (testDir scope)
│   ├── shared-secrets/        Domain: Shared Secrets / Tracker Encryption
│   │   ├── helpers.ts         Domain-specific helpers (re-exports common/auth)
│   │   └── tc*.spec.ts
│   ├── wiki-pages/            Domain: Wiki page management
│   │   ├── helpers.ts         Domain-specific helpers (re-exports common/auth)
│   │   └── tc*.spec.ts
│   └── smoke/                 Domain: Framework smoke tests (subpath routing)
│       └── tc*.spec.ts
└── playwright.config.ts       testDir "./suites" — scoped to runnable suites only
```

### Adding a new domain

1. Create a subdirectory: `tests/e2e/suites/<domain-name>/`
2. Add `helpers.ts` — import shared auth from `../../common/auth`, add domain-specific helpers
3. Add `tc*.spec.ts` spec files
4. If PHP fixtures are needed, add a `fixtures/<domain-name>/` subfolder and write each
   script to the fixture contract below: a `return function (array $input): array { ... }`,
   with no bootstrap code of its own

## Fixtures

PHP scripts in `fixtures/` create and clean up database objects required by specific test cases.

### The bootstrap

No fixture script bootstraps itself. They are all run through `fixtures/bootstrap.php`, which
owns every step they would otherwise repeat: routing errors to stderr, the CLI-only guard,
`tiki-setup.php` and `helpers.php`, `bootstrap_admin()`, decoding the input argument, printing
the result as JSON on stdout, and turning a thrown exception into a stderr message and a
non-zero exit.

```bash
php tests/e2e/fixtures/bootstrap.php <script> [base64(JSON input)]
```

The script path is relative to `fixtures/`, so no caller has to know where the Tiki root sits
inside a container. A fixture therefore contains only its own logic, and returns a callable:

```php
<?php

// TC-11 setup: creates an encryption key assigned to Test1.

return function (array $input): array {
    delete_keys_by_name('TC11-EncKey');

    $key = create_encryption_key('TC11-EncKey', 'Test1');

    return ['keyId' => $key['keyId'], 'share' => $key['shares'][0]];
};
```

The returned array is printed as JSON on stdout, the only thing ever written there. `$input` is
the decoded argument: for a `tcNN_teardown.php` that is whatever `tcNN_setup.php` printed.
Failures throw, and `common/fixtures.ts` surfaces the message and skips the suite.

A fixture that needs a different environment says so instead of hand-rolling one:

```php
return [
    'tiki'  => false,   // skip tiki-setup.php, raw PDO access only
    'admin' => false,   // skip bootstrap_admin(), defaults to the 'tiki' value
    'run'   => function (array $input): array { ... },
];
```

### Wiring a fixture to a spec

Per-testcase fixtures are wired **by naming convention** — a spec never writes its own
`beforeAll`/`afterAll` plumbing. Add one line at the top of the spec:

```ts
import { useSuiteFixtures } from "../../common/fixtures";

const fx = useSuiteFixtures(test);
```

For a spec at `suites/<suite>/tcNN-*.spec.ts`, the runner checks for
`fixtures/<suite>/tcNN_setup.php` and `fixtures/<suite>/tcNN_teardown.php` and runs
whichever exist (the call is a no-op for specs without fixture scripts):

- **setup** runs in `beforeAll` and must print a single JSON object on stdout —
  available to the spec as `fx.data` (e.g. `fx.data.trackerId`). If setup fails,
  the whole spec file is skipped with a warning pointing at `TIKI_FIXTURE_CMD`.
- **teardown** runs in `afterAll` and receives that same JSON as its `$input`
  (handed over base64-encoded, so it is shell-safe through `ddev exec` / docker /
  plain php), and can read back the IDs setup created.

| Script                                       | Used by        | Purpose                                       |
|----------------------------------------------|----------------|-----------------------------------------------|
| `bootstrap.php`                              | Every fixture  | Runs a fixture script: CLI guard, Tiki bootstrap, admin permissions, JSON in/out |
| `create_test_users.php`                      | Global setup   | Ensure Test1/Test2 exist with known passwords |
| `enable_encryption.php`                      | Global setup   | Persist `feature_user_encryption='y'` in the DB (idempotent) |
| `clean_baseline.php`                         | Maintenance    | Wipe all TC-prefixed keys and trackers from DB (recovery after a killed run in existing-instance mode) |
| `provision_admin.php`                        | Provisioning   | Set admin password + `pass_confirm` on a fresh install (called by `e2e-provision.sh`) |
| `provision_defaults.php`                     | Provisioning   | Test-friendly prefs on a fresh install, e.g. no admin wizard (called by `e2e-provision.sh`) |
| `shared-secrets/tc04_setup.php`              | shared-secrets | Create tracker + encrypted field + key        |
| `shared-secrets/tc04_teardown.php`           | shared-secrets | Remove TC-04 tracker and key                  |
| `shared-secrets/tc06_setup.php`              | shared-secrets | Create tracker + key + return Test1 share string |
| `shared-secrets/tc06_teardown.php`           | shared-secrets | Remove TC-06 tracker and key                  |
| `shared-secrets/tc10_setup.php`              | shared-secrets | Create tracker + key with share string        |
| `shared-secrets/tc10_teardown.php`           | shared-secrets | Remove TC-10 tracker and key                  |
| `shared-secrets/tc11_setup.php`              | shared-secrets | Create key for regeneration test              |
| `shared-secrets/tc11_teardown.php`           | shared-secrets | Remove TC-11 key                              |

## Test Suites

### `shared-secrets/` — Shared Secrets / Tracker Encryption

| File                                    | Coverage                                |
|-----------------------------------------|-----------------------------------------|
| `tc01-encryption-tab.spec.ts`           | Encryption tab visible/accessible       |
| `tc02-create-key.spec.ts`               | Key creation with one shared user       |
| `tc03-create-key-with-users.spec.ts`    | Key creation with shared users          |
| `tc04-tracker-field-encryption.spec.ts` | Write + read encrypted tracker field    |
| `tc05-edit-key.spec.ts`                 | Edit key metadata (rename, description, duplicate guard) |
| `tc06-unlock-flow.spec.ts`              | Encrypted field unlock flow (share entry, session unlock) |
| `tc07-delete-key.spec.ts`               | Key deletion flow                       |
| `tc08-security.spec.ts`                 | Unauthorized access is blocked          |
| `tc09-algorithm.spec.ts`                | Algorithm dropdown (absent on v27.x; future-proofed) |
| `tc10-manual-key-entry.spec.ts`         | Manual share entry via UI modal         |
| `tc11-key-regeneration.spec.ts`         | Key regeneration flow                   |

### `wiki-pages/` — Wiki Page Management

| File                          | Coverage                                           |
|-------------------------------|----------------------------------------------------|
| `tc01-page-lifecycle.spec.ts` | Create, view content, and delete a wiki page       |
| `tc02-page-access.spec.ts`    | Page listing accessible; nonexistent page prompts creation |

### `smoke/` — Framework & Environment Smoke Tests

Fast checks run first to catch harness or environment problems before a domain suite does.

| File                            | Needs live Tiki | Coverage                                           |
|---------------------------------|:---------------:|----------------------------------------------------|
| `tc00-subpath-routing.spec.ts`  | no              | `page.goto` subpath prefixing: prepends `BASE_PATH` to leading-slash URLs, leaves absolute and relative paths untouched |
| `tc01-environment.spec.ts`      | yes             | Environment readiness: instance reachable, admin credentials log in, User Encryption feature enabled |

## Self-Contained Mode (dedicated test DB + built-in web server)

By default the suite targets an existing Tiki instance (`TIKI_BASE_URL`). Self-contained
mode instead provisions a **disposable Tiki in this checkout** — dedicated test database,
scripted clean install, PHP's built-in web server — so no pre-existing instance or web
server is needed, and a development database can never be touched by test runs.

```bash
# 1. Provision once (writes db/local.php, installs the schema, sets admin password).
#    Refuses to overwrite an existing db/local.php — use a fresh checkout/worktree.
tests/e2e/bin/e2e-provision.sh

# 2. Run — Playwright starts/stops `php -S` automatically:
cd tests/e2e && TIKI_E2E_WEBSERVER=1 npx playwright test
```

Configuration (env or `tests/e2e/.env`): `TIKI_E2E_DB_HOST` / `_NAME` / `_USER` / `_PASS`
select the test database, `TIKI_E2E_PORT` the server port (default 8686). In this mode
`TIKI_BASE_URL` and `TIKI_FIXTURE_CMD` are overridden — the spawned server and this
checkout's host PHP are, by definition, the system under test.

Every test run starts from a schema-fresh database (re-run `e2e-provision.sh --force`
to reset), which guarantees the DB state concern raised in review: tests cannot leak
state into — or depend on state from — a developer instance.

## CI Integration

The dedicated `e2e-tests` stage in `.gitlab-ci.yml` runs the suite fully
self-contained — no pre-existing Tiki instance is involved. It contains one
manual job per scope, all sharing the `.e2e-base` template:

| Job | Runs |
|---|---|
| `e2e-all` | every suite under `suites/` |
| `e2e-suite: [smoke]` | framework + environment smoke checks |
| `e2e-suite: [shared-secrets]` | the Shared Secrets domain |
| `e2e-suite: [wiki-pages]` | the wiki-pages domain |

The per-suite jobs come from a `parallel:matrix` on the `E2E_SUITE` variable —
after `mkdir suites/<domain>/`, adding the domain name to that matrix list is
the only CI change needed to get a dedicated play button for it.

1. **Image & services** — the official Playwright image (Node + browsers, version pinned
   to the `@playwright/test` devDependency) plus a MariaDB service container (`mysql` alias).
2. **Dependencies via `needs`** — the job downloads artifacts from three build-stage jobs:
   `composer` (`vendor_bundled/`), `node_modules`, and `node_build` (`public/generated/`
   JS/CSS bundles + theme CSS). The `node_build` artifacts are essential: without them
   every page loads without Bootstrap JS and all tab/modal interactions time out.
3. **PHP CLI** — installed via apt inside the job (`php-cli`, DB/XML/intl/… extensions,
   and `php-bcmath`, required by the Shamir secret-sharing library behind
   shared-secrets key creation).
4. **Provision** — `bin/e2e-provision.sh` writes `db/local.php` against the MariaDB
   service, installs the Tiki schema, and sets the admin password (self-contained mode,
   `TIKI_E2E_WEBSERVER=1`).
5. **Run** — `npx playwright test` serves the checkout with `php -S` via the Playwright
   `webServer` hook and runs the job's scope (everything for `e2e-all`; the
   `suites/${E2E_SUITE}/` subtree for a matrix job). The PHP server's stderr (per-request log lines
   plus any PHP errors) is redirected to `tests/e2e/php-server.log` — piped into the job
   output it exceeds GitLab's 4MB log limit and truncates the test results. The file is
   uploaded as an artifact, and an `after_script` greps it for PHP fatals so they still
   show in the job log.

All jobs are `when: manual` + `allow_failure: true` while runtimes are validated on the
shared runners; promote them to automatic once stable. To launch one, open the pipeline
on the merge request and press the ▶ play button on the job (stage `e2e-tests`).

### Accessing the HTML report artifact

Every job uploads `tests/e2e/playwright-report/` and `tests/e2e/php-server.log`
as artifacts (pass or fail, kept 2 days). To view the report:

1. Open the finished job page in GitLab.
2. In the right sidebar under **Job artifacts**, click **Browse**.
3. Navigate to `tests/e2e/playwright-report/` and click `index.html` — GitLab renders
   it directly, giving the interactive Playwright report (pass/fail per test,
   screenshots, videos, and traces for failures).

Direct URL pattern:
`https://gitlab.com/tikiwiki/tiki/-/jobs/<job-id>/artifacts/file/tests/e2e/playwright-report/index.html`

Alternatively, **Download** the artifact zip and open
`tests/e2e/playwright-report/index.html` locally, or unzip it and run
`npx playwright show-trace <path-to>/trace.zip` to step through a failure.
