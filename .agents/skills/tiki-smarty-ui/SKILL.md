---
name: tiki-smarty-ui
description: Use when writing or editing a Tiki Smarty template (.tpl) — wiring inner content into the tiki.tpl chrome, styling tables, writing Bootstrap 5 markup, adding {icon} icons, or handling RTL/logical utilities. Covers the tiki.tpl/mid relationship, normal/formcolor table classes, data-bs-* + logical Bootstrap utils, semantic {icon name=...}, and the no-inline-styles / author-LTR rules.
---

# Tiki Smarty UI: chrome, tables, Bootstrap 5, icons

How to write the presentation layer. Tiki uses **Smarty**; templates live in
`templates/`. Paths below are relative to the Tiki source-tree root — every one
is a real file you can open.

## Root chrome: `tiki.tpl` + `mid`

A feature page renders through two templates:

- **`tiki.tpl`** — the root chrome (header, modules, footer). The PHP page ends
  with `$smarty->display('tiki.tpl')`.
- **`mid`** — the inner content template, named by the page:
  `$smarty->assign('mid', 'tiki-<feature>.tpl')` before the `display`.

The wiring is indirect (do not fight it): `templates/tiki.tpl` extends
`layout_view.tpl` and its content block is just `{$mid_data}`
(`templates/tiki.tpl:11-13`). The framework fetches the `mid` template into
`mid_data` for you — `lib/core/Tiki/Smarty/SmartyTiki.php:322` reads the
`mid` var, `:361-363` renders it and assigns the result to `mid_data`. So you
author `templates/tiki-<feature>.tpl`; you never touch `mid_data`.

Your `mid` template starts with the copyright header and holds only the page's
inner content — no `<html>`, `<head>`, header/footer; the chrome supplies those.
(The page-side `assign('mid',...)` / `display('tiki.tpl')` pair is the
**tiki-page-bootstrap** skill.)

## Tables

Two semantic table classes:

| Class | Use for |
|---|---|
| `normal` | listing rows (reports, item lists) |
| `formcolor` | form layouts (label/field rows) |

```smarty
<table class="table normal table-striped table-hover">
    <tr class="head"><th>{tr}Name{/tr}</th></tr>
    <tr><td>{$item.name|escape}</td></tr>
</table>
```

`<td>` helper classes carry meaning/alignment: `checkbox`, `action`, `icon`,
`id` (centered), `char`/`integer`/`floatnum` (numeric), `email`/`date`/`username`,
`norecords` (empty-result message). Unmarked `<td>` = left-aligned text.

**Gotcha — `normal` is combined, `formcolor` is standalone.** In the current source tree,
`class="normal"` never appears alone; `normal` rides alongside the Bootstrap
`.table` classes, e.g. `templates/tiki-references.tpl:34`
(`class="table normal table-striped table-hover"`). `formcolor` is still used
standalone, e.g. `templates/tiki-view_faq.tpl:71` (`<table class="formcolor">`).
Prefer `table-striped`/`table-hover` for row striping over the old manual
`{cycle values="even,odd"}` + `<tr class="{cycle}">` pattern.

## Bootstrap 5

Tiki's primary CSS/JS framework. Produce valid HTML5 and keep Bootstrap-compatible
structure. Two things bite when porting older markup:

**Data attributes are namespaced `data-bs-*`.**

```smarty
<button data-bs-toggle="modal" data-bs-target="#dlg">…</button>
```
`data-toggle` / `data-target` (no `-bs-`) is Bootstrap 4 and silently dead.

**Utilities are logical (`start`/`end`), not directional (`left`/`right`).**

| BS4 (do not use) | BS5 (use) |
|---|---|
| `.float-left` / `.float-right` | `.float-start` / `.float-end` |
| `.ml-*` / `.mr-*` | `.ms-*` / `.me-*` |
| `.pl-*` / `.pr-*` | `.ps-*` / `.pe-*` |
| `.text-left` / `.text-right` | `.text-start` / `.text-end` |
| `.sr-only` | `.visually-hidden` |
| `.close` | `.btn-close` |
| `.no-gutters` | `.g-0` |
| `.font-weight-*` | `.fw-*` |
| `.custom-select` | `.form-select` |
| `.custom-check` | `.form-check` |

Form labels need `.form-label`. Jumbotron, `.media`, `.card-deck`, and
`.input-group-append/prepend` are gone — use flex/grid utilities. Validate with
Bootlint if unsure.

## Icons — `{icon name="…"}`

Never hand-write `<img>` or `<i class="fa …">`. Use the centralized Smarty
function; it resolves theme icon sets, caches, and handles HTTPS/language.

```smarty
{icon name="save"} {tr}Save{/tr}
{icon name="trash" href="tiki-x.php?del=1" class="btn btn-danger" title="{tr}Delete{/tr}" _confirm="Are you sure?"}
{icon name="info-circle" data-bs-toggle="tooltip" title="{tr}Help{/tr}"}
```

The `name` param is a **semantic name from the icon set**, not a Font Awesome
class. Confirmed: `lib/smarty_tiki/function.icon.php` delegates to
`lib/smarty_tiki/FunctionHandler/Icon.php` — docblock `:16` ("name of icon
from themes/base_files/iconsets file"), and `:96-98` resolves it via
`$iconset->getHtml($name, $params)`. The map lives in
`themes/base_files/iconsets/default.php`, which translates each semantic name
to a real CSS class (`prepend` + `id` + `append`).

**Gotcha — semantic ≠ FA class.** `name="add"` renders `fa fa-plus-circle`
(`default.php:79-81`), `name="actions"` → `play-circle` (`:73`), `name="admin"` →
`cog` (`:76`). Many names are identity mappings (`save`→`save`, `trash`→`trash`,
`edit`→`edit`), so a raw FA name often happens to work — but author against the
semantic set so a theme can re-skin the icon centrally.

Useful params: `size` (`lg`/`2x`/`3x`), `style` (`solid`/`regular`/`light`/`brands`),
`class` (on the anchor), `iclass`/`ititle` (on the icon), `href` (clickable),
`title` (tooltip), `data-bs-*`. Legacy `_id`/`_type`/`_tag` still work but are
deprecated and bypass icon sets.

## RTL — author LTR with logical names

Write templates left-to-right only, using logical utility/property names
(`start`/`end`, `ms-`/`me-`, `ps-`/`pe-`). **RTLCSS auto-generates the RTL
counterpart** at build time.

**Gotcha — `left`/`right` breaks RTL.** A hard-coded `.ml-2`, `.text-left`, or
`float:left` won't flip for Arabic/Hebrew and produces a broken mirror layout.
There is no separate RTL template to edit; logical names are the entire RTL
strategy.

## No inline styles

Size, color, font, and layout go in **CSS classes** (Bootstrap utilities or theme
CSS) — never `style="…"`. Inline style is tolerated only for unavoidable
structural values that cannot be a class. This keeps theming and RTLCSS working.

## Cross-cutting reflexes

- **Escape every output** — `{$var|escape}`, `{$var|escape:'javascript'}` in JS
  context. Unescaped template output is the #1 XSS vector. → **tiki-security**.
- **Translate every literal** — `{tr}Save{/tr}`, and inside attributes
  `title="{tr}Delete{/tr}"`. Never bake a variable into a translatable string.
  → **tiki-i18n**.
- **Copyright header first** — every `.tpl` opens with the standard
  `{* (c) Copyright … *}` header block. Comments use `{* … *}`.
- **Format for readability, not output** — indent the template for humans;
  browser-visible whitespace does not matter. Do not reindent lines you didn't
  change (keeps diff/blame clean).
- **LF line endings, UTF-8.** Filenames lowercase.
- Wiki-plugin output templates live in `templates/wiki-plugins/` and follow
  these same rules → **tiki-wiki-plugins**.

## Verify against source

```bash
grep -rn '{icon ' templates | head                       # real icon usages (~2350)
grep -n "'<name>' =>" themes/base_files/iconsets/default.php   # semantic → FA map
grep -rn 'class="formcolor"' templates                   # standalone form tables
grep -rEn 'class="[^"]*\bnormal\b' templates             # normal + .table lists
```

Good models: `templates/tiki.tpl` (chrome/mid), `templates/tiki-view_faq.tpl`
(formcolor form), `templates/tiki-references.tpl` (normal list table).
