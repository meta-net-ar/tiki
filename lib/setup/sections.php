<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//this script may only be included - so its better to die if called directly.
if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    header('location: index.php');
    exit;
}

use Tiki\Sections;

$sections = Sections::getSections();
$headerlib = TikiLib::lib('header');

if (! isset($section)) {
    $section = '';
}

$sections_enabled = [];

foreach ($sections as $sec => $dat) {
    $feat = $dat['feature'];
    if ($feat === '' or (isset($prefs[$feat]) and $prefs[$feat] == 'y')) {
        $sections_enabled[$sec] = $dat;
    }
}

ksort($sections_enabled);
$smarty->assign_by_ref('sections_enabled', $sections_enabled);
if (! empty($section)) {
    $smarty->assign('section', $section);
}

if (! empty($section_class)) {
    $smarty->assign('section_class', $section_class);
} elseif (! empty($section)) {
    $section_class = 'tiki_' . str_replace(' ', '_', $section);
    $smarty->assign('section_class', $section_class);
}



// include UAB admin CSS and layout in case we are on an admin or management page (when script file name contains the string)
// This code used to be in lib/setup/theme.php, but is more related to sections than themes.
$groups = $userlib->get_user_groups($user);

if (
    $prefs['theme_unified_admin_backend'] === 'y' && in_array('Admins', $groups)
    && (strpos($_SERVER['PHP_SELF'], 'admin')
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
        || strpos($_SERVER['PHP_SELF'], 'tiki-sys'))
) { // TODO: refactor this check into an array of all admin and management pages we want to include and the related perms to access in UAB layout
    $headerlib->add_cssfile('themes/base_files/css/feature/adminui.css');

    if (! str_contains($_SERVER['PHP_SELF'], 'tiki-admin.php') && ! str_contains($_SERVER['PHP_SELF'], 'tiki-admin_modules.php') && strpos($_SERVER['PHP_SELF'], 'tiki-admin_tracker_fields.php') === false) { // Exclude tiki-admin.php, tiki-admin_tracker_fields.php, and the modules admin here
        /* Force the admin layout on admin pages */
        $prefs['site_layout_admin'] = 'admin';
        /* Force the admin layout on setup/management pages too */
        $prefs['site_layout'] = 'admin';
        /* Set the section to "admin" to display only the UAB specific modules (defined in lib/modules/modlib.php) */
        Sections::setCurrentSection(Sections::SECTION_ADMIN_LAYOUT);
        include_once 'admin/define_admin_icons.php';

        foreach ($admin_icons as &$admin_icon) {
            foreach ($admin_icon['children'] as &$child) {
                $child = array_merge(['disabled' => false, 'description' => ''], $child);
            }
        }
        $smarty->assign('admin_icons', $admin_icons);
    }
    if (! str_contains($_SERVER['PHP_SELF'], 'tiki-admin_modules.php')) { // Exclude the modules admin here
        $smarty->assign('navbar_color_variant', $prefs['theme_navbar_color_variant_admin']);
    }
} else {
    $smarty->assign('navbar_color_variant', $prefs['theme_navbar_color_variant']);
}

function current_object()
{
    return Sections::currentObject();
}
