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
        'staticKeyFilters'         => [
             'menuId'               => 'int',            //get
             'name'                 => 'text',           //post
             'find'                 => 'striptags',      //post
             'offset'               => 'int',            //get
             'sort_mode'            => 'alnumdash',      //get
             'remove'               => 'int',            //post
             'save'                 => 'alpha',          //post
             'icon'                 => 'text',           //post
             'use_items_icons'      => 'alpha',          //post
             'parse'                => 'alpha',          //post
             'description'          => 'xss',            //post
             'type'                 => 'string',         //post
             'reset'                => 'alpha',          //get
        ],
    ],
];
require_once('tiki-setup.php');
use Tiki\Sections;
$section = Sections::SECTION_ADMIN_LAYOUT;
Sections::setCurrentSection($section);
$menulib = TikiLib::lib('menu');
$auto_query_args = [
    'offset',
    'sort_mode',
    'menuId'
];
$access->check_permission(['tiki_p_edit_menu']);

if (! isset($_REQUEST['menuId'])) {
    $_REQUEST['menuId'] = 0;
}
$smarty->assign('menuId', $_REQUEST['menuId']);

if ($_REQUEST['menuId']) {
    $info = $menulib->get_menu($_REQUEST['menuId']);
} else {
    $info = [];
    $info['name'] = '';
    $info['description'] = '';
    $info['type'] = 'd';
    $info['icon'] = null;
    $info['use_items_icons'] = 'n';
    $info['parse'] = 'n';
}
$smarty->assign_by_ref('info', $info);

if (isset($_REQUEST['remove']) && $access->checkCsrf(true)) {
    $menulib->remove_menu($_REQUEST['remove']);
}

if (isset($_REQUEST['save']) && $access->checkCsrf()) {
    if (! isset($_REQUEST['icon'])) {
        $_REQUEST['icon'] = null;
    }
    $_REQUEST['use_items_icons'] = (isset($_REQUEST['use_items_icons']) && $_REQUEST['use_items_icons'] == 'on') ? 'y' : 'n';
    $_REQUEST['parse'] = (isset($_REQUEST['parse']) && $_REQUEST['parse'] == 'on') ? 'y' : 'n';
    $menulib->replace_menu($_REQUEST['menuId'], $_REQUEST['name'], $_REQUEST['description'], $_REQUEST['type'], $_REQUEST['icon'], $_REQUEST['use_items_icons'], $_REQUEST['parse']);
    $_REQUEST['menuId'] = 0;
    $smarty->assign('menuId', 0);
    $smarty->assign(
        'info',
        [
            'name' => '',
            'description' => '',
            'type' => 'd',
            'icon' => null,
            'use_items_icons' => 'n',
            'parse' => 'n',
        ]
    );
}

if (
    isset($_REQUEST['reset'])
    && $access->checkCsrf(true)
) {
    $menulib->reset_app_menu();
}

if (! isset($_REQUEST['sort_mode'])) {
    $sort_mode = 'name_desc';
} else {
    $sort_mode = $_REQUEST['sort_mode'];
}
$smarty->assign_by_ref('sort_mode', $sort_mode);

if (! isset($_REQUEST['offset'])) {
    $offset = 0;
} else {
    $offset = $_REQUEST['offset'];
}
$smarty->assign_by_ref('offset', $offset);

$find = $_REQUEST['find'] ?? '';
$smarty->assign('find', $find);

$channels = $menulib->list_menus($offset, $maxRecords, $sort_mode, $find);
foreach ($channels['data'] as $i => $channel) {
    if ($userlib->object_has_one_permission($channel['menuId'], 'menus')) {
        $channels['data'][$i]['individual'] = 'y';
    }
}
$smarty->assign_by_ref('count', $channels['count']);
$smarty->assign_by_ref('channels', $channels['data']);

$modlib = TikiLib::lib('mod');
$assigned_modules = $modlib->get_assigned_modules();
$module_assign_id = null;

foreach ($assigned_modules as $zone => $modules) {
    foreach ($modules as $module) {
        if (isset($module['name']) && strtolower($module['name']) === 'menu') {
            $module_assign_id = $module['moduleId'];
            break 2;
        }
    }
}

$smarty->assign('module_assign_id', $module_assign_id);

// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
// Display the template
$smarty->assign('mid', 'tiki-admin_menus.tpl');
$smarty->display('tiki.tpl');
