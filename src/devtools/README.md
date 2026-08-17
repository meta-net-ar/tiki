# Tiki developer scripts

This directory contains developer, release, translation, maintenance, and
deployment utilities that are not part of the GitLab CI implementation. Tools
that are executed by GitLab CI live in [`../ci`](../ci/README.md).

Run commands from the root of a Tiki checkout unless a tool says otherwise.
Most PHP scripts expect the Tiki bootstrap files, Composer dependencies, and a
configured checkout to be available.

## Before running a script

- Read the script before using it. Several tools modify
  databases, Git branches, language files, or remote package storage.
- Back up the database and working tree before using a destructive tool.
- Use the CLI PHP binary. Web execution is unsupported unless explicitly noted.
- Review generated changes with `git diff` before committing them.
- Scripts which contact GitLab, `composer.tiki.org`, or other sites require
  network access. Some also require credentials or server-specific setup.

## General development tools

### `run_local_checks.php`

Runs the checks relevant to the current branch before a push. It determines the
base commit, selects checks based on changed file types, runs the shared checks
from `src/ci`, and reports every failure.

```bash
php src/devtools/run_local_checks.php
php src/devtools/run_local_checks.php --skip=2,5,9
php src/devtools/run_local_checks.php --stop-on-failure
php src/devtools/run_local_checks.php --skip-checks
```

Use `-h` or `--help` for the option list. Composer dependencies must be
installed for Composer, PHPCS, PHP lint, Smarty lint, and related checks.

### `check_url_availability.php`

Performs the network-dependent release check for external URLs found by the url scanner. 
Requests are made concurrently, and results are grouped as active, broken, or uncertain.

```bash
php src/devtools/check_url_availability.php
php src/devtools/check_url_availability.php --severity=critical,high
```

This can take time, and remote sites may rate-limit or block automated requests.
The structural external-link check used by CI is in `src/ci`.

### `TikiCodeStyleSettingsPhpStorm.xml`

PHPStorm code-style settings used to produce the repository's `.editorconfig`.
Import this file through the IDE's code-style settings when maintaining that
configuration.

## Release and source-maintenance tools

### `release.php`

Interactive release manager. It updates release metadata, languages, changelog,
and copyright information; builds packages; commits changes; and creates tags.
This tool can modify the checkout and remote Git history, so release managers
should first run it in development mode and inspect every step.

```bash
php src/devtools/release.php --help
php src/devtools/release.php --howto
php src/devtools/release.php --devmode 30.0
php src/devtools/release.php 30.0 RC1
```

Important options include `--no-commit`, `--no-check-vcs`,
`--no-first-update`, `--no-lang-update`, `--no-changelog-update`,
`--no-copyright-update`, `--no-packaging`, `--no-tagging`, `--force-yes`,
`--debug-packaging`, and `--skip=N`. Subrelease names beginning with `pre` are
not tagged.

### `generate_copyright.php`

Rebuilds `copyright.txt` from the Git author history and the current Tiki
version.

```bash
php src/devtools/generate_copyright.php
```

### `composer_http_mode.php`

Temporarily changes the bundled Composer repository from HTTPS to HTTP for
restricted legacy environments. `execute` creates HTTPS backups and rewrites
the Composer files; `revert` restores those backups.

```bash
php src/devtools/composer_http_mode.php execute
php src/devtools/composer_http_mode.php revert
```

HTTP mode disables transport security. Use it only when unavoidable and always
run `revert` afterward.

### `mergelang.php`

Merges language files from one Tiki checkout into another. An optional `lang`
argument limits the merge to comma-separated language codes.

```bash
php src/devtools/mergelang.php /path/to/source /path/to/target
php src/devtools/mergelang.php /path/to/source /path/to/target lang=pt-br,es
```

## Translation tools

### `update_english_strings.php`

Replaces an English source string in every `lang/*/language.php` catalogue.
The script is experimental and writes files in place.

```bash
php src/devtools/update_english_strings.php "Old text" "New text"
```

Review all resulting language-file changes before committing them.

### `commit_translations_by_lang.php`

Exports database translations to language files, groups contributors by
language, and creates language-specific Git commits and branches. It requires a
configured Tiki instance and repository access.

```bash
php src/devtools/commit_translations_by_lang.php
```

### `export_all_translation_to_file.php`

Legacy translation export/commit utility. It reads database translations and
builds contributor messages. Parts of its commit implementation still use SVN
commands and are retained primarily for historical intent; inspect and update
the script before operational use.

```bash
php src/devtools/export_all_translation_to_file.php
```

## Reports and administrative maintenance

### `prefreport.php`

Produces a CSV inventory of Tiki preferences, including defaults, descriptions,
dependencies, permissions, and source locations.

```bash
php src/devtools/prefreport.php > prefreport.csv
```

### `tiki-sync_ldap.php`

Synchronizes every Tiki user and group with the configured LDAP directory.

```bash
php src/devtools/tiki-sync_ldap.php
```

Run only on a configured site after verifying LDAP settings and taking a
database backup.

### `process_user_logins.php`

One-way migration that converts email-address logins to conventional usernames
and optionally migrates tracker profile pictures. Before it can run, an
administrator must edit the configuration variables inside `processUsers()`.

```bash
php src/devtools/process_user_logins.php
```

There is no automatic undo. Close the site, test on an offline database copy,
back up production, process small batches, and rebuild the search index after
completion.

## Composer and Satis maintenance

These tools maintain Tiki's Composer mirror. They are intended for mirror
administrators rather than routine development.

### `check_satis_validation.php`

Checks that every non-extension dependency in
`vendor_bundled/composer.json` is present in `src/config/satis.json`.

```bash
php src/devtools/check_satis_validation.php
```

### `composer_packages_in_use.php`

Reads supported Tiki branches and reports Composer packages required across
those branches. `all` is the default and emits combined Composer constraints;
`current` emits installed package/version paths.

```bash
php src/devtools/composer_packages_in_use.php
php src/devtools/composer_packages_in_use.php all
php src/devtools/composer_packages_in_use.php current
```

It bootstraps Tiki and contacts the Git repository and raw branch files.

### `satis_composer_packages_gitlab.php`

Queries GitLab branches and tags, builds a reduced `satis.json`, and writes
package-availability reports under Tiki's Satis temporary directory. Run it from
a disposable working directory because it writes `satis.json` in the current
directory.

```bash
php /path/to/tiki/src/devtools/satis_composer_packages_gitlab.php
```

### `satis_packages_cleanup.php`

Compares the Composer mirror's published metadata with files in its local
`dist/` directory. Edit `$composer_packages_path` in the script before use. The
default mode only lists unused files; `--cleanup` permanently deletes them.

```bash
php src/devtools/satis_packages_cleanup.php
php src/devtools/satis_packages_cleanup.php --cleanup
```

Always inspect the dry-run output and back up the mirror before cleanup.

### `src/config/satis.json`

Please see the [satis.json](../config/README.md) documentation for
more information.

## Database migration helpers

### `tiki.export.sh`

Prompts for MySQL connection details (or reads the corresponding environment
variables) and creates a timestamped SQL dump with `mysqldump`.

```bash
bash src/devtools/tiki.export.sh
```

Supported environment variables are `TIKI_DBHOST`, `TIKI_DBNAME`,
`TIKI_DBUSER`, and `TIKI_DBPASSWD`.

### `tiki.import.sh`

Imports a SQL dump into a target Tiki checkout, clears caches, updates
permissions and the schema, rebuilds the search index, and changes selected
preferences.

```bash
bash src/devtools/tiki.import.sh
```

It accepts `TIKI_PATH`, `TIKI_DBDUMP`, `TIKI_DBHOST`, `TIKI_DBNAME`,
`TIKI_DBUSER`, and `TIKI_DBPASSWD`, prompting for missing values. The script
drops and recreates the target database. Inspect it before use.

## Tiki Instance Manager (`tim/`)
Please see the [Tiki Instance Manager README](../tim/README.md) for more information.