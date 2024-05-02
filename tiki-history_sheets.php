<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$tiki_sheet_div_style = '';
require_once('tiki-setup.php');

$sheetlib = TikiLib::lib("sheet");

$auto_query_args = [
    'sheetId',
    'idx_0',
    'idx_1'
];
$access->check_feature('feature_sheet');

if (! isset($_REQUEST['sheetId'])) {
    $smarty->assign('msg', tra('Missing parameter "sheetId"'));
    $smarty->display('error.tpl');
    die;
}

$info = $sheetlib->get_sheet_info($_REQUEST['sheetId']);
if (empty($info)) {
    $smarty->assign('msg', tr('Unable to retrieve sheet id %0', $_REQUEST['sheetId']));
    $smarty->display('error.tpl');
    die;
}

$objectperms = Perms::get('sheet', $_REQUEST['sheetId']);
if ($tiki_p_admin != 'y' && ! $objectperms->view_sheet && ! ($user && $info['author'] == $user)) {
    $smarty->assign('msg', tra('Permission denied'));
    $smarty->display('error.tpl');
    die;
}
$smarty->assign('objectperms', $objectperms);
$smarty->assign('sheetId', $_REQUEST["sheetId"]);
$smarty->assign('title', $info['title']);
$smarty->assign('description', $info['description']);
$smarty->assign('page_mode', 'view');

$history = $sheetlib->sheet_history($_REQUEST['sheetId']);
$smarty->assign_by_ref('history', $history);

$sheetIndexes = [];
if (isset($_REQUEST['idx_0'])) {
    $sheetIndexes[0] = TikiFilter::get('int')->filter($_REQUEST['idx_0']);
} else {
    $sheetIndexes[0] = 1; //this sets defalut for initial page load
}
if (isset($_REQUEST['idx_1'])) {
    $sheetIndexes[1] = TikiFilter::get('int')->filter($_REQUEST['idx_1']);
} else {
    $sheetIndexes[1] = 0; //this sets defalut for initial page load
}

$historyTimestamps[0] = $history[$sheetIndexes[0]]['stamp'] ?? null;
$historyTimestamps[1] = $history[$sheetIndexes[1]]['stamp'] ?? null;

$smarty->assign_by_ref('sheetIndexes', $sheetIndexes);
$smarty->assign('ver_cant', count($history));
$smarty->assign('grid_content', $sheetlib->diff_sheets_as_html($_REQUEST["sheetId"], $historyTimestamps));

$cookietab = 1;

$sheetlib->setup_jquery_sheet();
$headerlib->add_jq_onready(
    "
    $.sheet.tikiOptions = $.extend($.sheet.tikiOptions, {
        editable: false
    });

    jST = $('div.tiki_sheet')
        .sheet($.sheet.tikiOptions)
        .on('paneScroll', $.sheet.paneScrollLocker)
        .on('switchSheet', $.sheet.switchSheetLocker);

    $.sheet.setValuesForCompareSheet('$sheetIndexes[0]', $('input.compareSheet1'), '$sheetIndexes[1]', $('input.compareSheet2'));

    let inFullScreen = false;
    $('#go_fullscreen').on('click', function(e) {
        e.preventDefault();
        if(inFullScreen) {
            $.sheet.dualFullScreenHelper($('#tiki_sheet_container').parent(), true);
        } else {
            $.sheet.dualFullScreenHelper($('#tiki_sheet_container').parent());
        }
        inFullScreen = !inFullScreen;
    });
",
    500
);

if ($tiki_sheet_div_style) {
    $smarty->assign('tiki_sheet_div_style', $tiki_sheet_div_style);
}

include_once('tiki-section_options.php');

$smarty->assign('lock', true);

// Display the template
$smarty->assign('mid', 'tiki-history_sheets.tpl');
$smarty->display("tiki.tpl");
