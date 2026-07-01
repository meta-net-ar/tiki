---
name: tiki-input-filtering
description: Use when handling request input in a Tiki page, service, or plugin — declaring the $inputConfiguration block before tiki-setup.php, choosing a filter for a superglobal key, filtering array values, or reading runtime-typed input via the JIT filters ($jitRequest / $jitPost / asArray). Covers the declarative rule set, the semantic-filter ladder, and the array-value gotcha.
---

# Tiki input filtering ($inputConfiguration + JIT)

All superglobal input in Tiki is sanitized at include time by a declarative
config, then optionally re-filtered at runtime when the data type only becomes
known later. Ground truth: `lib/core/TikiFilter.php` (filter-name resolver),
`lib/core/DeclFilter.php` (declarative rule engine), `lib/core/JitFilter.php`
(runtime filtering). Wiring: `tiki-setup_base.php:718-757`.

## Declarative filtering — the `$inputConfiguration` block

Assign it **before** `require_once 'tiki-setup.php'`; it is read at include time
(see tiki-page-bootstrap for placement/ordering). Real full example:
`tiki-admin_polls.php:14`.

```php
$inputConfiguration = [
    [
        'staticKeyFilters' => [
            'page'    => 'pagename',      // scalar value → filter name
            'content' => 'wikicontent',
            'pollId'  => 'int',
        ],
        'staticKeyFiltersForArrays' => [
            'categoryId' => 'digits',     // ARRAY value → filter each element
        ],
        'catchAllUnset' => null,          // drop every key not matched above
    ],
];
require_once 'tiki-setup.php';
```

Structure: outer array → one or more rule-set arrays (numeric-keyed) → each a map
of `rule => argument`. Rules are applied in the order written and **the first rule
that matches a key wins** (`DeclFilter::filter()`, `lib/core/DeclFilter.php:54`).
So a catch-all rule must be **last**.

| Rule | Applies to | Note |
|---|---|---|
| `staticKeyFilters` | scalar values | map key → filter name |
| `staticKeyFiltersForArrays` | array values | filters every element, recursively |
| `staticKeyUnset` | listed keys | `['a','b']` — always removed |
| `keyPatternFilters` / `keyPatternFiltersForArrays` | PCRE-matched keys | scalar / array |
| `keyPatternUnset` | PCRE-matched keys | removed |
| `catchAllUnset` | everything unmatched | pass `null`; prefer this — real: `tiki-contact.php:21` |

If you do not `catchAllUnset`, undeclared scalar input still gets a global
catch-all `xss` filter (unless `tiki_allow_trust_input`), applied in
`tiki-setup_base.php:750`. Unsetting is stronger — use it whenever the page's
input surface is fully enumerable.

## Choosing a filter — most restrictive semantic wins

`TikiFilter::get($name)` lists filters "most strict to least" and tells you to
"choose the first filter on the list that satisfies your requirements"
(`lib/core/TikiFilter.php:41-48`). Ladder, tightest first:

`int` / `digits` → `alpha` / `alnum` / `alnumdash` → `word` → semantic
(`pagename`, `username`, `groupname`, `email`, `url`, `text`, `date`) →
`striptags` → `purifier`/`html` → `xss` → `wikicontent` / `none` / `raw`.

- Prefer a **semantic** name (`pagename`, `username`, `email`, `url`) over a
  generic one (`alpha`, `digits`) — it documents intent and is future-proof even
  where several currently alias to `striptags` (`TikiFilter.php:161-176`).
- `xss`, `none`, `raw`, `wikicontent` permit markup — last resort only. Use
  `wikicontent` for actual wiki-source fields (it defers filtering inside plugins).
- An **unknown filter name does not fail loudly**: it triggers an `E_USER_WARNING`
  and silently falls back to `xss` (`TikiFilter.php:208-210`). Spell filter names
  exactly.

## Gotcha — `staticKeyFilters` silently does NOTHING to array values

`staticKeyFilters` runs the filter on the value as-is; a Laminas scalar filter
handed an array returns it unchanged, so `?categoryId[]=<script>` sails through
untouched. Array values need `staticKeyFiltersForArrays`, which sets the rule to
recurse into elements (`applyOnElements()`, `lib/core/DeclFilter/FilterRule.php:24-38`).
No error is raised either way — the difference is invisible until exploited.

```php
'staticKeyFilters'          => ['categoryId' => 'digits'],  // NO-OP on categoryId[]
'staticKeyFiltersForArrays' => ['categoryId' => 'digits'],  // correct for arrays
```

## JIT filtering — when the type is only known at runtime

Use JIT when the correct filter depends on runtime state (tracker field type,
wiki-vs-HTML content, a `type`/`action` switch). Four globals are created in
`tiki-setup_base.php:709-716`, each defaulting to the `xss` filter:
`$jitGet`, `$jitPost`, `$jitCookie`, `$jitRequest`. In a service Controller the
JitFilter instance arrives as the `$input` parameter (see tiki-services).

Object mode (per key — preferred, `lib/core/JitFilter.php:223`):

```php
$page    = $jitRequest->page->pagename();       // lib/core/Services/Edit/PluginController.php:370
$content = $jitRequest->edit->wikicontent();    // lib/setup/editmode.php:77
$mode    = $jitRequest->mode->text();           // tiki-iot_dashboard.php:25
$categs  = $jitRequest->asArray('categoryId', ',');  // array values → filtered array
```

Array mode (declare filters once, then read — `JitFilter::replaceFilters`,
`lib/core/JitFilter.php:171`). Real use: `lib/core/Services/Tracker/Controller.php:403`:

```php
$input->replaceFilters([
    'visible_by'  => 'groupname',
    'editable_by' => 'groupname',
]);
$visibleBy = $input->asArray('visible_by', false);   // recommended array accessor
$editableBy = $input->asArray('editable_by', false);
```

`replaceFilter($key, $filter)` swaps a single key's filter; you may re-request the
same key with a different filter safely. Always read array-valued keys with
`asArray(...)`, not `[$key]`.

## Related skills

- **tiki-page-bootstrap** — where the `$inputConfiguration` block sits in the page.
- **tiki-wiki-plugins** — plugin `_info()` declares per-param `filter`/`separator`;
  undeclared params are auto-stripped. Same TikiFilter names apply.
- **tiki-services** — the `$input` JitFilter passed to service controllers.
- **tiki-security** — filtering is input-side; still `|escape` on output.

## Standards

Tiki ≥23 → PSR-12 (4 spaces); ≤22 → Zend/tabs. Match the file; never reindent
lines you didn't touch. LF, UTF-8, lowercase filenames.

## Verify against source

```bash
grep -n inputConfiguration tiki-admin_polls.php        # real full block
grep -rl staticKeyFiltersForArrays tiki-*.php          # array-value pages (~60)
grep -rn 'replaceFilters\|->asArray\|->pagename()' lib/core/Services  # JIT usage
```

Filter names live in `lib/core/TikiFilter.php` (the `switch`); the rule engine
and each rule class are under `lib/core/DeclFilter.php` + `lib/core/DeclFilter/`.
