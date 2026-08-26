# `lib/smarty_tiki/`

Tiki's custom [Smarty](https://www.smarty.net/) functions, blocks, modifiers, compilers and filters live here. Since [Tiki 27](https://doc.tiki.org/Tiki27), everything is built on Smarty 5's [Extension](https://smarty-php.github.io/smarty/5.x/api/extending/extensions/) API, and every extension is a plain PSR-4 class that gets discovered automatically — there's no registry to edit by hand.

## Directory structure

| Directory | Namespace | What goes there |
|---|---|---|
| `FunctionHandler/` | `SmartyTiki\FunctionHandler` | `{function}` tags |
| `BlockHandler/` | `SmartyTiki\BlockHandler` | `{block}...{/block}` tags |
| `Modifier/` | `SmartyTiki\Modifier` | `\|modifier` filters |
| `Compile/Tag/` | `SmartyTiki\Compile\Tag` | compiler tags (compile-time code generation) |
| `Compile/Modifier/` | `SmartyTiki\Compile\Modifier` | compiler modifiers |
| `Filter/Pre/` | `SmartyTiki\Filter\Pre` | prefilters, run on template source before compiling |
| `Filter/Output/` | `SmartyTiki\Filter\Output` | output filters, run on rendered HTML |
| `Extension/` | `SmartyTiki\Extension` | `SmartyTikiExtension`, the single Extension Tiki registers with Smarty |
| `Traits/` | `SmartyTiki\Traits` | static facade traits (see below) |
| `Utils/` | `SmartyTiki\Utils` | small helpers used by a handful of extensions |

## How discovery works

Every extension class implements `SmartyTiki\TikiSmartyExtensionInterface`, which just requires a static `getSmartyName(): string` returning the name used in templates (e.g. `'icon'`, `'escape'`, `'self_link'`).

`Tiki\Composer\SmartyExtensionMapper` scans the directories above, finds every class implementing that interface, and writes `lib/smarty_tiki/generated_extension_map.php` — a plain array mapping Smarty names to class names. `SmartyTikiExtension` reads that file and uses it to resolve handlers at runtime. The map file is gitignored and regenerated automatically on `composer install`/`update`, or manually with:

```
php console.php smarty:generate-mapping
```

Add `--check` to verify the committed map is up to date without rewriting it (this is what CI runs).

## Adding a new function/block/modifier

Drop a class in the right directory, implement the interface, done — no wiring needed anywhere else.

**Function:**
```php
namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;
use SmartyTiki\TikiSmartyExtensionInterface;

class MyFunction extends Base implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'my_function';
    }

    public function handle($params, Template $template)
    {
        return 'whatever this should render';
    }
}
```

**Block** is the same but extends `Smarty\BlockHandler\Base` and `handle()` takes `($params, $content, Template $template, &$repeat)` — see the [block tags doc](https://smarty-php.github.io/smarty/5.x/api/extending/block-tags/).

**Modifier** doesn't extend anything — just implement the interface directly, with a `handle()` whose first argument is the value being filtered:
```php
namespace SmartyTiki\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;

class MyModifier implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'my_modifier';
    }

    public function handle($value, $someOption = null)
    {
        return strtoupper($value);
    }
}
```

Compiler tags/modifiers and filters follow the same pattern — implement the interface plus whatever Smarty asks for (`compile()` for compilers, `filter($source, $template)` for pre/output filters). Look at an existing class in the relevant directory for a concrete example; there's always at least one.

After adding a class, run `php console.php smarty:generate-mapping` so it shows up in the map.

## Calling a handler from plain PHP

Templates aren't the only caller — a lot of Tiki code calls these directly (e.g. building an icon or an escaped string outside a template). Rather than instantiating the class and juggling a Smarty template object, use the static facade traits:

- `FunctionHandlerStaticFacadeTrait` → `MyFunction::render($params, $template = null)`
- `BlockHandlerStaticFacadeTrait` → `MyBlock::render($params, $content, $template = null, $repeat = false)`
- `ModifierStaticFacadeTrait` → `MyModifier::apply(...$args)`

Add the trait to your class and these become available. If `$template` is omitted, it falls back to an empty internal template, which is fine for handlers that don't actually need template context.

## `_custom/shared/smarty/`

Site-specific extensions can live outside Tiki core entirely, under `_custom/shared/smarty/`, mirroring the same subdirectory layout (`FunctionHandler/`, `Modifier/`, etc.) under the `SmartyTikiCustom\` namespace instead of `SmartyTiki\`. See [_custom_dist/README.md](../../_custom_dist/README.md) and the sample in [_custom_dist/shared/smarty/](../../_custom_dist/shared/smarty/). Same rules apply: implement the interface, regenerate the map, and it's live.

## Smarty security preferences

Smarty 5 dropped support for using arbitrary PHP functions directly as modifiers/functions, which is why `smarty_security_modifiers`/`smarty_security_functions` are gone. In their place:

- `smarty_security_allowed_tags` / `smarty_security_disabled_tags` — allow/deny lists for function, block and filter tags.
- `smarty_security_allowed_modifiers` / `smarty_security_disabled_modifiers` — same, for modifiers.
- `smarty_security_allowed_builtin_php_functions` — the one exception: a specific whitelist of native PHP functions still allowed to be used directly as modifiers (checked in `SmartyTikiExtension::getModifierCallback()`).

Empty means no restriction. See [Smarty's security docs](https://smarty-php.github.io/smarty/5.x/api/security/) for the underlying mechanism.
