<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\TikiInit;

require_once 'tiki-setup.php';

$access = TikiLib::lib('access');
$access->check_feature('vimeo_upload');

$videoId = $_REQUEST['videoId'] ?? null;
$isEdit = !empty($videoId);

if ($isEdit) {
    $access->check_permission('tiki_p_edit_videos');
} else {
    $access->check_permission('tiki_p_upload_videos');
}

$vimeolib = TikiLib::lib('vimeo');
$errors = [];

include('lib/Filegals/max_upload_size.php');

$smarty = TikiLib::lib('smarty');

$videoInfo = null;
if ($isEdit) {
    try {
        $videoInfo = (object) $vimeolib->getVideo($videoId);
    } catch (Exception $e) {
        $errors[] = tra('Failed to load video details: ') . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if (mb_strlen($name) < 3 || mb_strlen($name) > 120) {
        $errors[] = tra('Title must be between 3 and 120 characters.');
    }

    if ($desc !== '' && mb_strlen($desc) < 3) {
        $errors[] = tra('Description must be at least 3 characters, or leave it empty.');
    }

    if (!$errors) {
        $metadata = ['name' => $name];

        if ($desc !== '') {
            $metadata['description'] = $desc;
        }

        if (!empty($_POST['privacy'])) {
            $metadata['privacy'] = $_POST['privacy'];
        }

        try {
            if ($isEdit) {
                // Update metadata
                $vimeolib->updateVideo($videoId, $metadata);

                // Replace video if new file provided
                if (!empty($_FILES['video']['size']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
                    if ($_FILES['video']['size'] > $max_upload_size) {
                        throw new Exception(tra('File exceeds maximum size of ') . $max_upload_size_comment);
                    }
                    $file = ['videofile' => $_FILES['video']['tmp_name']];
                    $vimeolib->replaceVideo($videoId, $file);
                }

                // Upload thumbnail if provided
                if (!empty($_FILES['thumbnail']['size']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
                    $thumb = ['thumbfile' => $_FILES['thumbnail']['tmp_name']];
                    $vimeolib->uploadThumbnail($videoId, $thumb);
                }

                Feedback::success(tra('Video updated successfully!'));
                header('Location: tiki-vimeo_video.php?videoId=' . urlencode($videoInfo->shortUUID));
                exit;

            } else {
                // New video upload
                if (!empty($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
                    if ($_FILES['video']['size'] > $max_upload_size) {
                        throw new Exception(tra('File exceeds maximum size of ') . $max_upload_size_comment);
                    }
                    $file = ['videofile' => $_FILES['video']['tmp_name']];
                    $result = $vimeolib->uploadVideo($file, $metadata);

                    if (!empty($result['video']['uuid'])) {
                        $newVideoId = $result['video']['shortUUID'];

                        // Upload thumbnail if provided
                        if (!empty($_FILES['thumbnail']['size']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
                            $thumb = ['thumbfile' => $_FILES['thumbnail']['tmp_name']];
                            $vimeolib->uploadThumbnail($newVideoId, $thumb);
                        }

                        Feedback::success(tra('Video uploaded successfully!'));
                        header('Location: tiki-vimeo_video.php?videoId=' . urlencode($newVideoId));
                        exit;
                    } else {
                        $errors[] = tra('Failed to upload video to Vimeo.');
                    }
                } else {
                    $errors[] = tra('No video file was uploaded. Please select a file and try again.');
                }
            }
        } catch (Exception $e) {
            $errors[] = tra('Error during operation: ') . $e->getMessage();
        }
    }
}

$smarty->assign('errors', $errors);
$smarty->assign('isEdit', $isEdit);
if ($isEdit && $videoInfo) {
    $smarty->assign('videoInfo', $videoInfo);
}

$smarty->assign('max_upload_size', $max_upload_size);
$smarty->assign('max_upload_size_comment', $max_upload_size_comment);
$smarty->assign('is_iis', TikiInit::isIIS());
$smarty->assign('mid', 'tiki-vimeo_upload.tpl');
$smarty->display('tiki.tpl');
