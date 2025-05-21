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
            'msgId'                        => 'int',                 //post
            'remove'                       => 'int',                 //get
            'shoutbox_admin'               => 'bool',                //post
            'shoutbox_autolink'            => 'bool',                //post
            'save'                         => 'bool',                //post
            'message'                      => 'text',                //post
            'tweet'                        => 'bool',                //post
            'sort_mode'                    => 'word',                //get
            'offset'                       => 'digits',              //get
            'find'                         => 'text',                //post
        ],
    ],
];

require_once('tiki-setup.php');
include_once('lib/shoutbox/shoutboxlib.php');

$access->check_feature('feature_shoutbox');
$access->check_permission('tiki_p_view_shoutbox');

if (! isset($_REQUEST["msgId"])) {
    $_REQUEST["msgId"] = 0;
}
$smarty->assign('msgId', $_REQUEST["msgId"]);
if ($_REQUEST["msgId"]) {
    $info = $shoutboxlib->get_shoutbox($_REQUEST["msgId"]);
    $owner = $info["user"];
    if ($tiki_p_admin_shoutbox != 'y' && $owner != $user) {
        Feedback::errorAndDie(tr("You do not have permission to edit messages %0", $owner), \Laminas\Http\Response::STATUS_CODE_404);
    }
} else {
    $info = [];
    $info["message"] = '';
    $info["user"] = $user;
    $owner = $info["user"];
}
$smarty->assign('message', $info["message"]);
if ($tiki_p_admin_shoutbox == 'y' || $user == $owner) {
    if (isset($_REQUEST["remove"]) && $access->checkCsrf()) {
        $shoutboxlib->remove_shoutbox($_REQUEST["remove"]);
    } elseif (isset($_REQUEST["shoutbox_admin"])) {
        $prefs['shoutbox_autolink'] = (isset($_REQUEST["shoutbox_autolink"])) ? 'y' : 'n';
        $tikilib->set_preference('shoutbox_autolink', $prefs['shoutbox_autolink']);
    }
}
if ($tiki_p_post_shoutbox == 'y') {
    if (isset($_REQUEST["save"]) && ! empty($_REQUEST['message'])) {
        $access->checkCsrf();
        if (($prefs['feature_antibot'] == 'y' && empty($user)) && ! $captchalib->validate()) {
            Feedback::error(['mes' => $captchalib->getErrors()]);
            $smarty->assign_by_ref('message', $_REQUEST['message']);
        } else {
            $shoutboxlib->replace_shoutbox(
                $_REQUEST['msgId'],
                $owner,
                $_REQUEST['message'],
                (isset($_REQUEST['tweet']) && $_REQUEST['tweet'] == 1)
            );
            $smarty->assign('msgId', '0');
            $smarty->assign('message', '');
        }
    }
}
$sort_mode = $_REQUEST["sort_mode"] ?? 'timestamp_desc';
$offset = $_REQUEST["offset"] ?? 0;
$smarty->assign_by_ref('offset', $offset);
$find = $_REQUEST["find"] ?? '';
$get = $_REQUEST["get"] ?? 0;
/* additions for ajax (formerly shoutjax) */

/* end additions for ajax */
$smarty->assign('find', $find);
$smarty->assign_by_ref('sort_mode', $sort_mode);
if ($get) {
    $data = $shoutboxlib->get_shoutbox($get);
    $channels['data'] = [$data];
    $channels['count'] = 1;
} else {
    $channels = $shoutboxlib->list_shoutbox($offset, $maxRecords, $sort_mode, $find);
}
$smarty->assign_by_ref('pages_count', $channels["count"]);
$smarty->assign_by_ref('channels', $channels["data"]);
// Display the template
$smarty->assign('mid', 'tiki-shoutbox.tpl');
$smarty->display("tiki.tpl");
