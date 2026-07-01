---
name: tiki-security
description: Use when escaping template output, adding CSRF ticket checks to state-changing actions, guarding include-only files against direct HTTP access, creating a new directory, or making any security-sensitive Tiki change. Covers context-correct escaping, the checkCsrf ticket flow, direct-access die-guards, and WYSIWYCA server-plus-display gating.
---

# Tiki security primitives

Every commit is security-reviewed against Tiki's Secure Coding Practice Guidelines.
The five risks: SQL injection, XSS, missing access control, CSRF, vulnerable bundled
libs. This skill covers XSS output escaping, CSRF, and direct-access guards. Input
filtering is **tiki-input-filtering**; feature/permission checks are **tiki-access-control**.

## XSS — escape ALL output, context-correct

The escape modifier is `lib/smarty_tiki/Modifier/Escape.php` (called via
`{$v|escape}` in templates, `smarty_modifier_escape($v, 'javascript')` in PHP). The
argument picks the escaping context — **wrong context = still exploitable**:

| Output context | Use | What it does |
|---|---|---|
| HTML body / text | `{$v|escape}` or `{$v|escape:'html'}` | `htmlspecialchars`, ENT_QUOTES (Escape.php:32) |
| HTML attribute value | `{$v|escape:'attr'}` | Laminas `escapeHtmlAttr` (Escape.php:113) |
| Inside `<script>` string literal | `{$v|escape:'javascript'}` | escapes `' " \ \r \n </` only (Escape.php:88) |
| Inside a URL / query value | `{$v|escape:'url'}` | `rawurlencode` (Escape.php:56) |
| URL keeping path slashes | `{$v|escape:'urlpathinfo'}` | rawurlencode but keeps `/` (Escape.php:59) |

Full esc_type list (Escape.php): `html htmlall url urlpathinfo quotes hex hexentity
decentity javascript mail nonstd unescape attr`.

**Gotcha — `'javascript'` is NOT html-escaping.** It only backslash-escapes quotes,
newlines and `</`. It is *only* safe inside a JS string literal in a `<script>` block.
Conversely, plain `{$v|escape}` (html) placed inside a `<script>` is exploitable — the
HTML parser doesn't run there, so `htmlspecialchars` is the wrong tool. Match the tool
to the parser that will read the value.

**Gotcha — an unknown/misspelled esc_type silently returns the string UNESCAPED**
(`default` case, Escape.php:117). There is no `'css'` type; `escape:'htlm'` (typo) =
zero escaping = XSS. Only ever pass a type from the list above.

**Gotcha — build the URL-escaped value first, then place it.** For
`tiki-editpage.php?page=<X>` use `{$page|escape:'url'}`, not `|escape` — html-escaping a
URL param leaves `&`/`=`/`?` un-neutralized in the URL grammar.

## CSRF — ticket-check every state-changing action

State-changing requests (writes, deletes, admin toggles) must pass a CSRF ticket check
**server-side** before acting. The API is `$access->checkCsrf()`
(`lib/tikiaccesslib.php:446`). It validates a per-session ticket + request origin and
returns `true` only when the check passes.

Real pattern (`tiki-admin_modules.php:101,168,430`):

```php
// simple action: guard the branch with checkCsrf()
if (isset($_REQUEST['clear_cache']) && $access->checkCsrf()) {
    // perform the state change
}

// destructive action needing an explicit confirmation page: pass postConfirm = true
if (isset($_REQUEST['um_remove']) && $access->checkCsrf(true)) {
    // performed only after the user submits the confirm form
}
```

With `checkCsrf(true)`, if the confirm form hasn't been submitted yet, Tiki renders
`confirm.tpl` and `die()`s (`tikiaccesslib.php:502-510`, `confirmRedirect` at :850); the
action runs only on the confirming POST.

**The token in the form comes from the `{ticket}` Smarty function**
(`lib/smarty_tiki/FunctionHandler/Ticket.php`):

- `{ticket}` — emits `<input type="hidden" name="ticket" value="…">`; put it in every
  state-changing `<form method="post">`.
- `{ticket mode='confirm'}` — additionally emits `<input type="hidden" name="confirmForm"
  value="y">`; this is what a `checkCsrf(true)` confirm form uses
  (`templates/confirm.tpl:28`).
- `{ticket mode='get'}` — returns the raw token only, for JS/link-based confirms.

**Gotcha — the confirm POST key is `confirmForm` (value `'y'`), not `confirm`.** Do not
invent your own confirm key; `checkCsrf` reads `$_POST['confirmForm']` internally
(`tikiaccesslib.php:504,852`).

**Gotcha — there is no `key_check()` / `key_get()` in this codebase.** Older Tiki docs
show a `key_check($area)` / `key_get($area)` pair; that API is gone. Use
`$access->checkCsrf()`. (`check_authenticity()` at `tikiaccesslib.php:920` also exists but
is `@deprecated` — do not use it in new code.)

**Superglobals — avoid `$_REQUEST`.** Read `$_POST` for state changes and `$_GET` for
reads specifically; only use `$_REQUEST` when GET and POST are genuinely both expected
(Secure-Coding-Practice-Guidelines.md). Note: some legacy pages still branch on
`$_REQUEST` — prefer `$_POST` in new code.

## Direct-file-access guard for include-only files

A file meant only to be `include`d (never hit directly over HTTP) needs a guard at the
top, right after the copyright header. Verified pattern (`categorize.php:12`):

```php
if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    die('This script may only be included.');
}
```

CLI-only scripts instead reject web requests:

```php
if (isset($_SERVER['REQUEST_METHOD'])) {
    die('Only available through command-line.');
}
```

**Naming convention:** prefix include-only files `include_*.php` (e.g.
`admin/include_features.php`), not `*.php.inc` — keeps directories readable while
signalling intent.

**Gotcha — `.htaccess` is NOT a substitute for the PHP guard.** `.htaccess` only blocks a
*browser* from fetching the file; PHP `include()` still works, and not every deploy honors
`.htaccess` (nginx, misconfig). The `die()` guard is the real protection; `.htaccess` is
defense-in-depth on top.

## New directory → blank `index.php`

Every new directory gets an empty `<dir>/index.php` to stop directory
listing. Root-level dirs additionally get an `.htaccess` whitelisting only the file types
that should be web-served (see the images-only example in
Secure-Coding-Practice-Guidelines.md). Non-negotiable, same rule as tiki-page-bootstrap.

## WYSIWYCA — display gating AND server checks, never one alone

**W**hat **Y**ou **S**ee **I**s **W**hat **Y**ou **C**an **A**ccess: don't render UI
(buttons, menu items, modules, admin panels) for actions the user can't perform. Gate the
template on the permission var:

```smarty
{if $tiki_p_edit eq 'y'}
    <a href="tiki-editpage.php?page={$page|escape:'url'}">{tr}Edit{/tr}</a>
{/if}
```

**Gotcha — hiding the button is UX, not security.** Display gating does **not** replace the
server-side `$access->check_feature(...)` / `check_permission(...)` / `Perms::get()->…`
check in the handler. An attacker posts the request regardless of what's rendered. Both
layers are mandatory: render only what the user can do, AND re-check on the server before
doing it. Permission API details live in **tiki-access-control**.

## Cross-cutting reflexes

For any security-sensitive change, confirm all of:

- Input filtered — `$inputConfiguration` before `tiki-setup.php` (**tiki-input-filtering**).
- Access checked — feature + permission at handler top (**tiki-access-control**).
- Output escaped — correct esc_type for every `{$v}` reaching a template.
- CSRF — `$access->checkCsrf()` guards every write/delete/toggle branch; forms carry `{ticket}`.
- SQL — parameterized queries, backticked identifiers (**tiki-database**).
- New include-only file guarded; new directory has `index.php`.
- i18n strings never interpolate user data (**tiki-i18n**) — also avoids injection into messages.

Service/AJAX handlers use `$this->checkCsrf()` from
`lib/core/Services/Utilities.php:106` instead of the page-level `$access` call — see
**tiki-services**.

## Verify against source

```bash
grep -rn "checkCsrf" tiki-admin_modules.php          # real CSRF branch guards
grep -n "case '" lib/smarty_tiki/Modifier/Escape.php  # supported escape contexts
grep -n "basename(__FILE__)" categorize.php           # real direct-access guard
```

Models: `tiki-admin_modules.php` (checkCsrf branches, incl. `checkCsrf(true)`),
`lib/smarty_tiki/Modifier/Escape.php` (escape contexts + the unsafe `default`),
`categorize.php` (direct-access die-guard), `templates/confirm.tpl` +
`lib/smarty_tiki/FunctionHandler/Ticket.php` (ticket/confirm form).
