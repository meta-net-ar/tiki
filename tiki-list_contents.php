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
        'staticKeyFilters'       => [
        'remove'                 => 'int',                 //get
        'contentId'              => 'int',                 //post
        'save'                   => 'bool',                //post
        'edit'                   => 'int',                //post
        'description'            => 'xss',                 //post
        'contentLabel'           => 'string',              //post
        'sort_mode'              => 'word',                //get
        'offset'                 => 'int',                 //get
        'find'                   => 'string',              //post
        ],
    ],
];
require_once('tiki-setup.php');
$auto_query_args = ['sort_mode', 'offset', 'find'];
//get_strings tra('Dynamic content')
$access->check_feature('feature_dynamic_content');
$access->check_permission('tiki_p_admin_dynamic');

$dcslib = TikiLib::lib('dcs');

if (isset($_REQUEST["remove"]) && $access->checkCsrf()) {
    $dcslib->remove_contents($_REQUEST["remove"]);
}
$smarty->assign('description', '');
$smarty->assign('contentLabel', '');
$smarty->assign('contentId', 0);
if (isset($_REQUEST["save"])) {
    $access->checkCsrf();
    $contentLabel = $_REQUEST["contentLabel"] ?? '';
    $description = $_REQUEST["description"] ?? '';
    $smarty->assign('contentLabel', $contentLabel);
    $smarty->assign('description', $description);

    if (empty(trim($contentLabel))) {
        Feedback::error('Content Label is required to save the content block.');
    } else {
        $id = $dcslib->replace_content($_REQUEST["contentId"], $description, $contentLabel);
        $smarty->assign('contentId', $id);
    }
}
if (isset($_REQUEST["edit"])) {
    $info = $dcslib->get_content($_REQUEST["edit"]);
    $smarty->assign('contentId', $info["contentId"]);
    $smarty->assign('description', $info["description"]);
    $smarty->assign('contentLabel', $info["contentLabel"]);
}
if (! isset($_REQUEST["sort_mode"])) {
    $sort_mode = 'contentId_desc';
} else {
    $sort_mode = $_REQUEST["sort_mode"];
}
$smarty->assign_by_ref('sort_mode', $sort_mode);
// If offset is set use it if not then use offset =0
// use the maxRecords php variable to set the limit
if (! isset($_REQUEST["offset"])) {
    $offset = 0;
} else {
    $offset = $_REQUEST["offset"];
}
$smarty->assign_by_ref('offset', $offset);
if (isset($_REQUEST["find"])) {
    $find = $_REQUEST["find"];
} else {
    $find = '';
}
$smarty->assign('find', $find);
// Get a list of last changes to the Wiki database
$listpages = $dcslib->list_content($offset, $maxRecords, $sort_mode, $find);
$smarty->assign_by_ref('cant', $listpages['cant']);
$smarty->assign_by_ref('listpages', $listpages["data"]);
// Display the template
$smarty->assign('mid', 'tiki-list_contents.tpl');
$smarty->display("tiki.tpl");
