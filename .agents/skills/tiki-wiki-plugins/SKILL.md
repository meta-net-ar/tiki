---
name: tiki-wiki-plugins
description: Use when creating or editing a Tiki wiki plugin, adding new wiki syntax ({PLUGIN(...)}...{PLUGIN}), exposing a feature as an embeddable content block, or debugging why a plugin param is ignored or a plugin name fails. Covers the one-file two-function shape, the info() array, param filters, and the .tpl render option.
---

# Tiki wiki plugins

A wiki plugin extends wiki syntax with a reusable, embeddable block. Paths below
are relative to the Tiki source-tree root — the examples are real files you can open.

Invocation syntax (what an author types in a wiki page):

```
{FONT(size="14", face="serif")}body content{FONT}
{CENTER()}no-param body{CENTER}
```

## One file, two functions

A plugin is a single file `lib/wiki-plugins/wikiplugin_<name>.php` defining
exactly two functions — nothing else is registered anywhere. Minimal real example
(`lib/wiki-plugins/wikiplugin_center.php`):

```php
<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
// ... 4-line header (see tiki-page-bootstrap) ...

function wikiplugin_center_info()
{
    return [
        'name'          => tra('Center'),          // label in the plugin picker
        'documentation' => 'PluginCenter',         // doc.tiki.org page name
        'description'   => tra('Center text'),
        'prefs'         => ['wikiplugin_center'],   // preference gate (see below)
        'body'          => tra('text'),            // hint shown for the body field
        'iconname'      => 'align-center',          // icon in the picker
        'filter'        => 'wikicontent',           // filter applied to $data body
        'tags'          => ['basic'],              // basic | advanced | experimental
        'introduced'    => 1,                       // Tiki version added
        'params'        => [],
    ];
}

function wikiplugin_center($data, $params)
{
    return '<div style="text-align:center">' . trim($data) . '</div>';
}
```

- `wikiplugin_<name>_info()` — metadata + param schema for the editor and the filter engine.
- `wikiplugin_<name>($data, $params)` — `$data` is the body, `$params` an assoc array
  of already-filtered values. Return an HTML/text **string** that replaces the plugin.

### info() keys (verified against real plugins)

`name`, `documentation`, `description`, `prefs`, `body`, `iconname`, `tags`,
`introduced`, `params` (all in `wikiplugin_center.php`), plus:
- `filter` — filter applied to the **body** `$data` (`wikicontent`, `rawhtml_unsafe`,
  `text`, …). Applied at `lib/parser/parserlib.php:1199`; defaults to `xss` if omitted.
- `format` — `'html'` when the plugin returns raw HTML (`lib/wiki-plugins/wikiplugin_code.php:53`).
- `searchable_by_default` — index the body for Unified Search (`wikiplugin_code.php:44`).

### Each param, fully declared

Real param (`lib/wiki-plugins/wikiplugin_code.php`, `wikiplugin_accordion.php`):

```php
'params' => [
    'size' => [
        'required'    => false,
        'name'        => tra('Font size'),
        'description' => tra('Font size in pixels. Numbers only.'),
        'filter'      => 'digits',        // REQUIRED per param — see gotcha
        'default'     => '12',            // needed when required=false
        'since'       => '9.0',
    ],
    'align' => [
        'required'    => false,
        'name'        => tra('Alignment'),
        'description' => tra('Text alignment'),
        'filter'      => 'word',
        'default'     => 'left',
        'since'       => '25.0',
        'options'     => [                // renders a dropdown in the editor
            ['text' => tra('Left'),  'value' => 'left'],
            ['text' => tra('Right'), 'value' => 'right'],
        ],
    ],
    'items' => [
        'required'  => false,
        'name'      => tra('Items'),
        'filter'    => 'text',
        'separator' => ',',              // value arrives as an array, split on ','
    ],
],
```

Per-param minimum: `required`, `name`, `description`, `filter`, `default` (when not
required), `since`. Read values via `$params['size']` (or `extract($params, EXTR_SKIP)`).

## Render via a template (optional)

Instead of concatenating HTML, fetch a Smarty template — file goes in
`templates/wiki-plugins/wikiplugin_<name>.tpl`. Real example
(`lib/wiki-plugins/wikiplugin_accordion.php:305`):

```php
$smarty = TikiLib::lib('smarty');
$smarty->assign('unique', $unique);
$smarty->assign_by_ref('headers', $headers);
return $smarty->fetch('wiki-plugins/wikiplugin_accordion.tpl');
```

Escape output in the `.tpl` with `|escape` — the return string is trusted as-is
(see **tiki-smarty-ui** and **tiki-security**).

## Reporting a missing required param

Don't hand-roll error HTML — return a `WikiParser_PluginOutput` argument error
(`lib/wiki-plugins/wikiplugin_accordion.php:295`):

```php
if (empty($params['headers'])) {
    return WikiParser_PluginOutput::argumentError(['headers']);
}
```

## Gotchas

- **Plugin names: alphabetical only.** The doc mandates letters-only names; numbers
  cause failures. Name the file, both functions, and the `{NAME(...)}` tag identically
  (lowercase in the file/functions; the parser lowercases the tag).
- **Undeclared params are auto-stripped.** Only keys present in `params` survive to
  `$params`; anything else is dropped by `array_intersect_key`
  (`lib/parser/parserlib.php:1226-1228`). A "why is my param ignored?" bug is
  almost always a param missing from `info()`.
- **Every param needs a `filter`.** It is the per-param input gate; an omitted filter
  silently falls back to the `xss` default. Always declare one — see **tiki-input-filtering**
  for the filter catalog (`digits`, `word`, `text`, `wikicontent`, `alpha`, …).
- **Editing `info()` requires a cache clear.** The plugin registry/param schema is
  cached under `./temp/cache/`. After changing `info()`, clear it: Admin → Tiki Cache
  (or CLI `php console.php cache:clear`). Body-function logic changes need no clear.
- **The return string is re-parsed as wiki by default.** To emit final HTML that must
  NOT be re-parsed, wrap it: `return ' ' . $ret . ' ';`.

## Cross-cutting reflexes

- Every plugin must be opt-in: add a `wikiplugin_<name>` preference and list it in the
  `prefs` key — see **tiki-preferences**.
- Param `filter` values, body `filter`, and `format` — see **tiki-input-filtering**.
- Template rendering + output escaping — see **tiki-smarty-ui** and **tiki-security**.
- Where the plugin file sits in the tree and the file header — see **tiki-page-bootstrap**.
- Wrap all user-facing strings in `tra()`/`tr()` — see **tiki-i18n**.

## Verify against source

```bash
ls lib/wiki-plugins/wikiplugin_*.php | head        # every plugin
grep -n "'iconname'\|'filter'\|'format'" lib/wiki-plugins/wikiplugin_code.php
ls templates/wiki-plugins/                         # plugins that render via .tpl
```

Good models: `lib/wiki-plugins/wikiplugin_center.php` (tiny, no params),
`lib/wiki-plugins/wikiplugin_code.php` (params, `format`, dynamic options),
`lib/wiki-plugins/wikiplugin_accordion.php` (`.tpl` render, argument error).
