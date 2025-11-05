<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$inputConfiguration = [
    [
        'staticKeyFilters'         => [
        'receivedPageId'           => 'int',               //get
        'accept'                   => 'bool',              //get
        'view'                     => 'word',              //get
        'preview'                  => 'bool',              //post
        'pageName'                 => 'pagename',          //post
        'data'                     => 'none',              //post
        'comment'                  => 'text',              //post
        'remove'                   => 'int',               //get
        'save'                     => 'bool',              //post
        'checked'                  => 'bool',              //post
        'prefix'                   => 'word',              //post
        'postfix'                  => 'word',              //post
        'sort_mode'                => 'alnumdash',         //get
        'offset'                   => 'digits',            //get
        'find'                     => 'text',              //get
        'sort_modes'               => 'alnumdash',         //get
        ],
    ],
];
require_once('tiki-setup.php');
include_once('lib/commcenter/commlib.php');
$wikilib = TikiLib::lib('wiki');
$auto_query_args = ['receivedPageId', 'sort_mode', 'offset', 'find', 'sort_modes'];
$access->check_feature('feature_comm');
$access->check_permission('tiki_p_admin_received_pages');

$pageId = $_REQUEST["receivedPageId"] ?? 0;
$smarty->assign('receivedPageId', $pageId);

$info = [];
$info["pageName"] = '';
$info["data"] = '';
$info["comment"] = '';
$errors = [];
if (isset($_REQUEST["accept"]) && $access->checkCsrf()) {
    // CODE TO ACCEPT A PAGE HERE
    if (! $commlib->accept_page($_REQUEST["accept"])) {
        $pageId = $_REQUEST["accept"];
        $errors[] = tr('Page already exists');
    }
}

$smarty->assign('view', 'n');

if (isset($_REQUEST["view"])) {
    $pageId = $_REQUEST["view"];
    $smarty->assign('view', 'y');
}

if (isset($_REQUEST["preview"])) {
    $info["pageName"] = $_REQUEST["pageName"];
    $info["data"] = $_REQUEST["data"];
    $info["comment"] = $_REQUEST["comment"];
}

if ($pageId) {
    try {
        $info = $commlib->get_received_page($pageId);
    } catch (Exception $e) {
        Feedback::error($e->getMessage());
        $smarty->assign('receivedPageId', 0);
    }
}

$smarty->assign('pageName', $info["pageName"]);
$smarty->assign('data', $info["data"]);
$smarty->assign('comment', $info["comment"]);
// Assign parsed
$smarty->assign('parsed', TikiLib::lib('parser')->parse_data($info["data"]));
if (isset($_REQUEST["remove"]) && $access->checkCsrf()) {
    $commlib->remove_received_page($_REQUEST["remove"]);
}
if (isset($_REQUEST["save"])) {
    $access->checkCsrf();
    $commlib->update_received_page($_REQUEST["receivedPageId"], $_REQUEST["pageName"], $_REQUEST["data"], $_REQUEST["comment"]);
    $smarty->assign('pageName', $_REQUEST["pageName"]);
    $smarty->assign('data', $_REQUEST["data"]);
    $smarty->assign('comment', $_REQUEST["comment"]);
    $smarty->assign('receivedPageId', 0);
    $smarty->assign('parsed', TikiLib::lib('parser')->parse_data($_REQUEST["data"]));
}
if (! empty($_REQUEST['checked']) && (! empty($_REQUEST['prefix']) || ! empty($_REQUEST['postfix']))) {
    $access->checkCsrf();
    foreach ($_REQUEST['checked'] as $page) {
        $newpage = empty($_REQUEST['postfix']) ? $_REQUEST['prefix'] . $page : $page . $_REQUEST['postfix'];
        if ($tikilib->page_exists($newpage)) {
            $errors[] = tr('Page already exists') . ' ' . $page;
        }
    }
    if (empty($errors)) {
        $commlib->rename_structure_pages($_REQUEST['checked'], $_REQUEST['prefix'] ?? '', $_REQUEST['postfix'] ?? '');
    }
}
if (! isset($_REQUEST["sort_mode"])) {
    $sort_mode = 'receivedDate_desc';
} else {
    $sort_mode = $_REQUEST["sort_mode"];
}
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
$smarty->assign_by_ref('sort_mode', $sort_mode);
$channels = $tikilib->list_received_pages($offset, $maxRecords, $sort_mode, $find, 'p');
$smarty->assign_by_ref('channels', $channels["data"]);
$smarty->assign_by_ref('count', $channels['count']);
if (! isset($_REQUEST['sort_modes'])) {
    $sort_modes = 'receivedDate_desc';
} else {
    $sort_modes = $_REQUEST['sort_modes'];
}
$structures = $tikilib->list_received_pages(0, -1, $sort_modes, $find, 's');
$smarty->assign_by_ref('structures', $structures['data']);
if (! empty($errors)) {
    Feedback::error(['mes' => $errors]);
}
// Display the template
$smarty->assign('mid', 'tiki-received_pages.tpl');
$smarty->display("tiki.tpl");
