<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$section = 'sheet';
require_once('tiki-setup.php');
require_once('lib/sheet/grid.php');
$sheetlib = TikiLib::lib('sheet');
$auto_query_args = [
    'sheetId',
    'readdate',
];

$access->check_feature('feature_sheet');

if (! isset($_REQUEST['sheetId'])) {
    Feedback::errorAndDie(tra('Missing parameter "sheetId"'), \Laminas\Http\Response::STATUS_CODE_400);
}

try {
    $info = TikiLib::lib("sheet")->get_sheet_info($_REQUEST['sheetId']);
} catch (Exception $e) {
    $smarty->assign('msg', tra($e->getMessage()));
    $smarty->display("error.tpl");
    die;
}

$objectperms = Perms::get('sheet', $_REQUEST['sheetId']);
if ($tiki_p_admin != 'y' && ! $objectperms->view_sheet && ! ($user && $info['author'] == $user)) {
    Feedback::errorAndDie(tra('Permission denied'), \Laminas\Http\Response::STATUS_CODE_403);
}

$encoding = new Encoding();
$charsetList = $encoding->get_input_supported_encodings();
$smarty->assign_by_ref("charsets", $charsetList);

$smarty->assign('title', $info['title']);
$smarty->assign('description', $info['description']);

$smarty->assign('page_mode', 'form');
$smarty->assign('sheetId', $_REQUEST['sheetId']);

// Process the insertion or modification of a gallery here
$grid = new TikiSheet();

$history = $sheetlib->sheet_history($_REQUEST['sheetId']);
$smarty->assign_by_ref('history', $history);

if (isset($_REQUEST['encoding'])) {
    $smarty->assign('page_mode', 'submit');

    $handler = new TikiSheetDatabaseHandler($_REQUEST['sheetId'], $_REQUEST['readdate']);
    $grid->import($handler);

    $handler = $_REQUEST['handler'];
    $handlers_ = new TikiSheet();

    if (! in_array($handler, $handlers_->getHandlerList())) {
        Feedback::errorAndDie(tra("Handler is not allowed."), \Laminas\Http\Response::STATUS_CODE_401);
    }

    $handler = new $handler("php://stdout", 'UTF-8', $_REQUEST['encoding']);
    $grid->export($handler);

    TikiLib::lib('header')->setXRobotsTag($robots);
    header("Content-type: text/comma-separated-values");
    header("Content-Disposition: attachment; filename=export.csv");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
    header("Pragma: public");

    echo $handler->output;
    exit;
} else {
    $list = [];

    $handlers_ = new TikiSheet();
    $handlers = $handlers_->getHandlerList();

    foreach ($handlers as $key => $handler) {
        $temp = new $handler();
        if (! $temp->supports(TIKISHEET_SAVE_DATA | TIKISHEET_SAVE_CALC)) {
            continue;
        }

        $list[$key] = [
            "name" => $temp->name(),
            "version" => $temp->version(),
            "class" => $handler
        ];
    }

    $smarty->assign_by_ref("handlers", $list);
}

$cat_type = 'sheet';
$cat_objid = $_REQUEST["sheetId"];
include_once("categorize_list.php");

include_once('tiki-section_options.php');
// Display the template
$smarty->assign('mid', 'tiki-export-sheets.tpl');
$smarty->display("tiki.tpl");
