---
name: tiki-build-composer
description: Use when building Tiki (sh setup.sh), adding or updating a Composer/PHP or npm/JS dependency, working with vendor_bundled or doc/devtools/satis.json, regenerating composer.lock, or checking whether a third-party library's license is allowed. Covers the setup.sh build, the add-a-dependency workflow, the Node version constraint, and the GPL-forbidden licensing rule.
---

# Tiki build + Composer / dependency ops

How to build Tiki and add or update third-party dependencies. Paths below are
relative to the Tiki source-tree root — the cited files are real and openable.

Two dependency worlds since Tiki 27:
- **PHP** libraries → Composer → `vendor_bundled/` (list in `vendor_bundled/composer.json`).
- **JavaScript/frontend** libraries → Node/npm build under `js/` (moved out of Composer in Tiki 27, MR 3760, −700k LOC).

## Build: `sh setup.sh`

One command does both halves. `build()` calls `composer` then `npm_setup`
(`setup.sh:735-738`):

```bash
sh setup.sh
```

- Composer install: `... temp/composer.phar install --working-dir vendor_bundled --prefer-dist --optimize-autoloader --no-interaction` (`setup.sh:634`).
- Node install + build: `npm clean-install --engine-strict` then `npm run build` (`setup.sh:718,725`).
- `--engine-strict` means the build **hard-fails** if your Node/npm is outside the allowed range.
- Broken/partial vendor state → delete `vendor_bundled/vendor/` and rerun `sh setup.sh`.
- Cron / non-interactive: `php8.2 console.php -q vcs:update; sh setup.sh -p php8.2 -k build;`.

## Node version constraint

Node.js + npm are required for dev since Tiki 27. Allowed range (per doc):

```json
"engines": { "npm": ">=9.0.0 <11", "node": ">=18.0.0 <22" }
```

Recommended: **Node 20 (LTS)**. Node is a **build-time** tool only — never a
runtime dependency.

> **Gotcha — verify the live range before trusting the number.** `--engine-strict`
> reads the `engines` block in `package.json`; the checked-in value drifts
> ahead of the docs. Run `grep -n engines -A3 package.json` and match your
> Node/npm to whatever is there, not to a remembered version.

## Add a PHP dependency (Composer)

Order matters — satis first (makes the package resolvable via composer.tiki.org),
then the manifest, then regenerate the lock (`vendor_bundled/composer.json:13-28`):

1. Find it on [packagist.org](https://packagist.org/) or [asset-packagist.org](https://asset-packagist.org/).
2. **Check the license first** (see hard rule below).
3. Add the package to `doc/devtools/satis.json`.
4. Add the `require` entry to `vendor_bundled/composer.json` (keep the list alphabetically sorted).
5. Clear cache: `rm -R ~/.composer/cache/`.
6. Regenerate the lock:
   ```bash
   php temp/composer.phar update --prefer-dist --working-dir="vendor_bundled"
   ```
7. Test.
8. Commit **both** `vendor_bundled/composer.json` **and** `vendor_bundled/composer.lock`.

**Updating** an existing dep is the same shape: add the new version to
`satis.json` **without removing old entries** (older Tiki branches still need
them), bump the constraint in `composer.json`, rerun the `update` command.

### Version constraints — `~` not `^`, never exact

Use tilde ranges (e.g. `~3.0`), and `>=` where appropriate. Do **not** use `^`,
exact versions (`1.10.57`), `*`, or `dev-*` unless truly necessary. `^` is
CI-rejected via `doc/devtools/check_caret_operator.sh`
(`vendor_bundled/composer.json:30-45`). Major bumps (3.x→4.x) go in their own
dedicated, tested commit.

## Add a JS dependency (Tiki 27+)

Do **not** add JS libs to `vendor_bundled`. They live in the Node build under
`js/` (common externals: `js/common-externals/package.json`). Follow the
migration guide at `js/#migrating-dependencies`, then rebuild with
`sh setup.sh`. Prefer **Vue.js 3** for new UI; jQuery-UI is deprecated and jQuery
is legacy-only.

## Licensing — HARD RULE

> **Gotcha — GPL is FORBIDDEN for bundled libraries.** Only **LGPL, MIT, or
> BSD-like** licenses may be bundled. GPL code must **not** be added. This is
> non-negotiable — verify the license *before* step 3 above, because ripping a
> dependency back out later is expensive. Documented historical exceptions like
> Wollabot exist but are not a license to add more — see https://tiki.org/LibLicense.

## Branch-switch breakage

> **Gotcha — after switching branches, delete `temp/cache/container.php`.** The
> compiled DI container is cached and goes stale across branches, producing
> confusing fatal errors. `rm temp/cache/container.php` and rerun.

Other quick fixes: cache corruption →
`php temp/composer.phar clearcache`; "broken signature" →
`php temp/composer.phar install`; out-of-memory → `memory_limit = -1` in `php.ini`.

## Picking a library (before you add anything)

Changing a dependency later is costly, so choose deliberately: document the
requirement on dev.tiki.org, list options with pros/cons, notify the Dev Mailing
List, check for abandoned projects / active forks, verify Tiki + WikiSuite
compatibility, reach community consensus, then implement.

## Cross-cutting reflexes

- Any **new directory** you create needs a blank `index.php` (see **tiki-page-bootstrap**).
- Bundled cleanup (stripping docs/tests/examples from vendors) is handled by `lib/core/Tiki/Composer/` CleanVendors — don't hand-edit vendor trees.
- Patching a dep (last resort, upstream-first) → `installer/composer-patches/composer-patches.json` via `cweagans/composer-patches`.
- Dependencies feed the DI container — see **tiki-services** for wiring; run the suite after a dep change (see **tiki-testing**); license/security review still applies (see **tiki-security**).

## Verify against source

```bash
grep -n engines -A3 package.json                    # live Node/npm range
grep -n "npm clean-install\|npm run build\|composer.phar install" setup.sh
grep -n '"require"' vendor_bundled/composer.json     # PHP dependency list
```

Good models: `setup.sh` (build orchestration), `vendor_bundled/composer.json`
(inline "How to update dependencies" comment block), `doc/devtools/satis.json`
(package repositories).
