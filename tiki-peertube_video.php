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
            'tags' => 'text',
        ],
    ],
];

require_once 'tiki-setup.php';

$access->check_feature('feature_peertube');

try {
    $videoId = $_REQUEST['videoId'] ?? '';
    $mode = $_REQUEST['action'] ?? null;

    $smarty->assign('videoId', $videoId);

    if (! empty($videoId) && $mode) {
        $smarty->assign('pmode', $mode);

        require_once 'lib/videogals/peertubelib.php';
        $peertubelib = new \Tiki\Videogals\PeerTubeLib();

        switch ($mode) {
            case 'delete':
                $access = TikiLib::lib('access');
                $access->check_permission(['tiki_p_delete_videos']);
                $access->checkCsrf();

                try {
                    $videoId = $_POST['videoId'];
                    $peertubelib->deleteVideo($videoId);
                    Feedback::success(tra('The video has been successfully deleted.'));
                } catch (Exception $e) {
                    Feedback::error(tra('Failed to delete the video: ') . $e->getMessage());
                }

                header('Location: tiki-list_peertube_entries.php');
                exit;

            case 'edit':
                $access->check_permission(['tiki_p_edit_videos']);
                $video = $peertubelib->getVideo($videoId);

                if (is_array($video)) {
                    $video = (object) $video;
                }

                if (! empty($_REQUEST['update'])) {
                    $updateData = [
                        'name' => $_REQUEST['name'],
                        'description' => $_REQUEST['description'],
                    ];

                    if (! empty($_REQUEST['tags'])) {
                        $updateData['tags'] = $_REQUEST['tags'];
                    }

                    $peertubelib->updateVideo($videoId, $updateData);
                    header('Location: tiki-peertube_video.php?videoId=' . urlencode($videoId));
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

        require_once 'lib/videogals/peertubelib.php';
        $peertubelib = new \Tiki\Videogals\PeerTubeLib();
        $video = $peertubelib->getVideo($videoId);

        if (is_array($video)) {
            $video = (object) $video;
        }

        $base = rtrim($prefs['peertube_service_url'], '/');
        $uuid     = $video->shortUUID ?? $video->uuid ?? $videoId;

        $privacyLabel  = $video->privacy['label'] ?? null;
        $categoryLabel = $video->category['label'] ?? null;
        $licenceLabel  = $video->licence['label'] ?? null;
        $langLabel     = $video->language['label'] ?? null;

        if (! $video) {
            Feedback::error(tra('Invalid URL or video not found. The video may be private or unavailable.'));
        } elseif ($privacyLabel === 'Internal') {
            Feedback::error(tra('This video is set to "Internal". You may not be able to view it here.'));
        }

        $viewUrl  = $base . '/w/' . $uuid;
        $embedUrl = isset($video->embedPath) ? $base . $video->embedPath : null;

        $dlUrl = $video->streamingPlaylists[0]['files'][0]['fileDownloadUrl'] ?? null;

        $smarty->assign('videoInfo', $video);
        $smarty->assign('videoUrl', $viewUrl);
        $smarty->assign('peertube_embed_url', $embedUrl);
        $smarty->assign('peertube_download_url', $dlUrl);
        $smarty->assign('peertube_privacy_label', $privacyLabel);
        $smarty->assign('peertube_category_label', $categoryLabel);
        $smarty->assign('peertube_licence_label', $licenceLabel);
        $smarty->assign('peertube_language_label', $langLabel);

        $smarty->assign('videoInfo', $video);
    }

    $smarty->assign('mid', 'tiki-peertube_video.tpl');
    $smarty->display('tiki.tpl');
} catch (Exception $e) {
    $access->display_error(
        '',
        tr('Communication error'),
        500,
        true,
        tr('Invalid response provided by the PeerTube server. Please retry.') . '<br /><em>' . $e->getMessage() . '</em>'
    );
}
