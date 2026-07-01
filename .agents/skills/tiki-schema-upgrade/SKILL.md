---
name: tiki-schema-upgrade
description: Use when adding or altering a DB table or column, writing a schema migration/patch, or changing db/tiki.sql. Covers the mandatory two-file rule, the YYYYMMDD_*_tiki patch conventions, the PHP companion migration shape, the update command, and the never-delete-data / no-downgrade policies.
---

# Tiki DB schema upgrades — the two-file rule

Every DB change lands in **two** files. Skip either and you break one install
path. Paths below are relative to the Tiki source-tree root — the examples are
real patches you can open.

| File | Purpose | Who reads it |
|---|---|---|
| `db/tiki.sql` | Canonical schema | **Fresh installs** |
| `installer/schema/YYYYMMDD_<desc>_tiki.sql` | Incremental patch | **Upgrades** of existing DBs |

An upgrader runs the patches; a fresh installer loads `tiki.sql`. Update only
the patch and new installs miss the change; update only `tiki.sql` and existing
sites never get it. **Always edit both, and keep them consistent.**

Real proof: patch `installer/schema/20260622_add_lastmodif_to_tiki_articles_tiki.sql`
does `ALTER TABLE ... ADD COLUMN lastModif`, and `CREATE TABLE tiki_articles`
in `db/tiki.sql` already lists that same `lastModif` column.

## Patch filename convention

`YYYYMMDD_short_description_tiki.sql` — date prefix orders application; snake_case
description; **must end in `_tiki`**.

```
20260416_add_is_resolved_to_comments_tiki.sql
20260622_add_lastmodif_to_tiki_articles_tiki.sql
```

- Pick `YYYYMMDD` = today (or later than the newest existing patch — `ls
  installer/schema/ | tail`). Don't collide with an existing date+name.
- The `_tiki` suffix is load-bearing: `_tiki` patches are the core set you also
  mirror into `tiki.sql`. A **non-`tiki`-suffixed** patch (third-party/custom
  deployment) applies only on upgrade and needs no `tiki.sql` change.

## The SQL patch (the common case)

Pure DDL — one file, plus the mirror edit in `tiki.sql`. No PHP needed.

```sql
-- installer/schema/20260416_add_is_resolved_to_comments_tiki.sql
ALTER TABLE `tiki_comments` ADD COLUMN `is_resolved` char(1) NOT NULL DEFAULT 'n' AFTER `locked`;
```

Then add the same column to `CREATE TABLE tiki_comments` in `db/tiki.sql`.

**Gotcha — `ENGINE=MyISAM` on new `CREATE TABLE`, not InnoDB.** Every table in
`db/tiki.sql` and every recent `CREATE TABLE` patch is written
`ENGINE=MyISAM`. The installer *rewrites* `MyISAM`→`InnoDB` at load time
(`installer/Installer.php:270-272`, `str_ireplace`), so fresh installs end
up InnoDB — but the literal you write to match the tree is `MyISAM`.

```sql
CREATE TABLE `tiki_myfeature` (
  `myfeatureId` int(11) NOT NULL auto_increment,
  ...
  PRIMARY KEY (`myfeatureId`)
) ENGINE=MyISAM;
```

Table naming (`tiki_` prefix, ≤26 chars, lowercase; backtick every identifier)
and query rules live in **tiki-database**.

## The PHP companion (complex / data migrations)

When SQL can't express it (data transforms, conditional logic, cross-version
compatibility workarounds, pref defaults), add a `.php` next to the `.sql` with
the **same basename**: `YYYYMMDD_<desc>_tiki.php`.

Two shapes, both dispatched by `installer/Installer.php` (lines 160-199):

### Modern — standalone `upgrade_<basename>($installer)`

The dominant idiom (real example
`installer/schema/20260312_change_fixed_width_default_tiki.php`):

```php
<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Installer\Installer;

function upgrade_20260312_change_fixed_width_default_tiki($installer)
{
    global $prefs;
    // do everything: $installer->query(...), data fixes, pref defaults...
    // return true/null on success, false to mark the patch FAILED
}
```

The function name **must** be `upgrade_` + the exact file basename (no
extension). **Gotcha:** when an `upgrade_` function exists, the installer treats
it as standalone and does **not** auto-run a same-named `.sql`. Either put your
DDL inside the function (`$installer->query(...)`) or use the pre/post form
below.

### Legacy — `pre_`/`post_<basename>($installer)` wrapping the SQL

Documented flow: run `pre_`, run the `.sql`, run `post_`. Use when you need the
`.sql` file to auto-run and just want hooks around it.

```php
function pre_20240101_my_desc_tiki($installer)  { /* before the SQL */ }
function post_20240101_my_desc_tiki($installer) { /* after the SQL  */ }
```

Both `pre_`/`post_` are still supported by the installer but are legacy; new code
uses `upgrade_`. (See divergence note at the end of this skill.)

## Apply / test the upgrade

```bash
php console.php -n database:update    # applies pending patches + runs PHP companions
sh doc/devtools/sqlupgrade.sh         # MultiTiki-aware wrapper
```

`database:update` is `lib/core/Tiki/Command/UpdateCommand.php`
(`name: 'database:update'`). Update also runs via the web installer
(`tiki-install.php`). Order of events per patch: `pre_` → SQL → `post_`
(or the standalone `upgrade_`), then the patch is recorded. **Tiki 27+ stops on
the first error.**

Global post-all-patches scripts (cache clears, etc.) go in `installer/script/`.

## Non-negotiable policies

- **Never delete data in a patch.** Removing a feature? Leave its rows/tables in
  place for admins to review; Tiki Check flags unused tables and Orphan
  preferences handles abandoned prefs. Patches drop *nothing* that holds data.
- **No downgrade path exists.** Schema changes are irreversible. **Test on a DB
  backup first** — there is no rollback command.
- **Cross-version compatibility.** Patches must run across the supported PHP /
  MySQL / MariaDB matrix. Older engines lack e.g. `IF EXISTS` in `ALTER TABLE`;
  guard such cases in a PHP companion (probe with `SHOW INDEX` / `SHOW COLUMNS`
  before altering — see `installer/schema/20260509_cleanup_duplicate_indexes_tiki.php`).

## Verify against source

```bash
ls installer/schema/ | tail -20            # newest patches → pick your date
grep -n "ENGINE=" db/tiki.sql | head       # MyISAM convention
grep -rn "upgrade_\|pre_\|post_" installer/schema/<file>.php
```

Good models: `installer/schema/20260416_add_is_resolved_to_comments_tiki.sql`
(one-line ALTER), `..._20260312_change_fixed_width_default_tiki.php` (standalone
`upgrade_` doing a pref default), `..._20260509_cleanup_duplicate_indexes_tiki.php`
(compatibility-safe conditional DDL).

## Related skills

- **tiki-database** — table naming, backticks, PDO `?`-placeholder queries.
- **tiki-preferences** — migrations that seed/adjust pref defaults (as above).
- **tiki-page-bootstrap** — where schema files sit in the code-layout map.

---

### Divergences from the officially documented pattern

- **PHP companion naming.** The doc shows only `pre_`/`post_<name>()`. The live
  tree overwhelmingly uses standalone `upgrade_<name>($installer)` (89 files vs
  3 `pre_`/5 `post_`); the installer supports all three
  (`installer/Installer.php:160-199`). This skill leads with `upgrade_` and
  keeps `pre_`/`post_` as the documented legacy form.
- **CREATE TABLE engine.** The doc's example writes `ENGINE=InnoDB`; every real
  table is written `ENGINE=MyISAM` and converted to InnoDB at install time
  (`installer/Installer.php:270-272`). This skill teaches `ENGINE=MyISAM` to
  match the tree while still yielding InnoDB on fresh installs, as the doc states.
