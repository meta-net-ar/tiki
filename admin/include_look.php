<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// This script may only be included - so its better to die if called directly.
if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
    header('location: index.php');
    exit;
}
global $prefs;
$themelib = TikiLib::lib('theme');

//handle case when changing the themes in the Look and Feel settings panel
$a_theme = $prefs['theme'];
if (isset($_REQUEST['looksetup'])) {
    if (isset($_REQUEST['theme'])) {
        if (! isset($_REQUEST['theme_option']) || $_REQUEST['theme_option'] = '') {
            // theme has no options
            $_REQUEST['theme_option'] = '';
        }
    }
} else {
    // just changed theme menu, so refill options
    if (isset($_REQUEST['theme']) && $_REQUEST['theme'] != '') {
        $a_theme = $_REQUEST['theme'];
    }
}

$themes = $themelib->list_themes();
$smarty->assign_by_ref('themes', $themes);
$theme_options = $themelib->list_theme_options($a_theme);
$smarty->assign('theme_options', $theme_options);

$themePrefs = TikiLib::lib('prefs')->getThemePrefs();
$smarty->assign('themePrefs', $themePrefs);


$default_themes_modes = [];
$custom_themes_modes = [];
try {
     $default_themes_modes = TikiDb::get()->fetchAll("SELECT * FROM tiki_custom_color_modes WHERE custom='n'", null, -1, -1, 'exception');
     $custom_themes_modes = TikiDb::get()->fetchAll("SELECT * FROM tiki_custom_color_modes WHERE custom='y'", null, -1, -1, 'exception');
} catch (Exception $e) {
    $smarty->assign('color_mode_error', true);
    //$message = '<span title="' . tra("You need to update your database to start using color modes on your website") . '">' . tra("Your database needs to be updated") . '<i class="bi bi-question"></i></span>';
   //$smarty->assign('message', $message);
}

$smarty->assign('default_modes', $default_themes_modes);
$smarty->assign('custom_modes', $custom_themes_modes);

// get thumbnail if there is one
$thumbfile = $themelib->get_thumbnail_file($prefs['site_theme'], $prefs['site_theme_option']);
if (empty($thumbfile)) {
    $thumbfile = $themelib->get_thumbnail_file($prefs['site_theme']);
}
if (empty($thumbfile)) {
    $thumbfile = 'img/trans.png';
}
$smarty->assign('thumbfile', $thumbfile);

// hash of themes and their options and their thumbnail images
if ($prefs['feature_jquery'] == 'y') {
    $js = 'var theme_options = {';
    foreach ($themes as $theme => $value) {
        $js .= "\n'$theme':['" . $themelib->get_thumbnail_file($theme, '') . '\',{';
        $options = $themelib->list_theme_options($theme);
        if ($options) {
            foreach ($options as $option) {
                $js .= "'$option':'" . $themelib->get_thumbnail_file($theme, $option) . '\',';
            }
            $js = substr($js, 0, strlen($js) - 1) . '}';
        } else {
            $js .= '}';
        }
        $js .= '],';
    }
    $js = substr($js, 0, strlen($js) - 1);
    $js .= '};';

    //Setup theme layouts array matching themes and theme:options with their respective layouts
    $js .= 'var theme_layouts = ';
    foreach ($themes as $theme => $value) {
        $theme_layouts[$theme] = ThemeLib::listUserSelectableLayouts($theme);
        $options = $themelib->list_theme_options($theme);
        if ($options) {
            foreach ($options as $option) {
                $theme_layouts[$theme . ':' . $option] = ThemeLib::listUserSelectableLayouts($theme, $option);
            }
        }
    }
    //encode $theme_layouts into json to allow js below to fetch layouts based on theme selected by user
    $theme_layouts_js = json_encode($theme_layouts);
    $js .= $theme_layouts_js . ";";

    // JS to handle theme/option changes client-side
    // the var (theme_options) has to be declared in the same block for AJAX call scope
    $none = json_encode(tr('None'));
    $headerlib->add_js(
        <<<JS
$js

var css_vars_list = {
    //{var_name : [used:bool,input_markup:string]}
    "--bs-body-color": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--bs-body-color">
            <div class="col-auto">
                <label class="col-form-label">--bs-body-color</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--bs-body-color" value="#adb5bd" title="Choose your color">
            </div>
        </div>`,
    ],

    "--bs-body-bg": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--bs-body-bg">
            <div class="col-auto">
                <label class="col-form-label">--bs-body-bg</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--bs-body-bg" value="#212529" title="Choose your color">
            </div>
        </div>`,
    ],

    "--bs-emphasis-color": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--bs-emphasis-color">
            <div class="col-auto">
                <label class="col-form-label">--bs-emphasis-color</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--bs-emphasis-color" value="#ffffff" title="Choose your color">
            </div>
        </div>`,
    ],

    "--bs-secondary-bg": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--bs-secondary-bg">
            <div class="col-auto">
                <label class="col-form-label">--bs-secondary-bg</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--bs-secondary-bg" value="#343a40" title="Choose your color">
            </div>
        </div>`,
    ],

    "--bs-tertiary-bg": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--bs-tertiary-bg">
            <div class="col-auto">
                <label class="col-form-label">--bs-tertiary-bg</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--bs-tertiary-bg" value="#2b3035" title="Choose your color">
            </div>
        </div>`,
    ],

    "--bs-primary-text-emphasis": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--bs-primary-text-emphasis">
            <div class="col-auto">
                <label class="col-form-label">--bs-primary-text-emphasis</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--bs-primary-text-emphasis" value="#6ea8fe" title="Choose your color">
            </div>
        </div>`,
    ],

    "--bs-secondary-text-emphasis": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--bs-secondary-text-emphasis">
            <div class="col-auto">
                <label class="col-form-label">--bs-secondary-text-emphasis</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--bs-secondary-text-emphasis" value="#a7acb1" title="Choose your color">
            </div>
        </div>`,
    ],

    "--bs-success-text-emphasis": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--bs-success-text-emphasis">
            <div class="col-auto">
                <label class="col-form-label">--bs-success-text-emphasis</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--bs-success-text-emphasis" value="#75b798" title="Choose your color">
            </div>
        </div>`,
    ],

    "--bs-info-text-emphasis": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--bs-info-text-emphasis">
            <div class="col-auto">
                <label class="col-form-label">--bs-info-text-emphasis</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--bs-info-text-emphasis" value="#6edff6" title="Choose your color">
            </div>
        </div>`,
    ],

    "--bs-warning-text-emphasis": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--bs-warning-text-emphasis">
            <div class="col-auto">
                <label class="col-form-label">--bs-warning-text-emphasis</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--bs-warning-text-emphasis" value="#ffda6a" title="Choose your color">
            </div>
        </div>`,
    ],

    "--bs-danger-text-emphasis": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--bs-danger-text-emphasis">
            <div class="col-auto">
                <label class="col-form-label">--bs-danger-text-emphasis</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--bs-danger-text-emphasis" value="#ea868f" title="Choose your color">
            </div>
        </div>`,
    ],

    "--bs-light-text-emphasis": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--bs-light-text-emphasis">
            <div class="col-auto">
                <label class="col-form-label">--bs-light-text-emphasis</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--bs-light-text-emphasis" value="#f8f9fa" title="Choose your color">
            </div>
        </div>`,
    ],

    "--bs-dark-text-emphasis": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--bs-dark-text-emphasis">
            <div class="col-auto">
                <label class="col-form-label">--bs-dark-text-emphasis</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--bs-dark-text-emphasis" value="#dee2e6" title="Choose your color">
            </div>
        </div>`,
    ],

    "--bs-primary-bg-subtle": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--bs-primary-bg-subtle">
            <div class="col-auto">
                <label class="col-form-label">--bs-primary-bg-subtle</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--bs-primary-bg-subtle" value="#031633" title="Choose your color">
            </div>
        </div>`,
    ],

    "--bs-secondary-bg-subtle": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--bs-secondary-bg-subtle">
            <div class="col-auto">
                <label class="col-form-label">--bs-secondary-bg-subtle</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--bs-secondary-bg-subtle" value="#161719" title="Choose your color">
            </div>
        </div>`,
    ],

    "--bs-success-bg-subtle": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--bs-success-bg-subtle">
            <div class="col-auto">
                <label class="col-form-label">--bs-success-bg-subtle</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--bs-success-bg-subtle" value="#051b11" title="Choose your color">
            </div>
        </div>`,
    ],

    "--bs-info-bg-subtle": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--bs-info-bg-subtle">
            <div class="col-auto">
                <label class="col-form-label">--bs-info-bg-subtle</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--bs-info-bg-subtle" value="#032830" title="Choose your color">
            </div>
        </div>`,
    ],

    "--bs-warning-bg-subtle": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--bs-warning-bg-subtle">
            <div class="col-auto">
                <label class="col-form-label">--bs-warning-bg-subtle</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--bs-warning-bg-subtle" value="#332701" title="Choose your color">
            </div>
        </div>`,
    ],

    "--bs-danger-bg-subtle": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--bs-danger-bg-subtle">
            <div class="col-auto">
                <label class="col-form-label">--bs-danger-bg-subtle</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--bs-danger-bg-subtle" value="#2c0b0e" title="Choose your color">
            </div>
        </div>`,
    ],

    "--bs-light-bg-subtle": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--bs-light-bg-subtle">
            <div class="col-auto">
                <label class="col-form-label">--bs-light-bg-subtle</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--bs-light-bg-subtle" value="#343a40" title="Choose your color">
            </div>
        </div>`,
    ],

    "--bs-dark-bg-subtle": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--bs-dark-bg-subtle">
            <div class="col-auto">
                <label class="col-form-label">--bs-dark-bg-subtle</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--bs-dark-bg-subtle" value="#1a1d20" title="Choose your color">
            </div>
        </div>`,
    ],

    "--bs-primary-border-subtle": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--bs-primary-border-subtle">
            <div class="col-auto">
                <label class="col-form-label">--bs-primary-border-subtle</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--bs-primary-border-subtle" value="#084298" title="Choose your color">
            </div>
        </div>`,
    ],

    "--bs-secondary-border-subtle": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--bs-secondary-border-subtle">
            <div class="col-auto">
                <label class="col-form-label">--bs-secondary-border-subtle</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--bs-secondary-border-subtle" value="#41464b" title="Choose your color">
            </div>
        </div>`,
    ],

    "--bs-success-border-subtle": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--bs-success-border-subtle">
            <div class="col-auto">
                <label class="col-form-label">--bs-success-border-subtle</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--bs-success-border-subtle" value="#0f5132" title="Choose your color">
            </div>
        </div>`,
    ],

    "--bs-info-border-subtle": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--bs-info-border-subtle">
            <div class="col-auto">
                <label class="col-form-label">--bs-info-border-subtle</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--bs-info-border-subtle" value="#087990" title="Choose your color">
            </div>
        </div>`,
    ],

    "--bs-warning-border-subtle": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--bs-warning-border-subtle">
            <div class="col-auto">
                <label class="col-form-label">--bs-warning-border-subtle</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--bs-warning-border-subtle" value="#997404" title="Choose your color">
            </div>
        </div>`,
    ],

    "--bs-danger-border-subtle": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--bs-danger-border-subtle">
            <div class="col-auto">
                <label class="col-form-label">--bs-danger-border-subtle</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--bs-danger-border-subtle" value="#842029" title="Choose your color">
            </div>
        </div>`,
    ],

    "--bs-light-border-subtle": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--bs-light-border-subtle">
            <div class="col-auto">
                <label class="col-form-label">--bs-light-border-subtle</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--bs-light-border-subtle" value="#495057" title="Choose your color">
            </div>
        </div>`,
    ],

    "--bs-dark-border-subtle": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--bs-dark-border-subtle">
            <div class="col-auto">
                <label class="col-form-label">--bs-dark-border-subtle</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--bs-dark-border-subtle" value="#343a40" title="Choose your color">
            </div>
        </div>`,
    ],

    "--tiki-top-bg": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--tiki-top-bg">
            <div class="col-auto">
                <label class="col-form-label">--tiki-top-bg</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--tiki-top-bg" value="#ffffff" title="Choose your color">
            </div>
        </div>`,
    ],

    "--tiki-top-color": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--tiki-top-color">
            <div class="col-auto">
                <label class="col-form-label">--tiki-top-color</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--tiki-top-color" value="#000000" title="Choose your color">
            </div>
        </div>`,
    ],

    "--tiki-top-hover-color": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--tiki-top-hover-color">
            <div class="col-auto">
                <label class="col-form-label">--tiki-top-hover-color</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--tiki-top-hover-color" value="#000000" title="Choose your color">
            </div>
        </div>`,
    ],

    "--tiki-top-hover-bg": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--tiki-top-hover-bg">
            <div class="col-auto">
                <label class="col-form-label">--tiki-top-hover-bg</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--tiki-top-hover-bg" value="#ffffff" title="Choose your color">
            </div>
        </div>`,
    ],

    "--tiki-top-border": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--tiki-top-border">
            <div class="col-auto">
                <label class="col-form-label">--tiki-top-border</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--tiki-top-border" value="#111" title="Choose your color">
            </div>
        </div>`,
    ],

    "--tiki-topbar-bg": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--tiki-topbar-bg">
            <div class="col-auto">
                <label class="col-form-label">--tiki-topbar-bg</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--tiki-topbar-bg" value="#ffffff" title="Choose your color">
            </div>
        </div>`,
    ],

    "--tiki-topbar-color": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--tiki-topbar-color">
            <div class="col-auto">
                <label class="col-form-label">--tiki-topbar-color</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--tiki-topbar-color" value="#000000" title="Choose your color">
            </div>
        </div>`,
    ],

    "--tiki-topbar-hover-color": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--tiki-topbar-hover-color">
            <div class="col-auto">
                <label class="col-form-label">--tiki-topbar-hover-color</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--tiki-topbar-hover-color" value="#000000" title="Choose your color">
            </div>
        </div>`,
    ],

    "--tiki-topbar-hover-bg": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--tiki-topbar-hover-bg">
            <div class="col-auto">
                <label class="col-form-label">--tiki-topbar-hover-bg</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--tiki-topbar-hover-bg" value="#ffffff" title="Choose your color">
            </div>
        </div>`,
    ],

    "--tiki-topbar-border": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--tiki-topbar-border">
            <div class="col-auto">
                <label class="col-form-label">--tiki-topbar-border</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--tiki-topbar-border" value="#eee" title="Choose your color">
            </div>
        </div>`,
    ],

    "--tiki-site-title-color": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--tiki-site-title-color">
            <div class="col-auto">
                <label class="col-form-label">--tiki-site-title-color</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--tiki-site-title-color" value="#ffffff" title="Choose your color">
            </div>
        </div>`,
    ],
    
    "--tiki-admin-top-nav-bg" : [
        false, 
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--tiki-admin-top-nav-bg">
            <div class="col-auto">
                <label class="col-form-label">--tiki-admin-top-nav-bg</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--tiki-admin-top-nav-bg" value="#000000" title="Choose your color">
            </div>
        </div>`,
    ],

    "--tiki-admin-top-nav-color": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--tiki-admin-top-nav-color">
            <div class="col-auto">
                <label class="col-form-label">--tiki-admin-top-nav-color</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--tiki-admin-top-nav-color" value="#000000" title="Choose your color">
            </div>
        </div>`,
    ],

    "--tiki-admin-top-nav-hover-color": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--tiki-admin-top-nav-hover-color">
            <div class="col-auto">
                <label class="col-form-label">--tiki-admin-top-nav-hover-color</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--tiki-admin-top-nav-hover-color" value="#000000" title="Choose your color">
            </div>
        </div>`,
    ],

    "--tiki-admin-top-nav-hover-bg": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--tiki-admin-top-nav-hover-bg">
            <div class="col-auto">
                <label class="col-form-label">--tiki-admin-top-nav-hover-bg</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--tiki-admin-top-nav-hover-bg" value="#ffffff" title="Choose your color">
            </div>
        </div>`,
    ],

    "--tiki-admin-aside-nav-bg": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--tiki-admin-aside-nav-bg">
            <div class="col-auto">
                <label class="col-form-label">--tiki-admin-aside-nav-bg</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--tiki-admin-aside-nav-bg" value="#ffffff" title="Choose your color">
            </div>
        </div>`,
    ],

    "--tiki-admin-aside-nav-color": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--tiki-admin-aside-nav-color">
            <div class="col-auto">
                <label class="col-form-label">--tiki-admin-aside-nav-color</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--tiki-admin-aside-nav-color" value="#000000" title="Choose your color">
            </div>
        </div>`,
    ],

    "--tiki-admin-aside-nav-hover-color": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--tiki-admin-aside-nav-hover-color">
            <div class="col-auto">
                <label class="col-form-label">--tiki-admin-aside-nav-hover-color</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--tiki-admin-aside-nav-hover-color" value="#000000" title="Choose your color">
            </div>
        </div>`,
    ],

    "--tiki-admin-dropdown-bg": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--tiki-admin-dropdown-bg">
            <div class="col-auto">
                <label class="col-form-label">--tiki-admin-dropdown-bg</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--tiki-admin-dropdown-bg" value="#ffffff" title="Choose your color">
            </div>
        </div>`,
    ],

    "--tiki-admin-dropdown-link-color": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--tiki-admin-dropdown-link-color">
            <div class="col-auto">
                <label class="col-form-label">--tiki-admin-dropdown-link-color</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--tiki-admin-dropdown-link-color" value="#000000" title="Choose your color">
            </div>
        </div>`,
    ],

    "--tiki-admin-dropdown-link-hover-color": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--tiki-admin-dropdown-link-hover-color">
            <div class="col-auto">
                <label class="col-form-label">--tiki-admin-dropdown-link-hover-color</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--tiki-admin-dropdown-link-hover-color" value="#000000" title="Choose your color">
            </div>
        </div>`,
    ],

    "--tiki-admin-dropdown-link-hover-bg": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--tiki-admin-dropdown-link-hover-bg">
            <div class="col-auto">
                <label class="col-form-label">--tiki-admin-dropdown-link-hover-bg</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--tiki-admin-dropdown-link-hover-bg" value="#ffffff" title="Choose your color">
            </div>
        </div>`,
    ],

    "--bs-heading-color": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--bs-heading-color">
            <div class="col-auto">
                <label class="col-form-label">--bs-heading-color</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--bs-heading-color" value="inherit" title="Choose your color">
            </div>
        </div>`,
    ],

    "--bs-link-color": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--bs-link-color">
            <div class="col-auto">
                <label class="col-form-label">--bs-link-color</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--bs-link-color" value="#6ea8fe" title="Choose your color">
            </div>
        </div>`,
    ],
    "--bs-navbar-color":[
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--bs-navbar-color">
            <div class="col-auto">
                <label class="col-form-label">--bs-navbar-color</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--bs-navbar-color" value="#6ea8fe" title="Choose your color">
            </div>
        </div>`,
    ],
    "--bs-navbar-hover-color":[
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--bs-navbar-hover-color">
            <div class="col-auto">
                <label class="col-form-label">--bs-navbar-hover-color</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--bs-navbar-hover-color" value="#6ea8fe" title="Choose your color">
            </div>
        </div>`,
    ],
    "--bs-link-hover-color": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--bs-link-hover-color">
            <div class="col-auto">
                <label class="col-form-label">--bs-link-hover-color</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--bs-link-hover-color" value="#8bb9fe" title="Choose your color">
            </div>
        </div>`,
    ],

    "--bs-code-color": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--bs-code-color">
            <div class="col-auto">
                <label class="col-form-label">--bs-code-color</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--bs-code-color" value="#e685b5" title="Choose your color">
            </div>
        </div>`,
    ],

    "--bs-border-color": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--bs-border-color">
            <div class="col-auto">
                <label class="col-form-label">--bs-border-color</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--bs-border-color" value="#495057" title="Choose your color">
            </div>
        </div>`,
    ],

    "--bs-form-valid-color": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--bs-form-valid-color">
            <div class="col-auto">
                <label class="col-form-label">--bs-form-valid-color</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--bs-form-valid-color" value="#75b798" title="Choose your color">
            </div>
        </div>`,
    ],

    "--bs-form-valid-border-color": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--bs-form-valid-border-color">
            <div class="col-auto">
                <label class="col-form-label">--bs-form-valid-border-color</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--bs-form-valid-border-color" value="#75b798" title="Choose your color">
            </div>
        </div>`,
    ],

    "--bs-form-invalid-color": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--bs-form-invalid-color">
            <div class="col-auto">
                <label class="col-form-label">--bs-form-invalid-color</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--bs-form-invalid-color" value="#ea868f" title="Choose your color">
            </div>
        </div>`,
    ],

    "--bs-form-invalid-border-color": [
        false,
        `<div class="row g-3 align-items-center mb-1" data-css-variable-for="--bs-form-invalid-border-color">
            <div class="col-auto">
                <label class="col-form-label">--bs-form-invalid-border-color</label>
            </div>
            <div class="col-auto">
                <input type="color" class="form-control form-control-color" name="--bs-form-invalid-border-color" value="#ea868f" title="Choose your color">
            </div>
        </div>`,
    ],
};


var default_defined_modes = {}
function sync_color_mode_state(value,id){
    default_defined_modes[id]=value;
}
function add_custom_mode(container){
    
    /* Reset data in the off-canvas */
    $("#cm-action-off-canvas input[name='mode']").val("");
    $("#cm-action-off-canvas input[name='icon']").val("");
    //reset all values
    for (var key in css_vars_list) {
        if (css_vars_list.hasOwnProperty(key)) {
            css_vars_list[key][0] = false;
            let el = $("#cm-modal-content span[data-badge-for='"+key+"']");
            $(el).addClass('text-bg-primary');
            $(el).removeClass('text-bg-secondary');
        }
    }
    $("#cm-modal-content .css_colors").empty();
    /* End */
    $("#cm-action-off-canvas input[name='operation']").val('create');
    $("#cm-action-off-canvas").animate({right:0},500)
    return false;
}

$('#cm-action-off-canvas .close-cm-canvas').on("click", function(){
    $("#cm-action-off-canvas").animate({right:'-100vw'},500);
})

function toggle_input(target){
    $(target).toggleClass('d-none');
}

function cancel_submit(event){
    if (event.keyCode === 13) {
      event.preventDefault();
      $("#cm-save-default").trigger('click');
      return false;
    }
}
function toggle_css_variable(el,css_var_name){
    $(el).toggleClass('text-bg-primary');
    $(el).toggleClass('text-bg-secondary');
    if(css_vars_list[css_var_name][0]){ //remove from list
        css_vars_list[css_var_name][0] = false;
        $("#cm-modal-content .css_colors").find('[data-css-variable-for="' + css_var_name + '"]').remove();
    }
    else{ //add to list
        css_vars_list[css_var_name][0] = true;
        $("#cm-modal-content .css_colors").append(css_vars_list[css_var_name][1]);
        //TODO : scroll to the last element of the css_colors
        var offsetTop = $("#cm-modal-content")[0].scrollHeight;
        $('#cm-action-off-canvas').animate({
            scrollTop: offsetTop
        }, 1000);
    }
}

function save_default_color_mode_icons(el){
    $(el).attr('disabled',true);
    $(el).html('save <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
    $.post("tiki-ajax_services.php",{
        'controller':'colormodes',
        'action':'save_icons_for_default_modes',
        'payload': JSON.stringify(default_defined_modes)
    }).always(function(){
        location.reload();
    })
    return false;
}

function handle_mode_create_edit(el){
    $(el).attr('disabled',true);
    $(el).html('save <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
    let error = $('<div class="error text-danger pt-1"><span></span></div>');
    var mode_name = $("#cm-modal-content input[name='mode']").val().replace(/ /g,"-");
    var mode_icon = $("#cm-modal-content input[name='icon']").val();
    var colors_vars = {};
    
    $("#cm-modal-content input[name='mode']").parent().find('.error').remove();
    $("#cm-modal-content input[name='icon']").parent().find('.error').remove();
    $("#cm-modal-content .error").remove();

    if(mode_name.length<3){
        error.text("Please fill the mode name");
        $("#cm-modal-content input[name='mode']").parent().append(error);
        $(el).attr('disabled',false);
        $(el).html('save');
        $('#cm-action-off-canvas').animate({
            scrollTop: 0
        }, 1000);
        return false;
    }
    
    $("#cm-modal-content input[name='mode']").parent().find('.error').remove();

    if(mode_icon.length<3){
        error.text("Please fill the icon name");
        $("#cm-modal-content input[name='icon']").parent().append(error);
        $(el).attr('disabled',false);
        $(el).html('save');
        $(el).attr('disabled',false);
        $(el).html('save');
        $('#cm-action-off-canvas').animate({
            scrollTop: 0
        }, 1000);
        $(el).attr('disabled',false);
        $(el).html('save');
        return false;
    }

    $("#cm-modal-content input[name='icon']").parent().find('.error').remove();

    $("#cm-modal-content .css_colors input").each(function(){
        colors_vars[$(this).attr('name')]=$(this).val();
    })

    if(Object.keys(colors_vars).length<1){
        error.text("Please customize at least one color");
        $("#cm-modal-content .css_colors").append(error);
        $(el).attr('disabled',false);
        $(el).html('save');
        return false;
    }
    $("#cm-modal-content .error").remove();
    const payload = {
        mode_name,
        mode_icon,
        colors_vars
    };
    if($("#cm-action-off-canvas input[name='operation']").val() == 'create'){
        $.post("tiki-ajax_services.php",{
            'controller':'colormodes',
            'action':'save_new_mode',
            'payload': JSON.stringify(payload)
        }).always(function(){
            location.reload();
        })
    }
    if($("#cm-action-off-canvas input[name='operation']").val() == 'edit'){
        $.post("tiki-ajax_services.php",{
            'controller':'colormodes',
            'action':'edit_mode',
            'payload': JSON.stringify(payload),
            'id' : $("#cm-action-off-canvas input[name='id']").val()
        }).always(function(){
            location.reload();
        })
    }
    return false;
}

function delete_custom_mode(el,id,name){
    var html = $(el).html();
    $(el).attr('disabled',true);
    $(el).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
    $.post("tiki-ajax_services.php",{
        'controller':'colormodes',
        'action':'delete_mode',
        'payload' : [name,id],
    }).always(function(){
        location.reload();
    })
    return false;
}

function edit_custom_mode(el,id,name,icon){
    /* Reset data in the off-canvas */
    $("#cm-action-off-canvas input[name='mode']").val("");
    $("#cm-action-off-canvas input[name='icon']").val("");
    //reset all values
    for (var key in css_vars_list) {
        if (css_vars_list.hasOwnProperty(key)) {
            css_vars_list[key][0] = false;
            let el = $("#cm-modal-content span[data-badge-for='"+key+"']");
            $(el).addClass('text-bg-primary');
            $(el).removeClass('text-bg-secondary');
        }
    }
    $("#cm-modal-content .css_colors").empty();
    /* End */
    $("#cm-action-off-canvas input[name='operation']").val('edit');
    $("#cm-action-off-canvas input[name='id']").val(id);
    $("#cm-action-off-canvas input[name='mode']").val(name);
    $("#cm-action-off-canvas input[name='icon']").val(icon);

    var parent = $(el).parent().parent();
    var name = parent.data('mode-name');
    var iconame = parent.data('mode-icon');
    var match = parent.find('code').html().match(/\{([\s\S]+?)\}/);
    var raw = "";
    var css_vars = {};
    for (var key in css_vars_list) { //reset all values
        if (css_vars_list.hasOwnProperty(key)) {
            css_vars_list[key][0] = false;
        }
    }
    if(match){
        raw=match[1];
        raw.split(';').forEach(function(pair) {
            var keyValue = pair.trim().split(':');
            if (keyValue.length === 2) {
                var key = keyValue[0].trim();
                var value = keyValue[1].trim();
                css_vars[key] = value; //hold info about the object created after parsing css vars from DB, currently not used
                let el = $("#cm-modal-content span[data-badge-for='"+key+"']");
                $(el).toggleClass('text-bg-primary');
                $(el).toggleClass('text-bg-secondary');
                css_vars_list[$(el).data('badge-for')][0] = true;
                let input_markup = $(css_vars_list[$(el).data('badge-for')][1]);
                input_markup.find('input').val(value);
                $("#cm-modal-content .css_colors").append(input_markup);
            }
        });
    }
    $("#cm-action-off-canvas").animate({right:0},500); //open the off-canvas
}

\$(function() {
    $("input[data-icon-name-for]").each(function(){
        sync_color_mode_state($(this).val(),$(this).attr('data-icon-name-for'));
    })
    var setupThemeSelects = function (themeDropDown, optionDropDown, showPreview) {
        // pick up theme drop-down change
        themeDropDown.on("change", function() {
            var ops = theme_options[themeDropDown.val()];
            var none = true;
            var current = optionDropDown.val();
            optionDropDown.empty().attr('disabled',false)
                    .append(\$('<option/>').attr('value','').text($none));
            if (themeDropDown.val()) {
                \$.each(ops[1], function(i, val) {
                    optionDropDown.append(\$('<option/>').attr('value',i).text(i));
                    none = false;
                });
            }
            optionDropDown.val(current);
            if (!optionDropDown.val()){
                optionDropDown.val('');
            }

            if (none) {
                optionDropDown.attr('disabled',true);
            }
            optionDropDown.trigger("change");
            if (jqueryTiki.select2) {
                optionDropDown.trigger("change:select2");
            }
        }).trigger("change");
        optionDropDown.on("change", function() {
            if (showPreview !== undefined) {
                var t = themeDropDown.val();
                var o = optionDropDown.val();
                var f = theme_options[t][1][o];

                if ( ! f ) {
                    f = theme_options[t][0];
                }

                if (f) {
                    \$('#theme_thumb').fadeOut('fast').attr('src', f).fadeIn('fast').animate({'opacity': 1}, 'fast');
                } else {
                    \$('#theme_thumb').animate({'opacity': 0.3}, 'fast');
                }
            }
        });
    };

    setupThemeSelects(\$('.tab-content select[name=theme]'), \$('.tab-content select[name=theme_option]'), true);
    setupThemeSelects(\$('.tab-content select[name=theme_admin]'), \$('.tab-content select[name=theme_option_admin]'));

    var setupThemeLayouts = function (themeDropDown, optionDropDown, layoutDropDown) {
        themeDropDown,optionDropDown.on("change", function() {
            var theme_name = themeDropDown.val();
            if (optionDropDown.val()){
                theme_name += ":" + optionDropDown.val();
            }
            var layouts = theme_layouts[theme_name];
            var current = layoutDropDown.val();
            layoutDropDown.empty();
            if (!theme_name){
                layoutDropDown.append(\$('<option/>').attr('value','').text('Site layout'));
                layoutDropDown.attr('disabled',true);
            } else {
                layoutDropDown.attr('disabled',false);
                \$.each(layouts, function(i, val) {
                    layoutDropDown.append(\$('<option/>').attr('value',i).text(val));
                });

                //try setting the option to the previously selected option and if no layout matched, set to 'basic'
                layoutDropDown.val(current);
                if (!layoutDropDown.val()){
                    layoutDropDown.val('basic');
                }
            }
            layoutDropDown.trigger("change");

        }).trigger("change");
    };

    setupThemeLayouts(\$('.tab-content select[name=theme]'), \$('.tab-content select[name=theme_option]'), \$('.tab-content select[name=site_layout]') );
    setupThemeLayouts(\$('.tab-content select[name=theme_admin]'), \$('.tab-content select[name=theme_option_admin]'), \$('.tab-content select[name=site_layout_admin]') );
});
JS
    );
}
