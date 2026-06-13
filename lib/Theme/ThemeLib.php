<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Theme;

use Symfony\Component\Routing\Exception\InvalidParameterException;
use Tiki\Package\ExtensionManager;
use Tiki\Paths\Customization;
use TikiLib;
use Tiki\Sections;

/*
ThemeLib
@uses TikiLib
*/

/** This manages the theme access
 *
 * This is really old code.  This library is currently systematically
 * accessed using: $themelib = TikiLib::lib('theme');
 *
 * But it does not actually use any TikiLib methods, and is entirely static (not even a cache), so could be trivially refactored into a class with all static methods, just didn't have time to do it - benoitg - 2024-03-26
 */
class ThemeLib extends TikiLib
{
    /*
    @return array of folder names under a base directory containing themes
    */
    private static function getThemes(string $themeBasePath): array
    {
        $themes = [];
        //Considers any folder who has a descendant css/ dir containing css files to be a valid theme.
        $list_css = glob("{$themeBasePath}/*/css/*.css");
        if ($list_css == false) {
            return [];
        }
        foreach ($list_css as $css) {
            $css = dirname(dirname($css));
            $theme = basename($css);
            $themes[$theme] = tr($theme);
        }
        //make sure non-theme directories are removed from the array
        unset($themes['base_files']);
        unset($themes['templates']);
        unset($themes['css']);
        unset($themes['js']);
        return $themes;
    }

    /**
     * Retrieve the active theme and option
     * Usually accessed as list($theme, $themeOption) = ThemeLib::getActiveThemeAndOption();
     * @return array array of two strings, first is the theme, second is the theme option if any.
     */
    public static function getActiveThemeAndOption(): array
    {
        global $prefs, $smarty;

        $section = Sections::getCurrentSection();
        // Detect and set admin section BEFORE determining theme so that section checks work correctly
        // Only do this once per request to avoid recursion
        static $adminSectionDetected = false;
        if (! $adminSectionDetected && self::isAdminPageRequest()) {
            Sections::setCurrentSection(Sections::SECTION_ADMIN_LAYOUT);
            $prefs['site_layout_admin'] = 'admin';
            $prefs['site_layout'] = 'admin';
            $adminSectionDetected = true;
        }
        //Initialize variables for the actual theme and theme option to be displayed
        $theme_active = $prefs['theme'] ?? '';
        $theme_option_active = $prefs['theme_option'] ?? '';

        // User theme previously set up in lib/setup/user_prefs.php

        //consider Group theme
        if (! empty($prefs['useGroupTheme']) && $prefs['useGroupTheme'] == 'y') {
            $userlib = TikiLib::lib('user');
            $users_group_groupTheme = $userlib->get_user_group_theme();
            if (! empty($users_group_groupTheme)) {
                //group theme and option is stored in one column (groupTheme) in the users_groups table, so the theme and option value needs to be separated first
                list($group_theme, $group_theme_option) = self::extractThemeAndOptionFromString($users_group_groupTheme); //for more info see list_themes_and_options() function in themelib

                //set active theme
                $theme_active = $group_theme;
                $theme_option_active = $group_theme_option;

                //set group_theme smarty variable so that it can be used elsewhere - this probably shouldn't be there - beonitg - 2024-04-08
                $smarty->assign_by_ref('group_theme', $users_group_groupTheme);
            }
        }

        //consider Admin Theme
        if (! empty($prefs['theme_admin']) && (Sections::isCurrentSection(Sections::SECTION_ADMIN) || Sections::isCurrentSection(Sections::SECTION_ADMIN_LAYOUT) || empty($section))) {        // use admin theme if set
            $theme_active = $prefs['theme_admin'];
            $theme_option_active = $prefs['theme_option_admin'] ?? '';                                // and its option
        }

        //consider CSS Editor (tiki-edit_css.php)
        if (! empty($_SESSION['try_theme'])) {
            list($theme_active, $theme_option_active) = self::extractThemeAndOptionFromString($_SESSION['try_theme']);
        }
        return [$theme_active, $theme_option_active];
    }

    /**
     * Update Smarty template variables with the currently active theme
     * Called when section changes to ensure theme is correctly applied
     * @param bool $redetectTheme If true, re-detect theme based on current section (for callbacks). If false, use theme already in $prefs (for initial load).
     */
    public static function applyCurrentTheme(bool $redetectTheme = false): void
    {
        global $prefs, $smarty, $headerlib, $iconset;

        $site_theme_before = $prefs['theme'] ?? 'not set';
        $admin_theme_before = $prefs['theme_admin'] ?? 'not set';
        $group_theme_before = 'not active';
        if (! empty($prefs['useGroupTheme']) && $prefs['useGroupTheme'] == 'y') {
            $userlib = TikiLib::lib('user');
            $users_group_groupTheme = $userlib->get_user_group_theme();
            if (! empty($users_group_groupTheme)) {
                $group_theme_before = $users_group_groupTheme;
            }
        }
        $themeChangeModuleCurrent = $_SESSION['try_theme'] ?? 'not active';

        // Only re-detect if requested (e.g., when section changes mid-request)
        if ($redetectTheme) {
            list($theme_active, $theme_option_active) = self::getActiveThemeAndOption();
        } else {
            // Use theme already set in $prefs (detected earlier during setup)
            $theme_active = $prefs['theme'] ?? '';
            $theme_option_active = $prefs['theme_option'] ?? '';
        }

        //Write back global variables and prefs so that they can be accessed elsewhere
        $prefs['theme'] = $theme_active;
        $prefs['theme_option'] = $theme_option_active;
        $smarty->assign('theme', $theme_active);
        $smarty->assign('theme_option', $theme_option_active);

        // Debug logging
        $currentSection = Sections::getCurrentSection();
        // Load Bootstrap as ESM module - needed for dropdowns, modals, etc.
        $headerlib->add_js_module('
            import * as bootstrap from "bootstrap";
            window.bootstrap = bootstrap;
        ');
        $headerlib->add_jsfile(NODE_PUBLIC_DIST_PATH . '/clipboard/dist/clipboard.min.js');

        // Apply fixed width if configured
        if ($prefs['feature_fixed_width'] === 'y' && ! empty($prefs['layout_fixed_width'])) {
            $headerlib->add_css(
                "@media (min-width: 1200px) { .container { max-width:{$prefs['layout_fixed_width']}; } }"
            );
        }

        // NOW apply all 8 theme setup steps

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
        } elseif ($theme_active == 'custom_url' && ! empty($prefs['theme_custom_url'])) {
            $custom_theme = $prefs['theme_custom_url'];
            if (preg_match('/^(http(s)?:)?\/\//', $custom_theme)) {
                $headerlib->add_cssfile($custom_theme, 'external');
            } else {
                $headerlib->add_cssfile($custom_theme);
            }
        } else {
            $theme_css = self::getThemeCssFilePath($theme_active, '');
            if ($theme_css) {
                if ($prefs['theme_option_includes_main'] != 'y' || empty($theme_option_active)) {
                    $headerlib->add_cssfile($theme_css);
                }
                if (! empty($theme_option_active)) {
                    $option_css = self::getThemeCssFilePath($theme_active, $theme_option_active);
                    $headerlib->add_cssfile($option_css);
                }
            } else {
                trigger_error("The requested theme's CSS file could not be read. Falling back to default theme.", E_USER_WARNING);
                $theme_active = 'default';
                $theme_option_active = '';
                $theme_css = self::getThemeCssFilePath($theme_active, null);
                $headerlib->add_cssfile($theme_css);
            }
        }

        //6) include UAB admin CSS and layout in case we are on an admin or management page
        if (self::isAdminPageRequest()) {
            $headerlib->add_cssfile('themes/base_files/css/feature/adminui.css');
            if (! str_contains($_SERVER['PHP_SELF'], 'tiki-admin_modules.php')) {
                $smarty->assign('navbar_color_variant', $prefs['theme_navbar_color_variant_admin']);
            }

            // Load admin icons if on an admin page
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
            $main_theme_path = self::getThemePath($theme_active, '');
            $main_theme_custom_css = "{$main_theme_path}/css/custom.css";
            if (is_readable($main_theme_custom_css)) {
                $headerlib->add_cssfile($main_theme_custom_css, 53);
            }
        }

        $custom_css = self::getThemePath($theme_active, $theme_option_active, 'custom.css');
        if (empty($custom_css)) {
            $custom_css = self::getThemePath('', '', 'custom.css');
        }
        if (is_readable($custom_css)) {
            $headerlib->add_cssfile($custom_css, 53);
        }

        // Favicon handling
        if (! isset($prefs['site_favicon_enable']) || $prefs['site_favicon_enable'] === 'y') {
            $favicon_path = self::getThemePath($prefs['theme'], $prefs['theme_option'], 'favicon-16x16.png', 'favicons');
            if ($favicon_path) {
                $headerlib->add_link('icon', $favicon_path, '16x16', 'image/png');
                $favicon_path = (dirname($favicon_path));
                if (is_file($favicon_path . '/apple-touch-icon.png')) {
                    $headerlib->add_link('apple-touch-icon', $favicon_path . '/apple-touch-icon.png', '180x180');
                }
                if (is_file($favicon_path . '/favicon-32x32.png')) {
                    $headerlib->add_link('icon', $favicon_path . '/favicon-32x32.png', '32x32', 'image/png');
                }
                if ($prefs['pwa_feature'] === 'y') {
                    if (is_file($favicon_path . '/site.webmanifest')) {
                        $headerlib->add_link('manifest', $favicon_path . '/site.webmanifest');
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
            } else {
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
        }

        //8) produce $iconset to be used for generating icons
        $iconset = TikiLib::lib('iconset')->getIconsetForTheme($theme_active, $theme_option_active);
        // and add js support file - wrap in onready to ensure jQuery is loaded
        $headerlib->add_jq_onready('jqueryTiki.iconset = ' . json_encode($iconset->getJS()) . ';');
        $headerlib->add_jsfile('lib/jquery_tiki/iconsets.js');

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
            $jq = "var color = window.getComputedStyle(document.documentElement).getPropertyValue('" . $css_color_variable . "');";
            $jq .= "$('meta[name=\"theme-color\"]').attr('content', color);";
            $headerlib->add_meta("theme-color", "");
            $headerlib->add_meta("apple-mobile-web-app-capable", "yes");
            $headerlib->add_meta("apple-mobile-web-app-status-bar-style", "black-translucent");
            $headerlib->add_jq_onready($jq, 5);
        }

        $smarty->initializePaths();
    }

    /**
     * Check if the current request is for an admin page that should use the admin theme
     * Encapsulates the complex condition check to avoid code duplication
     * @return bool True if this is an admin page request
     */
    public static function isAdminPageRequest(): bool
    {
        global $prefs, $user;

        if (($prefs['theme_unified_admin_backend'] ?? '') !== 'y') {
            return false;
        }

        $userlib = TikiLib::lib('user');
        $groups = $userlib->get_user_groups($user);

        if (! in_array('Admins', $groups)) {
            return false;
        }

        return (strpos($_SERVER['PHP_SELF'], 'admin')
            || strpos($_SERVER['PHP_SELF'], 'cache')
            || strpos($_SERVER['PHP_SELF'], 'import')
            || strpos($_SERVER['PHP_SELF'], 'manage')
            || strpos($_SERVER['PHP_SELF'], 'permissions')
            || strpos($_SERVER['PHP_SELF'], 'stats')
            || strpos($_SERVER['PHP_SELF'], 'tiki-edit_banner')
            || strpos($_SERVER['PHP_SELF'], 'tiki-edit_categories')
            || strpos($_SERVER['PHP_SELF'], 'tiki-edit_perspective')
            || strpos($_SERVER['PHP_SELF'], 'tiki-edit_quiz')
            || strpos($_SERVER['PHP_SELF'], 'tiki-export')
            || strpos($_SERVER['PHP_SELF'], 'tiki-import')
            || strpos($_SERVER['PHP_SELF'], 'tiki-list_banners')
            || strpos($_SERVER['PHP_SELF'], 'tiki-list_comments')
            || strpos($_SERVER['PHP_SELF'], 'tiki-list_contents')
            || strpos($_SERVER['PHP_SELF'], 'tiki-plugins')
            || strpos($_SERVER['PHP_SELF'], 'tiki-received')
            || strpos($_SERVER['PHP_SELF'], 'tiki-sys'));
    }

    /**
     * A utility method for \Tiki\Smarty\SmartyTiki to convert paths like
     * public/generated/_custom/sites/default_site/themes/customizationstest/ back into
     * _custom/sites/default_site/themes/customizationstest/
     *
     * @param string $path public path
     * @return string private path corresponding to public path.
     */
    private static function convertPublicToPrivatePath(string $path): string
    {
        $convertedPath = str_replace(TIKI_CUSTOMIZATIONS_PUBLIC_PATH, TIKI_CUSTOMIZATIONS_SRC_PATH, $path);
        return $convertedPath;
    }


    /**
     * Get the physical paths to look for themes, considering multitiki, etc.
     *
     * @return array of lookup paths, most specific first
     */
    private static function getThemeLookupPaths(): array
    {
        global $tikidomain;

        $paths = [];
        $path = Customization::getCurrentSitePublicPath(THEMES_PATH_FRAGMENT);
        if ($path) {
                $paths[] = $path;
        }
        if ($tikidomain) {
            //Legacy tikidomain themes
            $tikidomainThemePath = BASE_THEMES_SRC_PATH . "/$tikidomain";
            if (is_dir($tikidomainThemePath)) {
                 $paths[] = $tikidomainThemePath;
            }
        }

        //Shared customizations themes
        $customizationsSharedPath = TIKI_CUSTOMIZATIONS_SHARED_PUBLIC_PATH . '/' . THEMES_PATH_FRAGMENT;

        if (is_dir($customizationsSharedPath)) {
            $paths[] = $customizationsSharedPath;
        }

        //Base tiki themes
        $paths[] = BASE_THEMES_SRC_PATH;
        return $paths;
    }

    /* replaces legacy list_styles() function
    @return array of all themes offered by Tiki
    */
    public function list_themes(): array
    {
        //set special array values and get themes from the main themes directory
        //this way default and custom remains on the top of the array and default keeps its description
        $themes = [
        'default' => tr('Default Bootstrap'),
        'custom_url' => tr('Custom theme by specifying URL'),
        ];

        foreach (self::getThemeLookupPaths() as $lookupPath) {
            $themes = array_merge($themes, self::getThemes($lookupPath));
        }
        $themes = array_unique($themes);
        return $themes;
    }

/**
 * Lists the layouts available for the specified theme and theme option.
 * Includes the layouts distributed with tiki, and the parent theme's layouts.
 *
 * @param string|null $theme
 * @param string|null $theme_option
 * @return array
 */
    private static function listLayouts(?string $theme = null, ?string $theme_option = null): array
    {
        $available_layouts = [];
        //Look in base template dir
        foreach (scandir(TIKI_PATH . '/' . SMARTY_BASE_LAYOUTS_PATH) as $layoutName) {
            if ($layoutName[0] != '.' && $layoutName != 'index.php' && $layoutName != 'README.md') {
                $available_layouts[$layoutName] = ucfirst($layoutName);
            }
        }
        //Look in extensions
        foreach (ExtensionManager::getPaths() as $path) {
            if (file_exists($path . '/templates/layouts/')) {
                foreach (scandir($path . '/templates/layouts/') as $layoutName) {
                    if ($layoutName[0] != '.' && $layoutName != 'index.php') {
                         $available_layouts[$layoutName] = ucfirst($layoutName);
                    }
                }
            }
        }

        //Look in the specified theme
        $layoutsPath = self::getThemePath($theme, '', THEMES_LAYOUTS_PATH_FRAGMENT, true);
        if ($layoutsPath) {
            foreach (scandir(TIKI_PATH . "/" . $layoutsPath) as $layoutName) {
                if ($layoutName[0] != '.' && $layoutName != 'index.php') {
                    $available_layouts[$layoutName] = ucfirst($layoutName);
                }
            }
        }

        //Look in the specified theme option
        if ($theme_option) {
            $layoutsPath = self::getThemePath($theme, $theme_option, THEMES_LAYOUTS_PATH_FRAGMENT, true);

            if ($layoutsPath) {
                foreach (scandir(TIKI_PATH . "/" . $layoutsPath) as $layoutName) {
                    if ($layoutName[0] != '.' && $layoutName != 'index.php') {
                        $available_layouts[$layoutName] = ucfirst($layoutName);
                    }
                }
            }
        }
        return $available_layouts;
    }

    public static function listUserSelectableLayouts(?string $theme = null, ?string $theme_option = null): array
    {
        $selectable_layouts = [];
        $available_layouts = self::listLayouts($theme, $theme_option);
        foreach ($available_layouts as $layoutName => $layoutLabel) {
            if (
                $layoutName == 'mobile'
                || $layoutName == 'layout_plain.tpl'
                || $layoutName == 'internal'
                || $layoutName == 'admin'
            ) {
                // hide layouts that are for internal use only
                continue;
            } elseif ($layoutName == 'basic') {
                $selectable_layouts[$layoutName] = tra('Single Container');
            } elseif ($layoutName == 'classic') {
                $selectable_layouts[$layoutName] = tra('Classic Tiki (3 containers - header, middle, footer)');
            } elseif ($layoutName == 'social') {
                $selectable_layouts[$layoutName] = tra('Classic Bootstrap (fixed top navbar)');
            } else {
                $selectable_layouts[$layoutName] = $layoutLabel;
            }
        }
        return $selectable_layouts;
    }

    /*
    @TODO: does not support multidomain or new _custom options.  But that predated _custom.  Only used in lib/prefs/themes.php - benoitg 2024-03-29
    @return array of all theme options
    */
    public function get_options()
    {
        $options = [];
        foreach (glob(BASE_THEMES_SRC_PATH . "/*/options/*/css/*.css") as $css) {
            $css = dirname(dirname($css));
            $option = basename($css);
            $options[$option] = tr($option);
        }
        return $options;
    }

    /* replaces legacy list_style_options function
    @param $theme - main theme (e.g. "fivealive")
    @return array of options the theme's options directory (e.g. from "themes/fivealive/options/")
    */
    public function list_theme_options($theme)
    {
        $theme_options = [];
        if (isset($theme) and $theme != 'custom_url') { //don't consider custom URL themes to have options
            $themeOptionBasePath = self::getThemePath($theme, '');
            $list_css = glob("{$themeOptionBasePath}/options/*/css/*.css");
            if ($list_css == false) {
                return [];
            }
            foreach ($list_css as $css) {
                $css = dirname(dirname($css));
                $option = basename($css);
                $theme_options[$option] = tr($option);
            }
        }
        return $theme_options;
    }

    /* the group theme setting is stored in one column, so we need an array where all themes and all options are all available
    @return array of all themes and all options
    */
    public function list_themes_and_options()
    {
        $theme_options = [];
        $themes = $this->list_themes();
        unset($themes['custom_url']); //make sure Custom URL is removed from the list as it can not have options
        foreach ($themes as $theme) {
            $options = $this->list_theme_options($theme);
            foreach ($options as $option) {
                $theme_options[$theme . '/' . $option] = $theme . '/' . $option;
            }
        }
        $themes_and_options = array_merge($themes, $theme_options); //merge the two array
        natsort($themes_and_options); //sort the values
        return $themes_and_options;
    }

    /* if theme and option is concatenated into one string (eg: group themes, theme control), then extract theme and option info from the string
    @return theme and option name
    */
    public static function extractThemeAndOptionFromString($themeoption)
    {
        $theme = '';
        $option = '';
        if (! empty($themeoption)) {
            $items = explode("/", $themeoption);
            $theme = $items[0]; //theme is always there
            if (isset($items[1])) { //check if we have option
                $option = $items[1];
            }
        }

        return [$theme, $option];
    }

    /* get thumbnail for theme if there is one. The thumbnail should be a png file.
    @param $theme - theme name (e.g. fivealive)
    @param $option - optional theme option file name
    @return string path to thumbnail file to be used by an img element
    */
    public function get_thumbnail_file($theme, $option = '')
    {
        if (! empty($option) && $option != tr('None')) {
            $filename = $option . '.png'; // add .png
        } else {
            $filename = $theme . '.png'; // add .png
            $option = '';
        }
        return $this->get_theme_path($theme, $option, $filename);
    }

    /**
     * Retrieves the real file paths for a specific theme or theme option, taking into account multitiki, customizations, etc.
     *
     * All parameters are optional.  By default returns the path of the currently set theme.
     *
     * @param string|null $themeParam - main theme (e.g. "fivealive") - can be null to return the path of the currently set theme and theme option)
     * @param string|null $optionParam - optional theme option file name (e.g. "akebi").  To get the current base theme path without the current option, pass the empty string explicitely to this parameter
     * @param string|null $pathFragment  subdirectory or relative file path to look for.  For theme options, will fall back to parent theme.  If not found, returns null
     *
     *
     * @return string|null - path to dir or file if found or null if not found
     */
    public static function getThemePath(?string $themeParam = null, ?string $optionParam = null, ?string $pathFragment = null, bool $returnPrivatePath = false): ?string
    {
        global $tikidomain;
        list($activeTheme, $activeThemeOption) = self::getActiveThemeAndOption();
        $path = null;
        $dir_base = '';
        if ($tikidomain && is_dir(BASE_THEMES_SRC_PATH . "/$tikidomain")) {
            $dir_base = $tikidomain . '/';
        }

        if ($optionParam && ! $themeParam) {
            throw new InvalidParameterException("optionParam whithout a base theme specified in themeParam does not make sense");
        }
        $theme = is_null($themeParam) ? $activeTheme : $themeParam;
        //We only autodetect the current theme option if themeParam wasn't explicitely specified
        $option = is_null($optionParam) && is_null($themeParam) ? $activeThemeOption : $optionParam;

        $themePathFragment = '';
        if (! empty($theme)) {
            $themePathFragment = '/' . $theme;
        }

        if (! empty($option)) {
            $themeOptionPathFragment = '/options/' . $option;
        } else {
            $themeOptionPathFragment = '';
        }

        $suffixFragment = $pathFragment ? '/' . $pathFragment : '';

        foreach (self::getThemeLookupPaths() as $lookupPath) {
            $realLookupPath = $returnPrivatePath ? self::convertPublicToPrivatePath($lookupPath) : $lookupPath;
            if ($option) {
                $path = $realLookupPath .
                $themePathFragment . $themeOptionPathFragment . $suffixFragment;
                if (file_exists($path)) {
                    break;
                }
            }
            // try "parent" theme dir if no option one
            $path = $realLookupPath . $themePathFragment . $suffixFragment;
            if (file_exists($path)) {
                break;
            }
            $path = null;
        }
        return $path;
    }

    /** replaces legacy get_style_path function
     *
     * @deprecated new code should use ThemeLib::getThemePath() directly
     * Retrieves the real file paths for a specific theme or theme option, taking into account multitiki, customizations, etc.
     *
     * @param string $theme - main theme (e.g. "fivealive" - can be empty to return main themes dir for legacy support)
     * @param string $option - optional theme option file name (e.g. "akebi")
     * @param string $filename - optional filename to look for (e.g. "purple.png")
     * @param string $subdir - optional dir to look in, e.g. 'css' etc (will guess by file extension if this not set but filename is)
     * @return string          - path to dir or file if found or empty string if not - e.g. "themes/mydomain.tld/fivealive/options/akebi"
     */

    public function get_theme_path(?string $theme = null, $option = '', $filename = '', $subdir = ''): string
    {

        if (empty($subdir) && $filename) {
            $extension = substr($filename, strrpos($filename, '.') + 1);
            switch ($extension) {
                case 'css':
                    $subdir = 'css';
                    break;
                case 'php':
                    $subdir = 'icons';
                    break;
                case 'png':
                case 'gif':
                case 'jpg':
                case 'jpeg':
                case 'svg':
                    $subdir = 'images';
                    break;
                case 'less':
                    $subdir = 'less';
                    break;
                case 'js':
                    $subdir = 'js';
                    break;
                case 'tpl':
                    $subdir = 'templates';
                    break;
            }
        }
        $pathFragment = '';
        if ($subdir) {
            $pathFragment .= $subdir;
        }
        if ($filename) {
            $pathFragment .= ($subdir ? '/' . $filename : $filename);
        }
            $path = self::getThemePath($theme, $option, $pathFragment);
        if (is_null($path)) {
            $path = '';
        }
        return $path;
    }

    public static function getThemeCssFilePath(?string $theme = null, ?string $option = null): ?string
    {
        if ($option) {
            $path = self::getThemePath($theme, $option, "css/$option.css");
        } else {
            $path = self::getThemePath($theme, '', "css/$theme.css");
        }
        return $path;
    }

    /* get list of base iconsets
    @return $base_iconsets - an array containing all icon set names from themes/base_files/iconsets folder
    */
    public function list_base_iconsets()
    {
        $base_iconsets = [];
        $iconsetlib = TikiLib::lib('iconset');

        if (is_dir(BASE_THEMES_SRC_PATH . '/base_files/iconsets')) {
            foreach (scandir(BASE_THEMES_SRC_PATH . '/base_files/iconsets') as $iconset_file) {
                if ($iconset_file[0] != '.' && $iconset_file != 'index.php') {
                    $data = $iconsetlib->loadFile(BASE_THEMES_SRC_PATH . '/base_files/iconsets/' . $iconset_file);
                    $base_iconsets[substr($iconset_file, 0, -4)] = $data['name'];
                }
            }
        }
        return $base_iconsets;
    }

    /* get list of available themes and options
    @return array of available themes and options based on $prefs['available_themes'] setting. This function does not consider if change_theme is on or off.
    */
    public function get_available_themesandoptions()
    {
        global $prefs;
        $available_themesandoptions = [];
        if ($prefs['available_themes'] != 0 and ! empty($prefs['available_themes'][0])) { //if pref['available_themes'] is set, than use it
            $available_themesandoptions = array_combine($prefs['available_themes'], $prefs['available_themes']); // TODO: does it make any sense to combine the same pref array with itself? -- luci
        } else {
            $available_themesandoptions = $this->list_themes_and_options(); //else load all themes and options
            unset($available_themesandoptions['custom_url']); //make sure Custom URL is removed from the list
        }
        return $available_themesandoptions;
    }
    /* get a list of available themes
    @return array of available themes based on $prefs['available_themes'] setting. This function does not consider if change_theme is on or off.
    */
    public function get_available_themes()
    {
        global $prefs;
        $available_themes = [];
        if (! empty($prefs['available_themes']) && ! empty($prefs['available_themes'][0])) { //if pref['available_themes'] is set, than use it
            foreach ($prefs['available_themes'] as $available_theme) {
                $theme = self::extractThemeAndOptionFromString($available_theme)[0];
                $available_themes[$theme] = $theme;
                $available_themes['default'] = tr('Default Bootstrap');
            }
        } else {
            $available_themes = $this->list_themes(); //else load all themes and options
            unset($available_themes['custom_url']); //make sure Custom URL is removed from the list
        }
        return $available_themes;
    }

    /* get a list of available options for a theme
    @return array of available theme options based on $prefs['available_themes'] setting. This function does not consider if change_theme is on or off.
    */
    public function get_available_options($theme)
    {
        global $prefs;
        $available_options = [];
        if (! empty($prefs['available_themes']) && ! empty($prefs['available_themes'][0])) {
            foreach ($prefs['available_themes'] as $available_themeandoption) {
                $themeandoption = self::extractThemeAndOptionFromString($available_themeandoption);
                if ($theme === $themeandoption[0] && ! empty($themeandoption[1])) {
                    $available_options[$themeandoption[1]] = $themeandoption[1];
                }
            }
            return $available_options;
        } else {
            return $this->list_theme_options($theme);
        }
    }
}
