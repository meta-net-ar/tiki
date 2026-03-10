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
        'staticKeyFilters' => [
            'list' => 'string',
            'action' => 'string',
            'videoId' => 'string',
            'view' => 'string',
        ],
    ],
];
require_once 'tiki-setup.php';
$access->check_feature('vimeo_upload'); // Assuming vimeo_upload is the main feature flag used
$access->check_permission(['tiki_p_list_videos']);

if (! isset($_REQUEST['list'])) {
    $_REQUEST['list'] = 'videos';
}

try {
    TikiLib::lib('access')->setTicket();

    if (isset($_REQUEST['action'])) {
        $videoId = [];

        if (! empty($_REQUEST['videoId'])) {
            if (is_array($_REQUEST['videoId'])) {
                $videoId = $_REQUEST['videoId'];
            } else {
                $videoId[0] = $_REQUEST['videoId'];
            }
        }

        switch ($_REQUEST['action']) {
            case 'Delete':
                $access->check_permission(['tiki_p_delete_videos']);
                $access->checkCsrf();
                $vimeolib = TikiLib::lib('vimeo');

                foreach ($videoId as $vi) {
                    $vimeolib->deleteVideo($vi);
                }
                Feedback::success(tra('Video deleted successfully.'));
                header('Location: tiki-list_vimeo_entries.php?list=videos');
                die;
            default:
                Feedback::errorAndDie(tra('Invalid action'), 409);
        }
    }

    $sort_mode = $jitRequest->sort_mode->word() ?: 'created_desc';
    $smarty->assign_by_ref('sort_mode', $sort_mode);

    $find = $jitRequest->find->text();
    $smarty->assign('find', $find);

    $errors = [];
    $page_size = $jitRequest->maxRecords->int() ?: $prefs['maxRecords'];
    $offset = max(0, $jitRequest->offset->int());
    $page = (int)(($offset / $page_size) + 1);

    if ($_REQUEST['list'] == 'videos') {
        $vimeolib = TikiLib::lib('vimeo');
        $videolist = $vimeolib->listVideos($sort_mode, $page, $page_size, $find);

        if ($videolist && isset($videolist->data) && is_array($videolist->data)) {
            // Already cast appropriately in VimeoLib
            $smarty->assign('klist', $videolist->data);
            $smarty->assign('count', $videolist->total ?? count($videolist->data));
        } else {
            $smarty->assign('klist', []);
            $smarty->assign('count', 0);
        }
        $smarty->assign('entryType', 'videos');
        $smarty->assign('view', $jitRequest->view->alpha());
    }
} catch (Exception $e) {
    $errors[] = tr('Invalid response provided by the Vimeo API. Please retry.') .
        ' <em>' . $e->getMessage() . '</em>';
}

$smarty->assign('errors', $errors);
$smarty->assign('offset', $offset);
$smarty->assign('maxRecords', $page_size);
$smarty->assign('mid', 'tiki-list_vimeo_entries.tpl');
$smarty->assign('ticket', TikiLib::lib('access')->getTicket());
$smarty->display('tiki.tpl');
