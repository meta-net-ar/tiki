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
            'videoId' => 'word',
            'action' => 'word',
            'name' => 'text',
            'description' => 'xss',
            'update' => 'bool',
        ],
    ],
];

require_once 'tiki-setup.php';

$access->check_feature('vimeo_upload');

try {
    $videoId = $_REQUEST['videoId'] ?? '';
    $mode = $_REQUEST['action'] ?? null;

    $smarty->assign('videoId', $videoId);

    if (! empty($videoId) && $mode) {
        $smarty->assign('pmode', $mode);

        $vimeolib = TikiLib::lib('vimeo');

        switch ($mode) {
            case 'delete':
                $access = TikiLib::lib('access');
                $access->check_permission(['tiki_p_delete_videos']);
                $access->checkCsrf();

                try {
                    $videoId = $_POST['videoId'];
                    $vimeolib->deleteVideo($videoId);
                    Feedback::success(tra('The video has been successfully deleted.'));
                } catch (Exception $e) {
                    Feedback::error(tra('Failed to delete the video: ') . $e->getMessage());
                }

                header('Location: tiki-list_vimeo_entries.php');
                exit;

            case 'edit':
                $access->check_permission(['tiki_p_edit_videos']);
                $video = $vimeolib->getVideo($videoId);

                if (is_array($video)) {
                    $video = (object) $video;
                }

                if (! empty($_REQUEST['update'])) {
                    $updateData = [
                        'name' => $_REQUEST['name'],
                        'description' => $_REQUEST['description'],
                    ];

                    $vimeolib->updateVideo($videoId, $updateData);
                    header('Location: tiki-vimeo_video.php?videoId=' . urlencode($videoId));
                    exit;
                }

                $smarty->assign('videoInfo', $video);
                break;

            default:
                Feedback::errorAndDie(tra('Incorrect param'), \Laminas\Http\Response::STATUS_CODE_409);
        }
    } elseif (! empty($videoId)) {
        $access->check_permission(['tiki_p_view_videos']);
        $smarty->assign('pmode', 'view');

        $vimeolib = TikiLib::lib('vimeo');
        $video = $vimeolib->getVideo($videoId);

        if (is_array($video)) {
            $video = (object) $video;
        }

        $uuid = $video->shortUUID ?? $video->uuid ?? $videoId;

        if (! $video) {
            Feedback::error(tra('Invalid URL or video not found. The video may be private or unavailable.'));
        }

        $viewUrl = $video->link ?? ('https://vimeo.com/' . $uuid);

        $smarty->assign('videoInfo', $video);
        $smarty->assign('videoUrl', $viewUrl);
    }

    $smarty->assign('mid', 'tiki-vimeo_video.tpl');
    $smarty->display('tiki.tpl');
} catch (Exception $e) {
    $access->display_error(
        '',
        tr('Communication error'),
        500,
        true,
        tr('Invalid response provided by the Vimeo server. Please retry.') . '<br /><em>' . $e->getMessage() . '</em>'
    );
}
