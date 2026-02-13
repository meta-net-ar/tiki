<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Services_Kaltura_Controller
{
    public function setUp()
    {
        Services_Exception_Disabled::check('feature_kaltura');
    }

    /**
     * @param $input JitFilter
     *              sort_mode string   default desc_createdAt
     *              find string        unusued
     *              maxRecords int     entries per page
     *              offset int         for paging
     *              formId string      id of the form to add the media to
     *              targetName string  name of the target hidden input
     *
     * @return array
     * @throws Exception
     * @throws Services_Exception_Denied
     */
    public function action_list($input)
    {
        $perms = Perms::get();
        if (! $perms->upload_videos) {
            throw new Services_Exception_Denied('Not allowed to upload videos');
        }
        $sort_mode = $input->sort_mode->word() ?: 'desc_createdAt';
        $find = $input->find->text();   // TODO
        $page_size = $input->maxRecords->int() ?: -1;       // TODO paging $prefs['maxRecords'];
        $offset = max(0, $input->offset->int());
        $page = ($offset / $page_size) + 1;


        $kalturaadminlib = TikiLib::lib('kalturaadmin');
        $kmedialist = $kalturaadminlib->listMedia($sort_mode, $page, $page_size, $find);

        $out = [
            'entries' => $kmedialist->objects,
            'totalCount' => $kmedialist->totalCount,
            'formId' => $input->formId->text(),
            'targetName' => $input->targetName->text(),

        ];

        return $out;
    }

    /**
     * @param $input JitFilter
     *              targetName string  name of the target hidden input
     *              formId string      id of the form to add the media to
     *
     * @return array
     * @throws Services_Exception_Denied
     */
    public function actionUpload($input)
    {
        $perms = Perms::get();
        if (! $perms->upload_videos) {
            throw new Services_Exception_Denied(tr('Not allowed to upload videos'));
        }

        $targetName = $input->targetName->text();
        $out = [
            'formId' => $input->formId->text(),
            'targetName' => $targetName,
            'title' => tr('Upload Video'),
            'uploadInModal' => true,
            'name' => '',
            'description' => '',
            'tags' => '',
        ];

        // Get max upload size
        include 'lib/Filegals/max_upload_size.php';
        $out['max_upload_size'] = $max_upload_size;
        $out['max_upload_size_comment'] = $max_upload_size_comment;
        $out['is_iis'] = \Tiki\TikiInit::isIIS();

        // Handle POST upload
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['video'])) {
            $name = trim($input->name->text());
            $description = trim($input->description->text());
            $tags = trim($input->tags->text());

            // Repopulate form values on error
            $out['name'] = $name;
            $out['description'] = $description;
            $out['tags'] = $tags;

            $errors = [];

            if ($_FILES['video']['error'] !== UPLOAD_ERR_OK) {
                $errors[] = tr('File upload error: %0', $_FILES['video']['error']);
            } elseif ($_FILES['video']['size'] > $max_upload_size) {
                $errors[] = tr('File exceeds maximum size of %0', $max_upload_size_comment);
            } else {
                // Use shared validator
                require_once 'lib/core/Services/Video/Validator.php';
                $validationErrors = \Services\Video\Validator::validateMetadata($name, $description);
                $errors = array_merge($errors, $validationErrors);

                if (empty($errors)) {
                    $kalturalib = TikiLib::lib('kalturaadmin');

                    try {
                        $entry = $kalturalib->uploadVideo(
                            $_FILES['video']['tmp_name'],
                            $name,
                            $description,
                            $tags
                        );

                        if ($entry && ! empty($entry->id)) {
                            // Return the video ID and name in the format expected by the success handler
                            $out['entries'] = [$entry->id];
                            $out['entryNames'] = [$entry->name ?? $name];
                            $out['targetName'] = $targetName;
                            $out['extra'] = 'close';
                            $out['noTemplate'] = true;
                            return $out;
                        } else {
                            $errors[] = tr('Failed to upload video to Kaltura.');
                        }
                    } catch (Exception $e) {
                        $errors[] = tr('Error during upload: %0', $e->getMessage());
                    }
                }
            }

            if (! empty($errors)) {
                foreach ($errors as $error) {
                    Feedback::error($error);
                }
            }
        }

        return $out;
    }
}
