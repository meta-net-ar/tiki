# themes/css

If there is a custom.css file, it will be included by applyCurrentTheme() in lib/Theme/ThemeLib.php, but only if there is no custom.css file in the currently active theme.

But it's very unlikely you want to do this here, unless you don't yet have a full theme.

No versionned files should be put here, so any user files survives upgrades.
