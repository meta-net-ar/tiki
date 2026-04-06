# themes/

This directory is processed at runtime by Tiki\Lib\Theme\ThemeLib and contains:

* default/, which is the tiki default theme
* nameofthetheme/ subdirectories,  all tiki themes distributed with tiki, as well as any user created custom ones

* base_files/ which is not really a theme, more a collection of base stylesheet and icons used in tiki.
* [templates/](templates/README.md), override templates
* [css/](css/README.md), fallback custom css
* [js/](js/README.md), additional custom javascript

To build the sccs files, see documentation at [src/scss/](../src/scss/README.md)

## DESCRIPTION OF THEME SUBDIRECTORIES

To make a new theme (for example, a theme called "abc"), add a new directory abc with the following layout:

These are the supported theme sub-directories and their contents:

* css/: Contains the theme's .css files (manually editable or compiled from SCSS files).  There may be more than one css file here, but there must be one named abc.css (possibly compiled from scss/abc.css)
* css/abc.css The theme will be available in the list of themes once a .css file with the parent folder's name.  You can then select it at http://example.org/tiki-admin.php?page=look
* css/custom.css  This file will be included in tiki if present.  The previous file is still mandatory.  The point of this is (I presume benoitg - 2023-04-04) is to have custom css that is not generated from scss, possibly copied from a javascript library or something similar.
* favicons/: where you can place your theme-specific favicons (check the Tiki favicon feature)
* fonts/: contains theme-specific custom fonts (For fonts that are stored locally rather than imported via CSS)
* icons/: contains theme-specific custom icons (For a custom icon font set, as an option to the Font Awesome icon set that is bundled with Tiki)
* images/: contains theme-specific images (primarily background images but could also be logos, etc)
* jquery-ui/: contains theme-specific jquery scripts
* js/custom.js: contains theme-specific JavaScript.  Will be included by lib/setup/javascript.php
* less/: (Tiki 13 to 18) contains Less files to be compiled to create the theme CSS file
* layouts:  Allows a theme to define custom layouts for prefs site_layout and site_layout_admin.  See <https://gitlab.com/tikiwiki/tiki/-/commit/4f41519a14ad9e268618dd28ca111684efed8cb5>
* options/: contains "child" themes that are variants of the main theme (for example, check the FiveAlive theme)
* scss/: (Tiki 19+) contains SCSS files to be compiled to create the theme CSS file.  Best practice is to create the theme stylesheet by compiling SCSS files, which go in this directory.  Since tiki 27, only scss partials (files starting with a _) go in the scss directory.  Files that output css files go in the css directory.  This is because of a limitation of recursive compilation in dart CSS:  there are no wildcards, so it's the only way for generated files to end up in the css directory.  We didn't want to change the final theme folder structure to maintain backward compatibility with the tiki theme system.
* templates/: contains theme-specific variants of the default Smarty template (.tpl) files which override same-name equivalents in tiki's templates/

## TIKI THEMES

A number of visual themes (also known as skins or templates) are distributed with Tiki but more are available, and you can
create an original theme or adapt an existing theme made for plain HTML or for another platform such as WordPress (respecting licence restrictions) for your own purpose.
The bundled themes can serve as models of how themes work in Tiki.

Tiki's themes (stylesheets and associated files) are in the "themes" directory in the root directory of the Tiki installation.
If a new theme has only a stylesheet (CSS file), then only a "css" sub-directory is needed in the theme's directory.
The path to the theme stylesheet is, for example, "themes/mynewtheme/css/mynewtheme.css".
Only the directories that actually contain content are necessary. For example, if your theme uses custom fonts that are locally stored,
there needs to be a "fonts" directory within the new theme's directory to contain these fonts.

Please refer to the "Default" theme (themes/default/) or the "FiveAlive" theme (themes/fivealive/) folders to see the standard file system
organization and content for themes.

More information about themes can be found at https://themes.tiki.org .

More themes can be found at https://themes.tiki.org/Marketplace Themes . Currently these are available for Tiki 18 and before, but updates are coming.

Help with Tiki is available at: https://tiki.org/Help .

A Tiki consultant (providing paid services) can be found and contacted at: https://tiki.org/Consultants .

Please note that Tiki also has a theme customizer that worked for Bootstrap 3 (Up to Tiki18) but it wasn't ported to Bootstrap 4/5. See https://doc.tiki.org/Theme-Customizer .

## FOR DEVELOPERS

### Documentation

Web developers/designers with some knowledge of CSS can create or adapt a visual theme for Tiki. Please see the links below for more details and help:

* https://themes.tiki.org/How-To-Add-a-New-Theme
* https://themes.tiki.org/Creating-a-Tiki-theme
* https://themes.tiki.org/Theme-making-Questions-and-Answers
* https://themes.tiki.org/Icons
* https://themes.tiki.org/Template-Tricks
* https://doc.tiki.org/Favicon

### TODO

1. Our themes extensively use the rgba() function in a way that is problematic.  It causes "is no a color error" if we try to re-process the color.  The following stackoverflow explains the issue well
<https://stackoverflow.com/a/76862918> (it's been fixed in bootstrap since, but we have the same issue)
