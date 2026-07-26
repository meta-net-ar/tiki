<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Lib\Theme\ThemeLib;
use Tiki\Sections;

if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    die('This script may only be included.');
}

Sections::onSectionChange(function ($section) {
    global $prefs;
    // globals that probably shouldn't be...
    global $iconset, $admin_icons;

    // Section detection can change after tiki-setup.php has selected the
    // fallback global section. Keep only the assets for the current section.
    static $sectionThemeCssFiles = [];

    $themelib = TikiLib::lib('theme');
    $headerlib = TikiLib::lib('header');
    $smarty = TikiLib::lib('smarty');

    foreach ($sectionThemeCssFiles as $cssFile) {
        $headerlib->drop_cssfile($cssFile);
    }
    $sectionThemeCssFiles = [];

    $addSectionThemeCssFile = static function ($cssFile, $rank = 0) use ($headerlib, &$sectionThemeCssFiles) {
        if (! empty($cssFile)) {
            $headerlib->add_cssfile($cssFile, $rank);
            $sectionThemeCssFiles[] = $cssFile;
        }
    };

    list($theme_active, $theme_option_active) = ThemeLib::getActiveThemeAndOption();

    //Write back global variable and prefs so that they can be accessed elsewhere
    //This is not a great pattern, but it was like that and I didn't have time to refactor further.  At least now it's right after it's computed - benoitg - 2024-04-08
    $prefs['theme'] = $theme_active;
    $prefs['theme_option'] = $theme_option_active;

    //START loading theme related items
    //This bundle Loads bootstrap JS and popper JS

    //We now use popper elsewhere, so use the bootstrap that doesn't include it.

    /*
    bootstrap actually distributes a native ESM bundle https://getbootstrap.com/docs/5.0/getting-started/contents/#precompiled-bootstrap

    Note that assigning to the global object (window) is normally an antipattern.  ESM modules are normally imported from, not used from the global namespace.

    But older scripts may assume that it is set on the global object and it may be necessary to manually assign to the global object.  (for example it would be necessary if jquery were distributed as an ESM module).

    So this is an example of using a native ESM modules dependency and making it available in the global namespace for legacy code.

    This is necessary (As of 2023-12-11 bootstrap is used from legacy scripts: tiki-jquery.js, and several other places)
    */
    $headerlib->add_js_module('
    import * as bootstrap from "bootstrap";
    window.bootstrap = bootstrap;
');
    $headerlib->add_jsfile(NODE_PUBLIC_DIST_PATH . '/clipboard/dist/clipboard.min.js');

    if ($prefs['feature_fixed_width'] === 'y' && ! empty($prefs['layout_fixed_width'])) {
        $headerlib->add_css(
            "@media (min-width: 1200px) { .container { max-width:{$prefs['layout_fixed_width']}; } }"
        );
    }

    //2) Always add tiki_base.css. Add it first, so that it can be overriden in the custom themes
    $headerlib->add_cssfile("themes/base_files/css/tiki_base.css");

    //3) Always add bundled font-awesome css for the default icon fonts
    $headerlib->add_cssfile(FONTAWESOME_CSS_PATH . '/all.css');

    // 3a) Optionally add bundled Bootstrap-icons CSS for the optional Bootstrap icons
    if ($prefs['theme_iconset'] === 'bootstrap_icon_font') {
        $headerlib->add_cssfile(BOOTSTRAP_ICONS_FONT_PATH . '/bootstrap-icons.css');
    }

    //4) Add Addon custom css first, so it can be overridden by themes
    foreach (\Tiki\Package\ExtensionManager::getEnabledPackageExtensions() as $package) {
        $finder = new \Symfony\Component\Finder\Finder();
        foreach ($finder->in($package['path'])->path('/^css/')->name('*.css') as $file) {
            $cssFile = $package['path'] . '/' . $file->getRelativePathname();
            $headerlib->add_cssfile($cssFile);
        }
    }

    //5) Now add the theme or theme option
    if (! empty($prefs['header_custom_scss'])) {
        // TODO call compile_custom_scss() here
    } elseif ($theme_active == 'custom_url' && ! empty($prefs['theme_custom_url'])) { //custom URL, use only if file exists at the custom location
        $custom_theme = $prefs['theme_custom_url'];
        if (preg_match('/^(http(s)?:)?\/\//', $custom_theme)) { // Use external link if url begins with http://, https://, or // (auto http/https)
            $addSectionThemeCssFile($custom_theme, 'external');
        } else {
            $addSectionThemeCssFile($custom_theme);
        }
    } else {
        //first load the main theme css
        $theme_css = ThemeLib::getThemeCssFilePath($theme_active, '');
        if ($theme_css) {
            // exclude the main theme css if the option's css also includes it (pref is set)
            if ($prefs['theme_option_includes_main'] != 'y' || empty($theme_option_active)) {
                $addSectionThemeCssFile($theme_css);
            }
            //than load the theme option css file if needed
            if (! empty($theme_option_active)) {
                $option_css = ThemeLib::getThemeCssFilePath($theme_active, $theme_option_active);
                $addSectionThemeCssFile($option_css);
            }
        } else {
            trigger_error("The requested theme's CSS file could not be read. Falling back to default theme.", E_USER_WARNING);
            $theme_active = 'default';
            $theme_option_active = '';
            $theme_css = ThemeLib::getThemeCssFilePath($theme_active, null);
            $addSectionThemeCssFile($theme_css);
        }
    }

    //6) include UAB admin CSS and layout in case we are on an admin or management page
    if (Sections::getCurrentSection() === Sections::SECTION_ADMIN_LAYOUT) {
        $addSectionThemeCssFile('themes/base_files/css/feature/adminui.css');
        if (! str_contains($_SERVER['PHP_SELF'], 'tiki-admin_modules.php')) {
            $smarty->assign('navbar_color_variant', $prefs['theme_navbar_color_variant_admin']);
        }
        /* Force the admin layout on admin pages */
        $prefs['site_layout_admin'] = 'admin';

        include_once 'admin/define_admin_icons.php';
        foreach ($admin_icons as & $admin_icon) {
            foreach ($admin_icon['children'] as & $child) {
                $child = array_merge(['disabled' => false, 'description' => ''], $child);
            }
        }
        $smarty->assign('admin_icons', $admin_icons);
    } else {
        $smarty->assign('navbar_color_variant', $prefs['theme_navbar_color_variant']);
    }

    //7) include optional custom.css if there. In case of theme option, first include main theme's custom.css, than the option's custom.css
    if (! empty($theme_option_active)) {
        $main_theme_path = $themelib->get_theme_path($theme_active);
        $main_theme_custom_css = "{$main_theme_path}/css/custom.css";
        if (is_readable($main_theme_custom_css)) {
            $addSectionThemeCssFile($main_theme_custom_css, 53);
        }
    }

    $custom_css = $themelib->get_theme_path($theme_active, $theme_option_active, 'custom.css');
    if (empty($custom_css)) {
        $custom_css = $themelib->get_theme_path('', '', 'custom.css');
    }
    if (is_readable($custom_css)) {
        $addSectionThemeCssFile($custom_css, 53);
    }
    if (! isset($prefs['site_favicon_enable']) || $prefs['site_favicon_enable'] === 'y') {    // if favicons are disabled in preferences, skip the lot of it.
        $favicon_path = $themelib->get_theme_path($prefs['theme'], $prefs['theme_option'], 'favicon-16x16.png', 'favicons');
        if ($favicon_path) {  // if there is a 16x16 png favicon in the theme folder, then find and display others if they exist
            $headerlib->add_link('icon', $favicon_path, '16x16', 'image/png');
            $favicon_path = (dirname($favicon_path)); // get_theme_path makes a lot of system calls, so just remember what dir to look in.
            if (is_file($favicon_path . '/apple-touch-icon.png')) {
                $headerlib->add_link('apple-touch-icon', $favicon_path . '/apple-touch-icon.png', '180x180');
            }
            if (is_file($favicon_path . '/favicon-32x32.png')) {
                $headerlib->add_link('icon', $favicon_path . '/favicon-32x32.png', '32x32', 'image/png');
            }
            if ($prefs['pwa_feature'] === 'y') {
                if (is_file($favicon_path . '/site.webmanifest')) {
                    $headerlib->add_link('manifest', $favicon_path . '/site.webmanifest');
                    // The file name changed, so check for the old file if the new does not exist
                } elseif (is_file($favicon_path . '/manifest.json')) {
                    $headerlib->add_link('manifest', $favicon_path . '/manifest.json');
                }
            }
            if (is_file($favicon_path . '/favicon.ico')) {
                $headerlib->add_link('shortcut icon', $favicon_path . '/favicon.ico');
            }
            if (is_file($favicon_path . '/safari-pinned-tab.svg')) {
                $headerlib->add_link('mask-icon', $favicon_path . '/safari-pinned-tab.svg', '', '', '#5bbad5');
            }
            if (is_file($favicon_path . '/browserconfig.xml')) {
                $headerlib->add_meta('msapplication-config', $favicon_path . '/browserconfig.xml');
            }
        } else {    // if no 16x16 png favicon exists, display Tiki icons
            $headerlib->add_link('icon', 'themes/base_files/favicons/favicon-16x16.png', '16x16', 'image/png');
            $headerlib->add_link('apple-touch-icon', 'themes/base_files/favicons/apple-touch-icon.png', '180x180');
            $headerlib->add_link('icon', 'themes/base_files/favicons/favicon-32x32.png', '32x32', 'image/png');
            $headerlib->add_link('shortcut icon', 'themes/base_files/favicons/favicon.ico');
            $headerlib->add_link('mask-icon', 'themes/base_files/favicons/safari-pinned-tab.svg', '', '', '#5bbad5');
            $headerlib->add_meta('msapplication-config', 'themes/base_files/favicons/browserconfig.xml');
            if ($prefs['pwa_feature'] && $prefs['pwa_feature'] == 'y') {
                $headerlib->add_link('manifest', 'themes/base_files/favicons/site.webmanifest');
            }
        }
        unset($favicon_path);  // no longer needed, so bye bye
    }

    //8) produce $iconset to be used for generating icons
    $iconset = TikiLib::lib('iconset')->getIconsetForTheme($theme_active, $theme_option_active);
    // and add js support file
    $headerlib->add_jsfile('lib/jquery_tiki/iconsets.js');
    // because theme setup now happens before javascript, we need to add this in js_config but to render after the initial setup of jqueryTiki
    $headerlib->add_js_config('jqueryTiki.iconset = ' . json_encode($iconset->getJS()), 10);

    //Note: if Theme Control is active, than tiki-tc.php can modify the active theme

    // Web Monetization
    if ($prefs['webmonetization_all_website'] === 'y' && ! empty($prefs['webmonetization_default_payment_pointer'])) {
        $headerlib->add_meta('monetization', $prefs['webmonetization_default_payment_pointer']);
    }

    // set the color of header bar and address bar
    if ($prefs['theme_header_and_address_bar_color'] === 'y') {
        if (Sections::isCurrentSection(Sections::SECTION_ADMIN) || Sections::isCurrentSection(Sections::SECTION_ADMIN_LAYOUT) || empty(Sections::getCurrentSection())) {
            $css_color_variable = "--tiki-top-" . $prefs['theme_navbar_color_variant_admin'] . "-bg";
        } else {
            $css_color_variable = "--tiki-top-" . $prefs['theme_navbar_color_variant'] . "-bg";
        }
        // construct jq to get the value of the $css_color_variable
        $jq = "var color = window.getComputedStyle(document.documentElement).getPropertyValue('" . $css_color_variable . "');";
        $jq .= "$('meta[name=\"theme-color\"]').attr('content', color);";

        // add meta tag
        $headerlib->add_meta("theme-color", "");
        // iOS Safari
        $headerlib->add_meta("mobile-web-app-capable", "yes");
        $headerlib->add_meta("mobile-web-app-status-bar-style", "black-translucent");
        // add jq to set content of theme-color Meta Tag to the current theme navbar color
        $headerlib->add_jq_onready($jq, 5);
    }
    //finish
    $smarty->initializePaths();
});
