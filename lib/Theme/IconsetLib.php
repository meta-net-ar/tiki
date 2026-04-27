<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Theme;

use TikiLib;

class IconsetLib
{
    /**
     * @param $theme
     * @param $theme_option
     * @return Iconset
     * @throws Exception
     */
    public static function getIconsetForTheme($theme, $theme_option): Iconset
    {
        global $prefs;
        $themelib = TikiLib::lib('theme');

        // start with the default base and merge in others which will generally be less complete
        $iconset = new Iconset(self::loadFile('themes/base_files/iconsets/default.php'));

        //override the default icons with theme specific icons or with site icon set setting
        if (isset($prefs['theme_iconset']) && $prefs['theme_iconset'] === 'theme_specific_iconset') {
            $filename = $themelib->get_theme_path($theme, '', str_replace('-', '_', $theme) . '.php');
            if ($filename) {
                $iconset1 = new Iconset(self::loadFile($filename));
                $iconset->merge($iconset1);
            }
            $filename = $themelib->get_theme_path($theme, $theme_option, str_replace('-', '_', $theme_option) . '.php');
            if ($filename) {
                $iconset1 = new Iconset(self::loadFile($filename));
                $iconset->merge($iconset1);
            }
        } elseif (isset($prefs['theme_iconset']) && $prefs['theme_iconset'] !== 'default') {
            $filename = "themes/base_files/iconsets/{$prefs['theme_iconset']}.php";
            $iconset1 = new Iconset(self::loadFile($filename));
            $iconset->merge($iconset1);
        }

        //when a theme option is used, first override with the main theme's custom icons
        if (! empty($theme_option)) {
            $filename = $themelib->get_theme_path($theme, '', str_replace('-', '_', $theme) . '_custom.php', 'icons/');
            if ($filename) {
                $iconset1 = new Iconset(self::loadFile($filename));
                $iconset->merge($iconset1);
            }
        }

        //finally override with custom icons of the displayed theme
        $filename = $themelib->get_theme_path($theme, $theme_option, str_replace('-', '_', $theme_option) . '_custom.php', 'icons/');
        if ($filename) {
            $iconset1 = new Iconset(self::loadFile($filename));
            $iconset->merge($iconset1);
        }

        return $iconset;
    }

    /**
     * @param $filename
     * @return array
     */
    public static function loadFile($filename)
    {
        $data = [];
        if (is_readable($filename)) {
            include_once($filename);
            $function = 'iconset_' . str_replace('.php', '', basename($filename));
            if (function_exists($function)) {
                $data = $function();
            }
        }
        return $data;
    }
}
