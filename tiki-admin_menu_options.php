<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$inputConfiguration = [
    [
        'staticKeyFilters'    => [
            'menuId'             => 'int',        //get
            'deletemenu'         => 'int',        //post
            'preview_type'       => 'striptags',  //post
            'preview_bootstrap'  => 'alpha',      //post
            'find'               => 'striptags',  //get
            'sort_mode'          => 'alnumdash', //get
            'offset'             => 'int',        //get
        ],
    ],
];
require_once('tiki-setup.php');
use Tiki\Sections;
$section = Sections::SECTION_ADMIN_LAYOUT;
Sections::setCurrentSection($section);
$menulib = TikiLib::lib('menu');
$access->check_permission(['tiki_p_edit_menu_option']);
if (! isset($_REQUEST["menuId"])) {
    Feedback::errorAndDie(tra("No menu indicated"), \Laminas\Http\Response::STATUS_CODE_409);
}
$auto_query_args = [
    'menuId',
    'preview_css',
    'preview_type',
    'preview_bootstrap',
];

if (! empty($_REQUEST["deletemenu"]) && $access->checkCsrf()) {
    $optionId = (int) $_REQUEST["deletemenu"];
    if ($optionId > 0) {
        // Verify the option exists and belongs to the current menu
        $option = $menulib->get_menu_option($optionId);
        if ($option && $option['menuId'] == $_REQUEST["menuId"]) {
            $menulib->remove_menu_option($optionId);
        }
    }
}

$smarty->assign('menuId', $_REQUEST["menuId"]);
$editable_menu_info = $menulib->get_menu($_REQUEST["menuId"]);
$smarty->assign('editable_menu_info', $editable_menu_info);


$smarty->assign('preview_type', isset($_REQUEST['preview_type']) && $_REQUEST['preview_type'] === 'horiz' ? 'horiz' : 'vert');
$smarty->assign('preview_css', isset($_REQUEST['preview_css']) && $_REQUEST['preview_css'] === 'On' ? 'y' : 'n');

$headerlib->add_js('var permNames = ' . json_encode(TikiLib::lib('user')->get_permission_names_for('all')) . ';');
$feature_prefs = [];
foreach ($prefs as $k => $v) {  // attempt to filter out non-feature prefs (still finds 133!)
    if (str_contains($k, 'feature') && preg_match_all('/_/m', $k, $m) === 1) {
        $feature_prefs[] = $k;
    }
}
$headerlib->add_js('var prefNames = ' . json_encode($feature_prefs) . ';');

$options = $menulib->list_menu_options($_REQUEST["menuId"], 0, -1, 'position_asc', '', true, 0, true, true);
$smarty->assign_by_ref('no_editable_page', $options["no_editable_page"]);
$options = $menulib->prepare_options_for_editing($options);
$smarty->assign_by_ref('pages_count', $options["count"]);
$smarty->assign_by_ref('options', $options["data"]);
if (isset($info['groupname']) && ! is_array($info['groupname'])) {
    $info['groupname'] = explode(',', $info['groupname']);
}
$all_groups = $userlib->list_all_groups();
if (is_array($all_groups)) {
    foreach ($all_groups as $g) {
        if (isset($info) && isset($info['groupname'])) {
            $option_groups[$g] = (is_array($info['groupname']) && in_array($g, $info['groupname'])) ? 'selected="selected"' : '';
        } else {
            $option_groups[$g] = '';
        }
    }
}
$smarty->assign_by_ref('option_groups', $option_groups);
$smarty->assign('preview_type', 'vert');
$smarty->assign('preview_css', 'n');
$smarty->assign('preview_bootstrap', 'y');
$module_zones = [];
foreach (TikiLib::lib('mod')->module_zones as $initial => $zone) {
    $module_zones[$initial] = [
        'id' => $zone,
        'name' => tra(substr($zone, 0, strpos($zone, '_'))),
    ];
}
$smarty->assign('module_zone_list', $module_zones);

$headerlib->add_js_module('import "@jquery-tiki/tiki-admin_menu_options";');
$headerlib->add_jsfile('lib/modules/tiki-admin_modules.js');

// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
// Display the template
$smarty->assign('mid', 'tiki-admin_menu_options.tpl');
$smarty->display("tiki.tpl");
