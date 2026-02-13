<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE.

namespace Services\Peertube;

class ServicesPeerTubeController
{
    public function setUp()
    {
        \Services_Exception_Disabled::check('feature_peertube');
    }

    public function actionList($input)
    {
        $perms = \Perms::get();
        if (! $perms->list_videos) {
            throw new \Services_Exception_Denied('Not allowed to list videos');
        }

        $sort_mode = $input->sort_mode->word() ?: 'created_desc';
        $find      = $input->find->text();
        $page_size = min(max(1, $input->maxRecords->int() ?: 25), 100);
        $offset    = max(0, $input->offset->int());
        $page      = (int) floor($offset / $page_size) + 1;

        $peertube = \TikiLib::lib('peertubeuser');
        try {
            $list = $peertube->listVideos($sort_mode, $page, $page_size, $find);
        } catch (\Exception $e) {
            throw new \Services_Exception(tr('Error fetching videos: %0', $e->getMessage()));
        }

        $entries = $list->data ?? $list->objects ?? [];
        $total   = $list->total ?? $list->totalCount ?? 0;

        global $prefs;
        $base = rtrim($prefs['peertube_service_url'] ?? '', '/');

        $normalized = [];
        foreach ((array) $entries as $v) {
            $o = is_array($v) ? (object) $v : $v;
            if (! empty($o->thumbnailUrl)) {
                $o->thumbUrl = $o->thumbnailUrl;
            } elseif (! empty($o->thumbnailPath) && $base) {
                $o->thumbUrl = $base . $o->thumbnailPath;
            }
            $normalized[] = $o;
        }

        return [
            'entries'    => $normalized,
            'totalCount' => $total,
            'formId'     => $input->formId->text(),
            'targetName' => $input->targetName->text(),
        ];
    }

    public function actionUpload($input)
    {
        $perms = \Perms::get();
        if (! $perms->upload_videos) {
            throw new \Services_Exception_Denied(tr('Not allowed to upload videos'));
        }

        $targetName = $input->targetName->text();
        $out = [
            'formId' => $input->formId->text(),
            'targetName' => $targetName,
            'title' => tr('Upload Video'),
            'uploadInModal' => true,
            'name' => '',
            'description' => '',
            'channelId' => 0,
            'privacy' => 1,
        ];

        // Load channels for the form
        $peertube = \TikiLib::lib('peertubeuser');
        $channelList = [];
        try {
            $channelResponse = $peertube->getMyChannels();
            if (! empty($channelResponse['data'])) {
                foreach ($channelResponse['data'] as $channel) {
                    $id = $channel['id'];
                    $name = $channel['displayName'] ?? $channel['name'];
                    $channelList[$id] = $name;
                }
            }
        } catch (\Exception $e) {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                \Feedback::error(tr('Failed to load PeerTube channels: %0', $e->getMessage()));
            }
        }

        $out['channelList'] = $channelList;

        // Get max upload size
        include('lib/Filegals/max_upload_size.php');
        $out['max_upload_size'] = $max_upload_size;
        $out['max_upload_size_comment'] = $max_upload_size_comment;
        $out['is_iis'] = \Tiki\TikiInit::isIIS();

        // Handle POST upload
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['video'])) {
            $name = trim($input->name->text());
            $desc = trim($input->description->text());
            $channelId = $input->channelId->int();
            $privacy = $input->privacy->int();

            // Repopulate form values
            $out['name'] = $name;
            $out['description'] = $desc;
            $out['channelId'] = $channelId;
            $out['privacy'] = $privacy;

            $errors = [];

            if ($_FILES['video']['error'] !== UPLOAD_ERR_OK) {
                $errors[] = tr('File upload error: %0', $_FILES['video']['error']);
            } elseif ($_FILES['video']['size'] > $max_upload_size) {
                $errors[] = tr('File exceeds maximum size of %0', $max_upload_size_comment);
            } else {
                require_once 'lib/core/Services/Video/Validator.php';
                $validationErrors = \Services\Video\Validator::validateMetadata($name, $desc);
                $errors = array_merge($errors, $validationErrors);

                if ($channelId <= 0) {
                    $errors[] = tr('Please select a valid channel.');
                }

                if (empty($errors)) {
                    $metadata = [
                        'name' => $name,
                        'channelId' => $channelId,
                    ];

                    if ($desc !== '') {
                        $metadata['description'] = $desc;
                    }

                    if ($privacy) {
                        $metadata['privacy'] = $privacy;
                    }

                    $file = [
                        'videofile' => new \CURLFile(
                            $_FILES['video']['tmp_name'],
                            $_FILES['video']['type'],
                            $_FILES['video']['name']
                        ),
                    ];

                    try {
                        $result = $peertube->uploadVideo($file, $metadata);
                        if (! empty($result['video']['uuid'])) {
                            // Return the video ID and name in the format expected by the success handler
                            $videoId = $result['video']['shortUUID'] ?? $result['video']['uuid'] ?? $result['video']['id'];
                            $videoName = $result['video']['name'] ?? $name;

                            // Return entries as array - close modal and trigger success via custom event
                            $out['entries'] = [$videoId];
                            $out['entryNames'] = [$videoName]; // Store names separately for display
                            $out['targetName'] = $targetName; // Include targetName for event triggering
                            $out['extra'] = 'close'; // Close modal without reload
                            $out['noTemplate'] = true; // Flag to skip template rendering
                            return $out;
                        } else {
                            $errors[] = tr('Failed to upload video to PeerTube.');
                        }
                    } catch (\Exception $e) {
                        $errors[] = tr('Error during upload: %0', $e->getMessage());
                    }
                }
            }

            if (! empty($errors)) {
                foreach ($errors as $error) {
                    \Feedback::error($error);
                }
            }
        }

        return $out;
    }
}

class_alias(\Services\Peertube\ServicesPeerTubeController::class, 'Services_PeerTube_Controller');
