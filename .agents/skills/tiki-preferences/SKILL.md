---
name: tiki-preferences
description: Use when adding, defining, or reading a Tiki preference/feature toggle — writing a descriptor in lib/prefs/, mounting it on an admin panel with {preference}, gating a feature opt-in, or reading a setting via $prefs. Covers the descriptor array shape, the 'y'/'n' string gotcha, and conflicts-vs-dependencies enforcement.
---

# Tiki preferences (make every feature opt-in)

Every Tiki feature is off until a preference turns it on. A preference is:
declared once in a descriptor file, mounted on an admin panel, then read at
runtime through the global `$prefs` array. Paths below are relative to the Tiki
source-tree root; the cited files are real — open and copy from them.

## 1. Declare the descriptor

Descriptors live in `lib/prefs/<token>.php`. **The filename is the first
underscore-delimited token of the preference key:**

| Key | File |
|---|---|
| `feature_areas`, `feature_wiki` | `lib/prefs/feature.php` |
| `wiki_page_regex`, `wiki_url_scheme` | `lib/prefs/wiki.php` |
| a key with **no** underscore | `lib/prefs/global.php` |

Reserve `feature_*` (→ `feature.php`) for major, user-visible features gated by
`$access->check_feature()`. Everything else goes in the file matching its token.

Each file defines one function `prefs_<token>_list()` returning a map of
descriptors (real: `lib/prefs/feature.php:8`, `lib/prefs/wiki.php:7`):

```php
<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function prefs_feature_list($partial = false)
{
    return [
        'feature_areas' => [
            'name'         => tra('Areas'),               // required, human label
            'description'  => tra('Binding categories and perspectives to each other'), // required, searchable
            'type'         => 'flag',                      // boolean → 'y'/'n'
            'default'      => 'n',                         // every feature ships OFF
            'help'         => 'Areas',                     // doc.tiki.org page for help popup
            'tags'         => ['experimental'],            // basic|advanced|experimental|deprecated|unavailable
            'dependencies' => [                            // informational only — NOT enforced
                'feature_categories',
                'feature_perspective',
            ],
        ],
    ];
}
```

`type` values: `flag` (boolean 'y'/'n'), `list`, `radio`, `multilist`,
`multicheckbox`, `text`, `textarea`, `password`. Choice types add
`'options' => [...]`. Other common fields: `filter` (`digits`/`url`/`text`/…),
`size`, `keywords`, `public`, `perspective`. Wrap all human strings in `tra()`
(see **tiki-i18n**).

**Gotcha — `dependencies` is informational only; `conflicts` is enforced.**
The engine renders dependency hints but does not block on them
(`lib/prefslib.php:229` — `FIXME: Dependencies are not enforced currently`).
`conflicts` *does* prevent enabling a flag while a conflict is active
(`lib/prefslib.php:239`). Its real shape is keyed by the pref's own value,
each entry `preference`/`value`/`message` (real: `lib/prefs/feature.php:624`):

```php
'conflicts' => [
    'y' => [                                   // "if THIS pref is 'y'…"
        'preference' => 'session_storage',     // …and session_storage…
        'value'      => 'db',                  // …is 'db', block it
        'message'    => tra('It is recommended not to set the session storage to database if you use the webmail feature.'),
    ],
],
```

## 2. Mount it on an admin panel

Add one line to the matching `templates/admin/include_*.tpl`
(real: `templates/admin/include_comments.tpl:14`):

```smarty
{preference name=feature_areas}
```

Useful attributes (see `Code-Howto-Add-a-preference-to-an-admin-panel.md`):

```smarty
{preference name=feature_wiki visible="always"}                {* ignore basic/advanced filter *}
{preference name=some_name label="{tr}Shorter Name{/tr}"}      {* short label, full name still searchable *}
```

Hide dependent options when a parent flag is off by giving the wrapper
`id="<parent_pref>_childcontainer"`:

```smarty
<div class="adminoptionboxchild" id="feature_multilingual_childcontainer">…</div>
```

Custom rendering templates live in `templates/prefs/`. Smarty/template
conventions are **tiki-smarty-ui**.

## 3. Read it at runtime

After `tiki-setup.php`, the setting is in the global `$prefs`
(real: `tiki-index.php:66`):

```php
if ($prefs['feature_wiki_structure'] == 'y') { ... }   // flag on
if ($prefs['wiki_url_scheme'] !== 'urlencode') { ... }  // non-boolean value
```

**Gotcha — booleans are the STRINGS `'y'` / `'n'`, not true/false.** Always
test `== 'y'` (or `!= 'y'`). NEVER test `== 'n'`: an unset/legacy pref can be
`''`, `null`, or absent, so `== 'n'` silently misfires while `!= 'y'` is safe.

For a `feature_*` toggle, gate the page instead of hand-rolling the check —
`$access->check_feature('feature_areas')` in the page bootstrap (see
**tiki-page-bootstrap** for ordering, **tiki-access-control** for perms).

## Cross-cutting reflexes

- **Clear caches after any descriptor or mount change.** The `{preference}`
  block is compiled into the Smarty template cache; edits won't show until you
  clear it: `php console.php cache:clear` (real command:
  `lib/core/Tiki/Command/CacheClearCommand.php:18`), or Admin → clear
  caches / rebuild admin index.
- **PHP style:** Tiki ≥23 → PSR-12, 4 spaces (this tree is `31.0vcs`). Tiki ≤22
  → Zend standard with **tabs**. Match surrounding files.
- Filenames lowercase; LF line endings; UTF-8.
- Don't reindent lines you didn't change — keep the diff/blame clean.
- `lib/prefs/` already exists, so no new directory is needed; if you *do* create
  a new dir, drop in a blank `index.php`.

## Verify against source

```bash
grep -rn "{preference" templates/admin/*.tpl | head     # mount examples
grep -rn "prefs\['feature" tiki-*.php | head            # read examples
grep -n "'type' =>" lib/prefs/feature.php               # descriptor shapes
```
