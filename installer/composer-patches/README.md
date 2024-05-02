# composer-patches

A patch file serves as a record of alterations made to a source code, facilitating their application through patching tools. Within our workflow, we employ [cweagans/composer-patches](https://packagist.org/packages/cweagans/composer-patches) for this purpose. This mechanism proves invaluable when necessitating fixes or enhancements that haven't yet been formally incorporated into a package.

## How to Utilize

1. **Incorporate Your Patch File**: Place your patch file within this designated folder.
   
2. **Update `composer-patches.json`**:
   - Identify the targeted package for patch application.
   - Utilize the description of your patch as the corresponding key.
   - Specify the relative path to your patch file as the associated value.

## Example

```json
{
    "vendor/package": {
        "Change xyz to zyx to match format (example)": "../installer/composer-patches/vendor_package_change_xyz_to_zyx_to_match_format.patch"
    }
}
```

> **Note:** Ensure the patch file path is relative, as if the JSON data were referenced from the file [vendor_bundled/composer.json](./vendor_bundled/composer.json).

## Apply Your Patch File

Finally, to ensure that you're using your changes, execute the composer install process via `sh setup.sh`.

## List of available patches

### [jquery-sheet_fix-visibility-issues.patch](./jquery-sheet_fix-visibility-issues.patch)
__Fix visibility issues.__


We will get rid of this patch soon, after the replacement of jQuery.sheet (aka WickedGrid) with a more modern solution.

### [smartylint-php8.1-and-other-fixes.patch](./smartylint-php8.1-and-other-fixes.patch)
__Fix smartylint PHP 8.1 support and other fixes.__

SmartyLint seems to be very old not getting any updates since a period over 9 years, so, this patch is valuable. It fixes the following points:
* Ensure that the `auto_detect_line_endings` INI directive isn't set for PHP version 8.1 and greater.
* Fixes the iterator wrongly incremented in the lib.
* Fix the issue: assignement to undefined variable $iPointer.

Related: 
* https://github.com/smarty-php/smarty/discussions/951
* https://github.com/umakantp/SmartyLint/issues/3

### [smartylint-warning-curly-bracket.patch](./smartylint-warning-curly-bracket.patch)
__Fix smartylint syntax with curly braces.__

Related to the previous one, this patch replaces the usage of curly-braces syntax to access element or character at a specific index in an array or a string with the square bracket syntax.

### [text_wiki_mediawiki__func_collision.patch](./text_wiki_mediawiki__func_collision.patch)
__Renamed method to avoid collision with func from parent class__

Basically, this change renames the method `process()` to `process_emphasis()` in the  `Text_Wiki_Parse_Emphasis` class to avoid naming conflicts with the `process()` method in the parent class `Text_Wiki_Parse`. Yes, this patch is needed because the lib [pear/Text_Wiki_Mediawiki](https://github.com/pear/Text_Wiki_Mediawiki) hasn't been updated on this part, and it's less likely that it will, because its GitHub repository doesn't show any activity in almost six years.

### [text_wiki_mediawiki__php7fixes.patch](./text_wiki_mediawiki__php7fixes.patch)
__Removed deprecated syntax in PHP7.__

This patch adapts the library to run on newer PHP versions, starting from PHP 7. Specifically, it removes redundant assignments of objects to variables by reference and illegal assignments.

### [text_wiki_mediawiki__php8fixes.patch](./text_wiki_mediawiki__php8fixes.patch)
__Removed string access with curly braces syntax in PHP8.__

This patch replaces the usage of curly-braces syntax to access array elements and string characters.

### [xmpp-prebind-php__php8fixes.patch](./xmpp-prebind-php__php8fixes.patch)
__Removed string access with curly braces syntax in PHP8.__

This patch replaces the usage of curly-braces syntax to access array elements and string characters in the library [candy-chat/xmpp-prebind-php](https://github.com/candy-chat/xmpp-prebind-php).

### [nicolaskruchten_pivottable__sort_by_key_z_to_a.patch](./nicolaskruchten_pivottable__sort_by_key_z_to_a.patch)
__Sort rows and columns by key_z_to_a.__

Related:
* https://github.com/nicolaskruchten/pivottable/pull/1303
* https://gitlab.com/tikiwiki/tiki/-/merge_requests/1542

### [nicolaskruchten_pivottable_drag_and_drop_via_touch.patch](./nicolaskruchten_pivottable_drag_and_drop_via_touch.patch)
__Drag and drop via touch.__

Related: https://github.com/nicolaskruchten/pivottable/pull/1336