---
name: tiki-access-control
description: Use when gating a Tiki feature by permission — checking whether a user may view/edit/admin something, filtering a list of objects by permission, adding a new tiki_p_* permission, or deciding between display-gating and a server-side check. Covers $access->check_feature/check_permission, the Perms::get()/Perms::filter() API, prefix rules, and the Registered/Anonymous/global-edit gotchas.
---

# Tiki access control — feature + permission gating

Two independent gates protect every feature: **is the feature turned on?**
(`$prefs`) and **may this user do it?** (permissions). Both are checked at the
top of a page via `$access` (the `tikiaccesslib` instance, available after
`tiki-setup.php`). Paths below are relative to the Tiki source-tree root.

- Access lib: `lib/tikiaccesslib.php`
- Perms API: `lib/core/Perms.php`, `lib/core/Perms/Accessor.php`
- Global instance + prefix wiring: `lib/setup/perms.php`, `lib/core/Perms/Builder.php`

## The top-of-page gate (feature, then permission)

Placed right after `require_once('tiki-setup.php')`, before any logic
(ordering is owned by **tiki-page-bootstrap**). Real: `tiki-admin_banning.php:56`.

```php
$access->check_feature('feature_banning');       // pref key — MUST keep the feature_ prefix
$access->check_permission('tiki_p_admin_banning'); // permission — tiki_p_ prefix optional (see below)
```

Both are hard gates: a failing `check_feature` shows the "Required features"
error; a failing `check_permission` sends a 403 and stashes the URL for a
post-login bounce (`lib/tikiaccesslib.php:110`, `:204`).

- **`check_feature($features, $name='', $panel='features', $either=false)`** —
  `$features` may be an array; pass `$either=true` for "any one enabled".
  Real "any of": `tiki-admin_content_templates.php:45`.
- **`check_permission($perms, $name='', $objectType=false, $objectId=false)`** —
  array of perms = **all** required. For an object-scoped check pass type+id:
  `check_permission('edit_content_templates', 'Edit template', 'template', $templateId)`
  (`tiki-admin_content_templates.php:135`).
- **`check_permission_either([...])`** — any ONE of the listed perms suffices
  (`tiki-admin_calendars.php:68`). Admin perms auto-inherit the perms they
  administer, so you need not list them.

## Permission names — `tiki_p_` prefix

All permissions carry a `tiki_p_` prefix in the DB (`tiki_p_view`,
`tiki_p_edit`, `tiki_p_admin_calendar`). In the `Perms` accessor you **drop the
prefix**: `$perms->view`, `$perms->edit`, `$perms->admin_calendar`. The accessor
sanitizes either form (`lib/core/Perms/Accessor.php:121` strips the
configured prefix, default `tiki_p_` from `lib/core/Perms/Builder.php:9`), so
`check_permission('tiki_p_admin')` and `check_permission('admin')` both work —
but `$perms->tiki_p_edit` and `$perms->edit` are the same thing.

## Checking permissions directly with Perms::get()

`Perms::get()` returns an accessor; read the permission as a property.
Signature: `Perms::get($context = [], $object = null, $parentId = null)`
(`lib/core/Perms.php:117`).

```php
$global = Perms::get();                 // global scope
if ($global->edit_article) { ... }

$perms = Perms::get(['type' => 'wiki page', 'object' => 'HomePage']); // object scope
if ($perms->edit) { ... }
```

Object types include `wiki page`, `tracker`, `blog`, `forum`, `file gallery`,
`calendar`, `comments`, `category`, `group`, `trackeritem`. Real object-gated
page: `tiki-download_item_attachment.php:30` (`Perms::get(['type' =>
'trackeritem', 'object' => $info['itemId']])`, then `$itemPerms->view_trackers`).

## Lists — Perms::filter(), never a per-row loop

Checking N objects with N calls to `Perms::get()` is the classic N+1 (old code
issued ~90 queries for 30 objects). `Perms::filter()` bulk-loads and returns the
allowed subset in ~3 queries total. Signature:
`Perms::filter(array $baseContext, $bulkKey, array $data, array $contextMap, $permission)`
(`lib/core/Perms.php:222`).

```php
$pages    = $tikilib->list_pages();
$filtered = Perms::filter(
    ['type' => 'wiki page'],       // baseContext — common to every row
    'object',                       // bulkKey — the context key each row supplies
    $pages,                         // data — the records
    ['object' => 'pageName'],       // contextMap — context key => field in each record
    'view'                          // permission to require
);
```

Real usages: `lib/tikilib.php:4012`, `lib/userslib.php:2896`. Shortcut for
the flat case: `Perms::simpleFilter($type, $key, $permission, $data)`.

**Gotcha — N+1.** If you catch yourself writing `foreach ($rows as $r) { if
(Perms::get([...$r...])->view) ... }`, replace it with `Perms::filter()`. Same
result, 3 queries instead of N.

## Display-gating vs. the server-side check (WYSIWYCA)

WYSIWYCA — "What You See Is What You Can Access": never render UI for an action
the user cannot perform. Templates gate on the globalized `tiki_p_*` Smarty vars
that `Perms` publishes (`lib/core/Perms/Accessor.php:106` `globalize()`):

```smarty
{if $tiki_p_edit eq 'y'}
  <a href="tiki-editpage.php?page={$page|escape:'url'}">{tr}Edit{/tr}</a>
{/if}
```

**Gotcha — hiding the button is not enforcement.** Display-gating is UX only; a
crafted request still hits the handler. Every state-changing action must
re-check the permission server-side (`check_permission` / `Perms::get()`) *and*
validate a CSRF ticket. CSRF/XSS enforcement is owned by **tiki-security** — do
the perm check here, the ticket check there.

## Gotchas that bite

- **Registered does NOT inherit Anonymous.** The three hard-coded groups
  (Anonymous, Registered, Admins) are independent; a permission granted to
  Anonymous is *not* automatically available to Registered. Grant it explicitly
  to each group that needs it. (This is by design — e.g. it lets a login module
  show only to Anonymous.) See `Permission-inheritance.md`.
- **`tiki_p_edit` must be granted GLOBALLY to allow page creation.** Category-
  level `edit` lets a user edit existing pages in that category but does **not**
  let them create new pages; page creation keys off the global grant.
- **Don't mix the new API with old `$tiki_p_*` globals — silent overwrite.**
  Reading an object accessor calls `globalize()`, which writes
  `$GLOBALS['tiki_p_'.$perm]` and reassigns the `tiki_p_*` Smarty vars to the
  *object* scope (`lib/core/Perms/Accessor.php:113`). Legacy code that later
  reads `$tiki_p_edit` gets the object value, not the global one, with no error.
  Use the accessor return value (`$perms->edit`) directly; if you must override
  the globals for legacy templates, do it deliberately via
  `TikiLib::get_perm_object()` (`lib/tikilib.php:4182`), never by hand.

## Adding a new permission

Permissions are DB-driven rows in `users_permissions`; adding one is a schema
change — do it in **both** `db/tiki.sql` and a patch in
`installer/schema/` (owned by **tiki-schema-upgrade**).

```sql
INSERT INTO `users_permissions`
    (`permName`, `permDesc`, `level`, `type`, `admin`, `feature_check`)
VALUES
    ('tiki_p_wiki_view_source', 'Can view source of wiki pages',
     'basic', 'wiki', NULL, 'feature_source');
```

`level` = admin-panel grouping (`basic`/`registered`/`editors`/`admin`);
`type` = feature section; `admin`='y' grants all same-`type` perms;
`feature_check` = pref(s) that must be on for the perm to appear. Resolution
lives in `lib/userslib.php` (`get_raw_permissions()`, `:3931`).

## Standards

- PHP style: Tiki ≥23 → PSR-12 (4 spaces); ≤22 → Zend with **tabs**. Match
  surrounding files.
- LF endings, UTF-8, lowercase filenames. Don't reindent lines you didn't touch.

## Verify against source

```bash
grep -rn "Perms::get\|Perms::filter" lib | head
grep -rn "check_feature\|check_permission" tiki-*.php | head
```

Models: `tiki-admin_banning.php` (feature+perm gate),
`tiki-download_item_attachment.php` (object-scoped `Perms::get`),
`lib/userslib.php:2896` (`Perms::filter`).
