<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\TikiInit;

require_once 'tiki-setup.php';

$access = TikiLib::lib('access');
$access->check_feature('vimeo_upload'); // Assuming vimeo_upload is the main feature flag used
$access->check_permission('tiki_p_upload_videos');

$vimeolib = TikiLib::lib('vimeo');
$filegallib = TikiLib::lib('filegal');

$errors = [];

include('lib/Filegals/max_upload_size.php');

$smarty = TikiLib::lib('smarty');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['video'])) {
        if ($_FILES['video']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = tra('File upload error: ') . $_FILES['video']['error'] . ' (Details: ' . print_r($_FILES['video'], true) . ')';
        } elseif ($_FILES['video']['size'] > $max_upload_size) {
            $errors[] = tra('File exceeds maximum size of ') . $max_upload_size_comment;
        } else {
            $name = trim($_POST['name'] ?? '');
            $desc = trim($_POST['description'] ?? '');

            if (mb_strlen($name) < 3 || mb_strlen($name) > 120) {
                $errors[] = tra('Title must be between 3 and 120 characters.');
            }

            if ($desc !== '' && mb_strlen($desc) < 3) {
                $errors[] = tra('Description must be at least 3 characters, or leave it empty.');
            }

            if (! $errors) {
                $metadata = [
                    'name' => $name,
                ];

                if ($desc !== '') {
                    $metadata['description'] = $desc;
                }

                if (! empty($_POST['privacy'])) {
                    $metadata['privacy'] = $_POST['privacy'];
                }

                $file = [
                    'videofile' => $_FILES['video']['tmp_name']
                ];

                try {
                    $result = $vimeolib->uploadVideo($file, $metadata);
                    if (! empty($result['video']['uuid'])) {
                        Feedback::success(tra('Video uploaded successfully!'));
                        header('Location: tiki-vimeo_video.php?videoId=' . urlencode($result['video']['shortUUID']));
                        exit;
                    } else {
                        $errors[] = tra('Failed to upload video to Vimeo.');
                    }
                } catch (Exception $e) {
                    $errors[] = tra('Error during upload: ') . $e->getMessage();
                }
            }
        }
    } else {
        $errors[] = tra('No file was uploaded. Please select a file and try again.');
    }
}

$smarty->assign('errors', $errors);
$smarty->assign('max_upload_size', $max_upload_size);
$smarty->assign('max_upload_size_comment', $max_upload_size_comment);
$smarty->assign('is_iis', TikiInit::isIIS());
$smarty->assign('mid', 'tiki-vimeo_upload.tpl');
$smarty->display('tiki.tpl');
