---
name: tiki-page-bootstrap
description: Use when creating or editing a Tiki front-end page (tiki-*.php), scaffolding a new feature entry point, wiring a page to its Smarty template, or deciding where new PHP/template/JS code belongs in the Tiki source tree. Covers the mandatory page-bootstrap skeleton, its strict ordering, and the code-layout map.
---

# Tiki page bootstrap + code-layout map

How to write the entry point for a Tiki feature and place code in the right layer.
Paths below are relative to the Tiki source-tree root — the examples are
real files you can open and copy from.

## The canonical page skeleton

Every executable feature page is `tiki-<feature>.php`. Fixed shape
(real example: `tiki-admin_sitemap.php`):

```php
<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

$inputConfiguration = [
    [
        'staticKeyFilters' => [
            'rebuild' => 'int',        // filter each expected request key
        ],
    ],
];
require_once('tiki-setup.php');        // bootstrap — nothing works before this

$access->check_permission('tiki_p_admin');   // 2. permission
$access->check_feature('sitemap_enable');     // 1. feature toggle

// ... feature logic; use $prefs, $user, $smarty, TikiLib::lib('...') ...

$smarty->assign('mid', 'tiki-admin_sitemap.tpl');   // inner content template
$smarty->display('tiki.tpl');                        // render root chrome
```

The matching template is `templates/tiki-admin_sitemap.tpl`. The page name,
the `mid` template name, and the `.tpl` file all mirror each other.

## Strict ordering — do not reorder

1. **Copyright header** — first thing after `<?php` (exact 4-line block above; an
   optional `/** @package tikiwiki */` docblock may sit between `<?php` and it).
2. **`$inputConfiguration`** — MUST be assigned *before* the require. It is read
   at include time by `tiki-setup.php`; declaring it after does nothing. Input
   filtering is its own skill — here, just know the block goes first.
3. **`require_once('tiki-setup.php')`** — the bootstrap. Only after this line do
   you have: DB connection, `$prefs`, authenticated `$user`/`$group`/`$language`,
   `$smarty`, `$access`, and `TikiLib::lib()`.
4. **Access checks** — `$access->check_feature(...)` and
   `$access->check_permission(...)` at the top, before any work.
5. **Logic**, then **`assign('mid', ...)`** + **`display('tiki.tpl')`** last.

A page can skip `$inputConfiguration` only if it takes no input. It can skip the
`mid`/`display` pair if it just redirects (`$access->redirect(...)`) or delegates
via `include('tiki-other.php')` — see `tiki-orphan_pages.php` and
`tiki-list_users.php` for those minimal forms.

## Two page styles

- **Legacy front-end** — `tiki-<feature>.php` + `templates/tiki-<feature>.tpl`.
  Still active, still the norm for admin/editor screens. This is the skeleton above.
- **Modern canvas** — `tiki-index.php` renders wiki pages as a general-purpose
  surface; functionality is embedded through **wiki plugins**
  (`lib/wiki-plugins/wikiplugin_*.php`), not new top-level pages. Prefer this for
  content-embeddable features (wiki plugins are a separate skill).

## Where code goes (layout map)

| You are adding… | Put it in |
|---|---|
| A feature front-end page | `tiki-<feature>.php` (+ `templates/tiki-<feature>.tpl`) |
| Modern OOP library code | `lib/core/` (Zend-style folder = namespace) |
| An AJAX/REST service | `lib/core/Services/<Name>/Controller.php` |
| Unified Search code | `lib/core/Search/` |
| A wiki plugin | `lib/wiki-plugins/wikiplugin_<name>.php` (+ `templates/wiki-plugins/`) |
| A Smarty modifier/block/function | `lib/smarty_tiki/` |
| jQuery/JS | `lib/jquery_tiki/` |
| A sidebar module | `modules/mod-<name>.php` (+ `templates/modules/mod-<name>.tpl`) |
| A DB schema change | `db/tiki.sql` **and** `installer/schema/` (both — separate skill) |
| A feature preference | `lib/prefs/` (every feature is opt-in — separate skill) |

**Gotcha — never add to `lib/tikilib.php` or `lib/userlib.php`.** These are
the ancient core (25 yrs of accretion). New code goes in `lib/core/` or a
feature-specific `lib/<feature>/` subfolder, never bolted onto these.

## New-directory rule

Any new directory you create gets a blank `index.php` (prevents directory
listing). Non-negotiable for every folder you create.

## Standards for the file

- **PHP style:** Tiki ≥23 → PSR-12 (4 spaces). Tiki ≤22 → Zend standard with
  **tabs**. Match the surrounding files if unsure.
- Filenames lowercase; LF line endings; UTF-8.
- Don't reindent lines you didn't change — keeps diff/blame clean.

## Verify against source

When unsure of the exact current pattern, grep the source tree rather than guessing:

```bash
grep -l inputConfiguration tiki-*.php        # pages using input filtering
grep -rn "assign('mid'" tiki-admin_sitemap.php
```

`tiki-admin_sitemap.php` (full pattern), `tiki-calendar.php` (large,
feature-gated), and `tiki-orphan_pages.php` (minimal) are good models.
