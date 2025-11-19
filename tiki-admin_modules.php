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
            'wysiwyg'              => 'bool',        //post
            'clear_cache'          => 'alpha',       //post
            'edit_assign'          => 'int',         //get
            'preview'              => 'alpha',       //post
            'unassign_module_id'   => 'int',        //post
            'modup'                => 'int',         //post
            'moddown'              => 'int',         //post
            'module-order'         => 'text',        //post
            'um_update'            => 'text',        //post
            'um_name'              => 'text',        //post
            'um_data'              => 'none',        //post
            'um_title'             => 'text',        //post
            'um_parse'             => 'alpha',       //post
            'um_tgt_module'        => 'text',        //post
            'assign'               => 'alpha',       //post
            'assign_name'          => 'text',        //post
            'assign_rows'          => 'int',         //post
            'assign_position'      => 'alpha',       //post
            'assign_order'         => 'digits',      //post
            'assign_cache'         => 'digits',      //post
            'assign_type'          => 'alpha',       //post
            'um_remove'            => 'text',        //post
            'um_edit'              => 'text',        //get
            'module_list_show_all' => 'bool',        //post
            'offset'               => 'int',         //get
            'edit_module'          => 'bool',        //post
            'moduleId'             => 'int',         //post
            'menuId'               => 'int',         //get
            'sort_mode'            => 'alnumdash',   //get
            'find'                 => 'text',        //get
        ],'staticKeyFiltersForArrays' => [
            'assign_params'        => 'striptags',   //post
            'groups'               => 'groupname',   //post
        ],
    ],
];

$section = 'admin';
require_once 'tiki-setup.php';

$dcslib = TikiLib::lib('dcs');
$bannerlib = TikiLib::lib('banner');
$rsslib = TikiLib::lib('rss');
$polllib = TikiLib::lib('poll');
$structlib = TikiLib::lib('struct');
$modlib = TikiLib::lib('mod');
$menulib = TikiLib::lib('menu');
$tikilib = TikiLib::lib('tiki');

$userHasAssignedModules = $prefs['user_assigned_modules'] === 'y'
    && TikiLib::lib('usermodules')->user_has_assigned_modules($user);

$smarty->assign('wysiwyg', 'n');
if (isset($_REQUEST['wysiwyg']) && $_REQUEST['wysiwyg'] == 'y') {
    $smarty->assign('wysiwyg', 'y');
}

$access->check_permission(['tiki_p_admin_modules']);
$auto_query_args = ['show_hidden_modules'];

if (! empty($prefs['module_file'])) {
    $access->display_error(
        '',
        tr(
            'Module file in use. You need to edit %0 to configure your modules.',
            $tiki_p_admin === 'y' ? $prefs['module_file'] : basename($prefs['module_file'])
        )
    );
}

// Values for the user_module edit/create form
$smarty->assign('um_tgt_module', '');
$smarty->assign('um_name', '');
$smarty->assign('um_title', '');
$smarty->assign('um_data', '');
$smarty->assign('um_parse', '');
$smarty->assign('assign_name', '');
//$smarty->assign('assign_title','');
$smarty->assign('assign_position', '');
$smarty->assign('assign_order', '');
$smarty->assign('assign_cache', 0);
$smarty->assign('assign_rows', 10);
$smarty->assign('assign_params', '');
if (isset($_REQUEST['clear_cache']) && $access->checkCsrf()) {
    $result = $modlib->clear_cache();
    Feedback::note(tr('%0 cache files cleared', $result));
}
$module_groups = [];
$smarty->assign('assign_selected', '');
$smarty->assign('assign_type', '');
$smarty->assign('assign_title', '');

if (! empty($_REQUEST['edit_assign'])) {
    $info = $modlib->get_assigned_module($_REQUEST['edit_assign']);
    $grps = '';
    if (! empty($info['groups'])) {
        $module_groups = unserialize($info['groups']);
        foreach ($module_groups as $amodule) {
            $grps = $grps . ' $amodule ';
        }
    }
    $smarty->assign('module_groups', $grps);
    if (isset($info['ord'])) {
        $cosa = '' . $info['ord'];
    } else {
        $cosa = '';
    }
    $smarty->assign_by_ref('assign_name', $info['name']);
    //$smarty->assign_by_ref('assign_title',$info['title']);
    $smarty->assign_by_ref('assign_position', $info['position']);
    $smarty->assign_by_ref('assign_cache', $info['cache_time']);
    $smarty->assign_by_ref('assign_type', $info['type']);
    $smarty->assign_by_ref('assign_order', $cosa);
    $smarty->assign_by_ref('info', $info);
    if (! $info['name']) {
        $smarty->assign('assign_selected', $_REQUEST['edit_assign']);
    }

    if (! empty($_REQUEST['menuId']) && $info['name'] === 'menu') {
        $params = [];
        if (! empty($info['params'])) {
            parse_str($info['params'], $params);
        }
        $params['id'] = $_REQUEST['menuId'];
        $info['params'] = http_build_query($params, '', '&');
    }

    $modinfo = $modlib->get_module_info($info['name']);
    if ($modinfo['type'] != 'function') {
        $smarty->assign_by_ref('assign_rows', $info['rows']);
        $smarty->assign_by_ref('assign_params', $info['params']); // For old-style (user) modules
    } else {
        if (empty($info['params'])) {
            $info['params'] = [];
        }
        $modlib->dispatchValues($info['params'], $modinfo['params']);
        if (isset($modinfo['params']['rows'])) {
            $modinfo['params']['rows']['value'] = $info['rows'];
        }
    }
    $smarty->assign('assign_info', $modinfo);
}
//post is used for preview because there is another submit item on the form requiring it
//and using get for just the preview element would result in exposing the ticket
//non-state-changing-action
if (isset($_REQUEST['edit_assign']) || isset($_REQUEST['preview'])) {   // will be 0 for a new assignment
    $cookietab = 2;
}

if (! empty($_REQUEST['unassign_module_id']) && $access->checkCsrf()) {
    $info = $modlib->get_assigned_module($_REQUEST['unassign_module_id']);
    $result = $modlib->unassign_module($_REQUEST['unassign_module_id']);
    if ($result) {
        Feedback::success(tr('Module unassigned'));
        $logslib->add_log('adminmodules', 'unassigned module ' . $info['name']);
    } else {
        Feedback::error(tr('Module not unassigned'));
    }
}
$addonMsg = ' ' . tr('Displayed order may not change if other modules now have the same order rank.')
    . $userHasAssignedModules ? ' '
    . tr(
        'Also, displayed order may not change for you since you have assigned a custom order for modules %0here%1',
        '<a href="tiki-user_assigned_modules.php">',
        '</a>'
    ) : '';

if (! empty($_REQUEST['modup']) && $access->checkCsrf()) {
    $result = $modlib->module_up($_POST['modup']);
    if ($result && $result->numRows()) {
        Feedback::success(tr('Standard module display order moved up.') . $addonMsg);
    } else {
        Feedback::error(tr('Standard module display order not moved up'));
    }
}

if (! empty($_REQUEST['moddown']) && $access->checkCsrf()) {
    $result = $modlib->module_down($_POST['moddown']);
    if ($result && $result->numRows()) {
        Feedback::success(tr('Standard module display order moved down.') . $addonMsg);
    } else {
        Feedback::error(tr('Standard module display order not moved down'));
    }
}

if (! empty($_REQUEST['module-order']) && $access->checkCsrf()) {
    $module_order = json_decode($_REQUEST['module-order']);
    $result = $modlib->reorder_modules($module_order);
    if ($result) {
        Feedback::success(tr('Standard display of modules reordered or moved.') . $addonMsg);
    } else {
        $msg = tr('Standard display of modules not reordered or moved.');
        $msg .= $userHasAssignedModules ? ' '
            . tr(
                'If you receive this error after attempting more than once to move a module, it may be because the standard display has already been changed but it is not visible to you because you have assigned a custom order for modules %0here%1.',
                '<a href="tiki-user_assigned_modules.php">',
                '</a>'
            ) : '';
        Feedback::error($msg);
    }
}

/* Edit or delete a user module */
if (isset($_REQUEST['um_update']) && $access->checkCsrf()) {
    if (empty($_REQUEST['um_name'])) {
        Feedback::errorPage(tr('Cannot create or update module: You need to specify a name for the module'));
    }
    if (empty($_REQUEST['um_data'])) {
        Feedback::errorPage(tr('Cannot create or update module: You cannot leave the data field empty'));
    }
    if ($_REQUEST['um_update'] == tra('Create') && in_array(strtolower($_REQUEST['um_name']), $modlib->get_all_modules())) {
        Feedback::errorPage(tr('A module with that "name" already exists, please choose another'));
    }
    $_REQUEST['um_update'] = urldecode($_REQUEST['um_update']);
    $um_title = $_REQUEST['um_title'];
    $um_data = $_REQUEST['um_data'];
    $um_parse = $_REQUEST['um_parse'];

    $result = $modlib->replace_user_module($_REQUEST['um_name'], $_REQUEST['um_title'], $_REQUEST['um_data'], $_REQUEST['um_parse'], $_REQUEST['um_tgt_module']);
    if ($result && $result->numRows()) {
        $msg = $_REQUEST['um_update'] == tr('Create') ? tr('Custom module created') : tr('Custom module modified');
        $um_title = '';
        $um_data = '';
        $um_parse = '';
        $logslib->add_log('adminmodules', 'changed custom module ' . $_REQUEST['um_name']);
        Feedback::success($msg);
    } else {
        $smarty->assign_by_ref('um_tgt_module', $_REQUEST['um_tgt_module']);
        $smarty->assign_by_ref('um_name', $_REQUEST['um_name']);

        $msg = $_REQUEST['um_update'] == tr('Create') ? tr('Custom module not created') : tr('Custom module not modified');
        Feedback::error($msg);
    }
    $smarty->assign_by_ref('um_title', $um_title);
    $smarty->assign_by_ref('um_data', $um_data);
    $smarty->assign_by_ref('um_parse', $um_parse);
}
//post is used for preview because there is another submit item on the form requiring it
//and using get for just the preview element would result in exposing the ticket
//non-state-changing-action
if (isset($_REQUEST['assign']) || isset($_REQUEST['preview'])) {
    // Verify that required parameters are present
    $missing_params = [];
    $modinfo = $modlib->get_module_info($_REQUEST['assign_name']);
    if (isset($_REQUEST['moduleId']) && $_REQUEST['moduleId'] > 0) {
        foreach ($modinfo['params'] as $pname => $param) {
            if ($param['required'] && empty($_REQUEST['assign_params'][$pname])) {
                $missing_params[] = $param['name'];
            }
        }
    }
    $smarty->assign('missing_params', $missing_params);
}

$smarty->assign('preview', 'n');
//post is used for preview because there is another submit item on the form requiring it
//and using get for just the preview element would result in exposing the ticket
//non-state-changing-action
if (isset($_REQUEST['preview'])) {
    $smarty->assign('preview', 'y');
    $smarty->assign_by_ref('assign_name', $_REQUEST['assign_name']);
    if (! isset($_REQUEST['assign_params'])) {
        $module_params = [];
    } elseif (! is_array($_REQUEST['assign_params'])) {
        parse_str($_REQUEST['assign_params'], $module_params);
    } else {
        $module_params = $_REQUEST['assign_params'];
    }
    $smarty->assign_by_ref('module_params', $module_params);
    if (isset($module_params['title'])) {
        $smarty->assign('tpl_module_title', tra($module_params['title']));
    }

    if (isset($_REQUEST['assign_rows'])) {
        $module_rows = $_REQUEST['assign_rows'];
        $smarty->assign_by_ref('assign_rows', $_REQUEST['assign_rows']);
    } elseif (isset($_REQUEST['assign_params']['rows'])) {
        $module_rows = $_REQUEST['assign_params']['rows'];
    } else {
        $module_rows = 10;
    }

    if ($modlib->is_user_module($_REQUEST['assign_name'])) {
        $info = $modlib->get_user_module($_REQUEST['assign_name']);
        $smarty->assign_by_ref('user_title', $info['title']);

        $infoParsed = $modlib->parse($info);

        $smarty->assign_by_ref('user_data', $infoParsed['data']);

        try {
            $data = $smarty->fetch('modules/user_module.tpl');
        } catch (Exception $e) {
            Feedback::errorAndDie(
                tr(
                    'There is a problem with your custom module "%0": ' . '<br><br><em>' . $e->getMessage()
                    . '</em><br><br>' . '<span class="button"><a href="tiki-admin_modules.php?um_edit='
                    . $_REQUEST['assign_name'] . '&cookietab=2#editcreate">' . tr('Click here to edit the module')
                    . '</a></span>',
                    $_REQUEST['assign_name']
                ),
                \Laminas\Http\Response::STATUS_CODE_409
            );
        }
    } else {
        $phpfile = 'modules/mod-' . $_REQUEST['assign_name'] . '.php';
        $phpfuncfile = 'modules/mod-func-' . $_REQUEST['assign_name'] . '.php';
        $template = 'modules/mod-' . $_REQUEST['assign_name'] . '.tpl';
        if ($file = $tikilib->safeFileExistsInPath($phpfile, MODULES_PATH)) {
            include $file;
        } elseif ($file = $tikilib->safeFileExistsInPath($phpfuncfile, MODULES_PATH)) {
            if (isset($_REQUEST['assign_params']['rows'])) {
                $module_rows = $_REQUEST['assign_params']['rows'];
            } else {
                $module_rows = 10;
            }
            include_once $file;
            $function = 'module_' . $_REQUEST['assign_name'];
            $assign_param = $_REQUEST['assign_params'] ?? [];
            if (function_exists($function)) {
                $function(
                    [
                        'name' => $_REQUEST['assign_name'],
                        'position' => $_REQUEST['assign_position'] ?? '',
                        'ord' => $_REQUEST['assign_order'] ?? '',
                        'cache_time' => $_REQUEST['assign_cache'] ?? '',
                        'rows' => $module_rows
                    ],
                    $assign_param
                ); // Warning: First argument should have all tiki_modules table fields. This is just a best effort.
            }
        }

        if ($file = $tikilib->safeFileExistsInPath('templates/' . $template, TEMPLATES_MODULES_PATH)) {
            $data = $smarty->fetch($file);
        } else {
            $data = '';
        }
    }
    if (! empty($_REQUEST['moduleId'])) {
        $smarty->assign('moduleId', $_REQUEST['moduleId']);
    } else {
        $smarty->assign('moduleId', 0);
    }
    $smarty->assign_by_ref('assign_name', $_REQUEST['assign_name']);
    $smarty->assign_by_ref('assign_params', $_REQUEST['assign_params']);
    $smarty->assign_by_ref('assign_position', $_REQUEST['assign_position']);
    $smarty->assign_by_ref('assign_order', $_REQUEST['assign_order']);
    $smarty->assign_by_ref('assign_cache', $_REQUEST['assign_cache']);
    $grps = '';
    $module_groups = $_REQUEST['groups'] ?? [];
    foreach ($module_groups as $amodule) {
        $grps = $grps . ' $amodule ';
    }
    $smarty->assign('module_groups', $grps);
    $smarty->assign_by_ref('preview_data', $data);

    $modlib->dispatchValues($_REQUEST['assign_params'], $modinfo['params']);
    $smarty->assign('assign_info', $modinfo);
}

if (isset($_REQUEST['assign']) && $access->checkCsrf()) {
    $assign_name = urldecode($_REQUEST['assign_name']);
    $smarty->assign_by_ref('assign_name', $assign_name);
    $smarty->assign_by_ref('assign_position', $_REQUEST['assign_position']);
    $smarty->assign_by_ref('assign_params', $_REQUEST['assign_params']);
    $smarty->assign_by_ref('assign_order', $_REQUEST['assign_order']);
    $smarty->assign_by_ref('assign_cache', $_REQUEST['assign_cache']);

    if (isset($_REQUEST['assign_rows'])) {
        $module_rows = $_REQUEST['assign_rows'];
        $smarty->assign_by_ref('assign_rows', $_REQUEST['assign_rows']);
    } elseif (isset($_REQUEST['assign_params']['rows'])) {
        $module_rows = $_REQUEST['assign_params']['rows'];
        unset($_REQUEST['assign_params']['rows']); // hack, since rows goes in its own DB field
    } else {
        $module_rows = 10;
    }
    $smarty->assign_by_ref('assign_type', $_REQUEST['assign_type']);
    $grps = '';
    $module_groups = $_REQUEST['groups'] ?? [];
    foreach ($module_groups as $amodule) {
        $grps = $grps . " $amodule ";
    }
    $smarty->assign('module_groups', $grps);
    if (empty($missing_params)) {
        $result = $modlib->assign_module(
            $_REQUEST['moduleId'] ?? 0,
            $assign_name,
            '',
            $_REQUEST['assign_position'],
            $_REQUEST['assign_order'],
            $_REQUEST['assign_cache'],
            $module_rows,
            serialize($module_groups),
            $_REQUEST['assign_params'],
            $_REQUEST['assign_type']
        );
        $logslib->add_log('adminmodules', 'assigned module ' . $assign_name);
        $modlib->reorder_modules();
        if ($result) {
            Feedback::success(tr('Module assigned'));
        } else {
            Feedback::error(tr('Module not assigned'));
        }
        $access->redirect('tiki-admin_modules.php?cookietab=1');  // forcing return to 1st tab
    } else {
        $modlib->dispatchValues($_REQUEST['assign_params'], $modinfo['params']);
        $smarty->assign('assign_info', $modinfo);
    }
}

if (isset($_REQUEST['um_remove']) && $access->checkCsrf(true)) {
    $result = $modlib->remove_user_module($_REQUEST['um_remove']);
    if ($result && $result->numRows()) {
        Feedback::success(tr('Custom module deleted'));
    } else {
        Feedback::error(tr('Custom module not deleted'));
    }
    $logslib->add_log('adminmodules', 'removed custom module ' . $_REQUEST['um_remove']);
    $cookietab = 1;
}

if (isset($_REQUEST['um_edit'])) {
    $um_edit = urldecode($_REQUEST['um_edit']);
    $um_info = $modlib->get_user_module($um_edit);
    $smarty->assign('um_tgt_module', $um_info['name']);
    $smarty->assign('um_name', $um_info['name']);
    $smarty->assign('um_title', $um_info['title']);
    $smarty->assign('um_data', $um_info['data']);
    $smarty->assign('um_parse', $um_info['parse']);
}
$user_modules = $modlib->list_user_modules();
$smarty->assign('user_modules', $user_modules['data']);

$all_modules = $modlib->get_all_modules();
sort($all_modules);
$smarty->assign('all_modules', $all_modules);

$all_modules_info = array_combine(
    $all_modules,
    array_map([ $modlib, 'get_module_info' ], $all_modules)
);

foreach ($all_modules_info as &$mod) {
    $mod['enabled'] = true;
    foreach ($mod['prefs'] as $pf) {
        if ($prefs[$pf] !== 'y') {
            $mod['enabled'] = false;
        }
    }
}

uasort($all_modules_info, 'compare_names');
$smarty->assign_by_ref('all_modules_info', $all_modules_info);
$smarty->assign('module_list_show_all', ! empty($_REQUEST['module_list_show_all']));

$smarty->assign('orders', range(1, 50));
$groups = $userlib->list_all_groups();
$allgroups = [];
$temp_max = count($groups);
foreach ($groups as $groupName) {
    $allgroups[] = [
        'groupName' => $groupName,
        'selected' => in_array($groupName, $module_groups) ? 'y' : 'n',
    ];
}

$smarty->assign('groups', $allgroups);

if (! isset($_REQUEST['offset'])) {
    $offset = 0;
} else {
    $offset = $_REQUEST['offset'];
}
$maximum = 0;
$maxRecords = $prefs['maxRecords'];

$polls = $polllib->list_active_polls($offset, $maxRecords, 'publishDate_desc', '');
$smarty->assign('polls', $polls['data']);
$maximum = max($maximum, $polls['count']);

$contents = $dcslib->list_content($offset, $maxRecords, 'contentId_desc', '');
$smarty->assign('contents', $contents['data']);
$maximum = max($maximum, $contents['count']);

$rsss = $rsslib->list_rss_modules($offset, $maxRecords, 'name_desc', '');
$smarty->assign('rsss', $rsss['data']);
$maximum = max($maximum, $rsss['count']);

$menus = $menulib->list_menus($offset, $maxRecords, 'menuId_desc', '');
$smarty->assign('menus', $menus['data']);
$maximum = max($maximum, $menus['count']);

$banners = $bannerlib->list_zones();
$smarty->assign('banners', $banners['data']);
$maximum = max($maximum, $banners['count']);

$wikistructures = $structlib->list_structures('0', '100', 'pageName_asc', '');
$smarty->assign('wikistructures', $wikistructures['data']);
$maximum = max($maximum, $wikistructures['count']);

$smarty->assign('maxRecords', $maxRecords);
$smarty->assign('offset', $offset);
$smarty->assign('maximum', $maximum);

$assigned_modules = $modlib->get_assigned_modules();
$module_zones = [];
foreach ($modlib->module_zones as $initial => $zone) {
    $module_zones[$initial] = [
            'id' => $zone,
            'name' => tra(substr($zone, 0, strpos($zone, '_')))
            ];
}

$assigned_modules = array_map(
    function ($list) {
        return array_map(
            function ($entry) {
                $entry['params_presentable'] = str_replace('&', '<br>', urldecode($entry['params']));
                return $entry;
            },
            $list
        );
    },
    $assigned_modules
);

$smarty->assign('assigned_modules', $assigned_modules);
$smarty->assign('module_zone_list', $module_zones);
$smarty->assign('userHasAssignedModules', $userHasAssignedModules);

$prefs['module_zones_top'] = 'fixed';
$prefs['module_zones_topbar'] = 'fixed';
$prefs['module_zones_pagetop'] = 'fixed';
$prefs['feature_left_column'] = 'fixed';
$prefs['feature_right_column'] = 'fixed';
$prefs['module_zones_pagebottom'] = 'fixed';
$prefs['module_zones_bottom'] = 'fixed';
$prefs['module_sidebar_toggle_small_screen'] = 'fixed';

$headerlib->add_css(
    '.module:hover {' . ' cursor: move;' . ' background-color: #ffa;' . ' }'
);

$headerlib->add_cssfile('themes/base_files/feature_css/admin.css');
$headerlib->add_jsfile('lib/modules/tiki-admin_modules.js');

if ($prefs['feature_jquery_validation'] === 'y') {
    // set up validation for custom module smarty syntax
    $rules = [
        'rules' => [
            'um_name' => [
                'required' => true,
            ],
            'um_data' => [
                'required' => true,
            ],
        ],
        'submitHandler' => 'submitHandlerFunction',
    ];

    // Add remote validator only if eval is allowed
    if ($prefs['smarty_enable_string_eval'] === 'y') {
        $rules['rules']['um_data']['remote'] = [
            'url' => 'validate-ajax.php',
            'type' => 'post',
            'data' => [
                'validator' => 'smarty',
                'input' => 'inputFunction',
                'parameter' => 'parameterFunction',
            ],
        ];
    }

    $validationjs = '$("form[name=editusr]").validate(' . json_encode($rules) . ')';
    $validationjs = str_replace('"inputFunction"', 'function() { return $("#um_data").val(); }', $validationjs);
    $validationjs = str_replace('"parameterFunction"', 'function() { return $("#um_parse").val(); }', $validationjs);
    $validationjs = str_replace(
        '"submitHandlerFunction"',
        'function(form, event){return process_submit(form, event);}',
        $validationjs
    );

    TikiLib::lib('header')->add_jq_onready($validationjs);
}


$sameurl_elements = ['offset', 'sort_mode', 'where', 'find'];

// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');

if (! isset($_REQUEST['sort_mode'])) {
    $sort_mode = 'name_desc';
} else {
    $sort_mode = $_REQUEST['sort_mode'];
}

$find = $_REQUEST['find'] ?? '';

$channels = $menulib->list_menus($offset, $maxRecords, $sort_mode, $find);
foreach ($channels['data'] as $i => $channel) {
    if ($userlib->object_has_one_permission($channel['menuId'], 'menus')) {
        $channels['data'][$i]['individual'] = 'y';
    }
}

$smarty->assign('channels', $channels['data']);

if (! empty($_REQUEST['edit_module'])) {    // pick up ajax calls
    // the strings below are used to display the tab titles in the edit module box
    //get_strings tr('Module') tr('Appearance') tr('Visibility')
    $smarty->display('admin_modules_form.tpl');
} else {
    // unfix margins for hidden columns, css previously added in setup/cookies.php
    if (getCookie('show_col2') === 'n') {
        unset($headerlib->css[100][array_search('#c1c2 #wrapper #col1.marginleft { margin-left: 0; }', $headerlib->css[100])]);
    }
    if (getCookie('show_col3') === 'n') {
        unset($headerlib->css[100][array_search('#c1c2 #wrapper #col1.marginright { margin-right: 0; }', $headerlib->css[100])]);
    }
    $smarty->assign('padding_top_layout', true);
    $smarty->assign('layout_has_modules', true);
    $smarty->assign('theme_navbar_fixed_topbar_offset', $prefs["theme_navbar_fixed_topbar_offset"] + 35);
    $smarty->assign('mid', 'tiki-admin_modules.tpl');
    $smarty->display('tiki.tpl');
}
