---
name: tiki-i18n
description: Use when adding or editing any user-facing text — labels, buttons, messages, plugin names/descriptions, JS alerts — in PHP, Smarty templates, or JavaScript, or when wiring strings into the translation catalogs. Covers tr()/tra(), the {tr} block with _0/_1 placeholders, JS tr(), capitalization/punctuation conventions, contextual translations, and the never-embed-a-variable rule.
---

# Tiki i18n — translatable strings

Every user-visible string must go through the translation system (Tiki ships 57
languages under `lang/`). Wrap literal English text; never build sentences
from concatenated pieces or interpolated variables. Paths below are relative to
the Tiki source-tree root.

## PHP: `tra()` vs `tr()`

Both live in `lib/init/tra.php` and translate to the current language.

- **`tra($content)`** (`lib/init/tra.php:36`) — the base translator. Use for a
  plain static string with no substitution:
  ```php
  $text = tra('Copy Tracker Item');       // lib/wiki-plugins/wikiplugin_trackeritemcopy.php:10
  $smarty->assign('message', $text);
  ```
  Full signature `tra($content, $lg = null, $unused = false, array $args = [], $escape = false)`
  — pass a target language as arg 2, or a substitution array as arg 4, only when needed.

- **`tr($content, ...$args)`** (`lib/init/tra.php:20`) — variadic convenience
  wrapper; extra positional args fill `%0`, `%1`, … inline. Use it **whenever you
  have placeholders**:
  ```php
  $text = tr('There are %0 post(s) in this forum. The last was by %1', $count, $lastAuthor);
  ```
  Internally it just calls `tra($content, '', false, array_slice($args, 1))`.

Rule of thumb: `tra('...')` for a bare static string; `tr('...', $a, $b)` when
substituting. (Both appear for static strings in the wild — e.g. plugin `_info()`
labels use `tra()` — but prefer `tr()` the moment a `%0` is involved.)

## Smarty: the `{tr}` block

Wrap literal text in `{tr}…{/tr}`:
```smarty
{tr}Hello world{/tr}
{tr}Show diff{/tr}                          {* templates/tiki-admin_sync.tpl:17 *}
```

Placeholders use `_N` params mapped to `%N` (handler
`lib/smarty_tiki/BlockHandler/Tr.php:47`):
```smarty
{tr _0=$count _1=$author}There are %0 post(s) by %1{/tr}
{tr _0=$viewmode|escape}Previous %0{/tr}    {* templates/tiki-calendar_nav.tpl:20 *}
```
Optional params: `lang="fr"` forces a language, `noparse="y"` wiki-escapes output.
Most `{tr}` calls are resolved at compile time by the prefilter
(`lib/smarty_tiki/Filter/Pre/Tr.php`); only dynamic ones reach the block handler.

**Attributes — always double-quote** text containing `{tr}` (single quotes collide
with apostrophes in translations):
```smarty
alt="{tr}Click on icon to start monitoring this page{/tr}"
```

## JavaScript

`tr(str)` is defined in `lib/tiki-js.js:12`. It is a **single-argument
lookup** in the global `lang` object loaded from `lang/xx/language.js`; it returns
`str` unchanged when there is no translation. It does **not** substitute
placeholders — do that yourself:
```js
$(this).tikiModal(tr("Saving..."));                          // lib/jquery_tiki/inline_edit.js:45
message: tr("Are you sure you want to delete the event %0?").replace("%0", event_name);
```
Then add the English source string by hand to each `lang/xx/language.js`
(`lang = { "Saving..." : "Enregistrement..." }`). Auto-collection of JS strings via
`get_strings.php` is planned but not yet active — JS catalog upkeep is manual.

## Capitalization & punctuation (from Strings-Format-Convention)

| Location | Rule | Example |
|---|---|---|
| Feature names / menus | Title case | `{tr}Image Gallery{/tr}` |
| Action requests | Sentence case | `{tr}Submit a new link{/tr}` |
| Column headers | Sentence case | `{tr}Last author{/tr}` |
| Input labels | Sentence case + colon **inside** | `{tr}Upload picture:{/tr}` |
| Page titles | Title case (significant words) | `{tr}Messages and Strings Format{/tr}` |
| Button / action labels | Title case | `{tr}Create a New Structure{/tr}` |

**Punctuation goes inside `{tr}`.** Tiki strips a trailing `: ! ; . , ?` and
translates the base word and the punctuation separately, so `{tr}Login{/tr}` and
`{tr}Login:{/tr}` share one translation, and languages that need `" :"` (French)
still get it (see the header comment in `lang/fr/language.php`). Never move the
colon outside the block.

General: complete sentences, no slang/abbreviations, use `email` not `e-mail`, pick
one of `Create/Edit` vs `Create/edit` and stick to it, spellcheck before commit
(a spelling fix forces retranslation across all languages).

## Contextual translations

Same English word, different translation by context: append `_C(context)` to the
source string.
```smarty
{tr}Off_C(on-off){/tr}          {* templates/tiki-editpage.tpl:638 *}
{tr}Finish_C(imperative){/tr}   {* templates/wizard/wizard_bar_user.tpl:15 *}
```
The English catalog maps the suffixed key back to the bare word
(`"Off_C(on-off)" => "Off"`, `lang/en/language.php:20400`); other languages
supply a context-specific translation.

## Catalogs & extraction

- **`lang/xx/language.php`** — PHP + Smarty strings:
  `$lang = array("English source" => "Translation", …)`. The English source is the
  case-sensitive key. `lang/en/language.php` is the master list — check it
  before inventing a new variant of an existing phrase.
- **`lang/xx/language.js`** — JS strings: `lang = { "English" : "Translation" }`.
- **Extract:** `php get_strings.php` (`get_strings.php`) scans PHP/Smarty for
  `tr()`/`tra()`/`{tr}` and updates the catalogs.
- **Translation server:** https://i18n.tiki.org

## Gotchas

- **Never embed a variable in a string.** `{tr}{$page}{/tr}` and `tr($page)` are
  wrong — the value is not in the catalog and there is nothing to translate. Use a
  placeholder: `{tr _0=$page}There are no pages similar to '%0'{/tr}`.
- **Never concatenate translated fragments.** `tra('Format: from') . '|' . tra('to')`
  breaks languages with different word order. Keep the whole sentence in one call:
  `tra('Format: from|to|template')`.
- **Escape interpolated placeholder values**, not the literal: `{tr _0=$name|escape}Hi %0{/tr}`.
  The English text between the tags is author-controlled and needs no `|escape`.
- **Single quotes break on apostrophes** — use double quotes for any attribute
  holding `{tr}`.

## Cross-cutting reflexes

- Output escaping and general Smarty patterns live in **tiki-smarty-ui**; the XSS
  rationale for `|escape` and the quote convention are in **tiki-security**.
- Wiki-plugin `_info()` `name`/`description`/param labels must be wrapped in
  `tra()`/`tr()` — see **tiki-wiki-plugins**.
- Where PHP/template/JS code belongs (and thus where these strings live) is in
  **tiki-page-bootstrap**.

## Verify against source

```bash
grep -rn "tr('\|tra('" lib/wiki-plugins | head    # PHP usage
grep -rn "{tr _0=" templates | head                # placeholder templates
sed -n '20,48p' lib/init/tra.php                   # tr() vs tra() definitions
```

## Gotchas

- **No `_tr` function exists.** Older material sometimes shows PHP substitution as
  `_tr("Changed from %0 to %1", $old, $new)`, but `grep "function _tr"` finds
  nothing. The real function is `tr()` (`lib/init/tra.php:20`). Use `tr()`.
- **A bare variable in a `{tr}` block is a legacy idiom, not a model.** For
  developer-authored UI text, `{tr}{$var}{/tr}` is wrong — always use a `%0`
  placeholder. Yet `templates/tracker_input_field.tpl:5,9,12` do exactly
  `{tr}{$field_name}{/tr}` / `{tr}{$description}{/tr}` to translate DB-stored
  labels; that bare-variable form exists only for dynamic catalog values, not
  text to copy.
