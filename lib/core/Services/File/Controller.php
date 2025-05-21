<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Services_File_Controller
{
    private $defaultGalleryId = 1;
    /**
     * @var Services_File_Utilities $utilities
     */
    private $utilities;

    public function setUp()
    {
        global $prefs;

        if ($prefs['feature_file_galleries'] != 'y') {
            throw new Services_Exception_Disabled('feature_file_galleries');
        }
        $this->defaultGalleryId = $prefs['fgal_root_id'];
        $this->utilities = new Services_File_Utilities();
    }

    /**
     * Returns the section for use with certain features like banning
     * @return string
     */
    public function getSection()
    {
        return 'file_galleries';
    }

    /**
     * Call to prepare the upload in modal dialog, and then after the upload has happened
     * Here we add a description if that's enabled
     *
     * @param JitFilter $input
     * @return array
     * @throws Exception
     */
    public function action_uploader($input)
    {
        $gal_info = $this->checkTargetGallery($input);
        $filegallib = TikiLib::lib('filegal');

        $perms = Perms::get('tracker', $input->trackerId->int());

        $util = new Services_Utilities();
        if ($util->isActionPost()) {
            if ($input->offsetExists('description')) {
                $files = $input->asArray('file');
                $descriptions = $input->asArray('description');

                foreach ($files as $c => $file) {
                    $fileInfo = $filegallib->get_file_info($file);

                    if (isset($descriptions[$c])) {
                        $filegallib->update_file($fileInfo['fileId'], [
                            'name' => $fileInfo['filename'],
                            'description' => $descriptions[$c],
                            'lastModifUser' => $fileInfo['asuser'],
                        ]);
                    }
                }
            }
        }

        $return = [
            'galleryId'             => $gal_info['galleryId'],
            'limit'                 => abs($input->limit->int()),
            'typeFilter'            => $input->type->text(),
            'uploadInModal'         => $input->uploadInModal->int(),
            'files'                 => $this->getFilesInfo((array)$input->file->int()),
            'image_max_size_x'      => $input->image_max_size_x->text(),
            'image_max_size_y'      => $input->image_max_size_y->text(),
            'addDecriptionOnUpload' => $input->addDecriptionOnUpload->int(),
            'admin_trackers'        => $perms->admin_trackers,
            'requireTitle'          => $input->requireTitle->text(),
            'directoryPattern'      => $input->directoryPattern->text(),
        ];

        if ($input->uploadInModal->int()) {
            $return['title'] = tr('File Upload');
        }

        return $return;
    }

    public function action_upload($input)
    {
        if ($input->asArray('files')) {
            return [];
        }

        $gal_info = $this->checkTargetGallery($input);
        $fileId = $input->update->int() ? $input->fileId->int() : false;
        if (! empty($input->asArray('user')) && is_array($input->user->asArray())) {
            $asuser = $input->asArray('user')[0];
        }
        $title = $input->title->text();
        $description = $input->description->text() ?: '';
        $directoryPattern = $input->directoryPattern->text();
        $categlib = TikiLib::lib('categ');
        $categories = $fileId ? $categlib->get_object_categories('file', $fileId) : [];

        if (empty($asuser)) {
            $asuser = $GLOBALS['user'];
        }

        $perms = Perms::get('file gallery', $gal_info['galleryId']);
        if (! $perms->upload_files) {
            throw new Services_Exception_Denied();
        }

        $fileInfo = ($fileId) ? TikiLib::lib('filegal')->get_file_info($fileId) : false;
        if ($fileId && ! $fileInfo) {
            throw new Services_Exception_NotFound(tr('Requested file does not exist'));
        }

        if (! $input->imagesize->word()) {
            $image_x = $input->image_max_size_x->text();
            $image_y = $input->image_max_size_y->text();
        } else {
            $image_x = $gal_info["image_max_size_x"];
            $image_y = $gal_info["image_max_size_y"];
        }
        if (isset($_FILES['data'])) {
            // used by $this->action_upload_multiple and file gallery Files fields (possibly others)
            if (is_uploaded_file($_FILES['data']['tmp_name'])) {
                $_SESSION['lastUploadGalleryId'] = $gal_info["galleryId"];
                $file = new JitFilter($_FILES['data']);
                $name = $file->name->text();
                $size = $file->size->int();
                $type = $file->type->text();

                $data = file_get_contents($_FILES['data']['tmp_name']);
            } else {
                $message = $this->getFileUploadErrorMessage($_FILES['data']['error']);

                throw new Services_Exception_NotAvailable(tr($message));
            }
        } else {
            $name = $input->name->text();
            $size = $input->size->int();
            $type = $input->type->text();

            $data = $input->data->none();
            $data = base64_decode($data);
        }
        if (! $this->isTypeUploadable($type, $gal_info['type'])) {
            throw new Services_Exception(tr('File could not be uploaded: Type %0 not supported', $type), 406);
        }

        if (! $title) {
            $title = $name;
        }

        if ($directory = $input->directory->text()) {
            $gal_info = $this->utilities->findOrCreateDirectoryHierarchy($gal_info['galleryId'], $directory);
        }

        /* The above if/else sets $type using finfo_file(). The following uses finfo_buffer(), which gives a type different from that obtained from finfo_file() in the case of Outlook .msg files on PHP 5.6. In this case, finfo_file()'s result is better. It is not impossible that the technique below would give better results in other cases.
        See https://stackoverflow.com/questions/45243973/fileinfo-finfo-buffer-results-differ-from-finfo-file
        Chealer 2017-07-21
        $mimelib = TikiLib::lib('mime');
        $type = $mimelib->from_content($name, $data);
        */

        if (empty($fileId) && (empty($name) || $size == 0 || empty($data))) {
            $message = tr('File could not be uploaded:') . ' ';
            $error = error_get_last();

            if (empty($error)) {
                $message .= tr('File empty');
            } else {
                $message = $error['message'];
            }
            throw new Services_Exception(tr($message), 406);
        }
        $util = new Services_Utilities();
        // skip unsetting the CSRF ticket as multiple files might be dropped in the dropzone
        if ($util->isActionPost(false)) {
            if ($fileId) {
                // if we are updating a file, we need to get the missing file info from the database
                $size = $size ?: $fileInfo['filesize'];
                $type = $type ?: $fileInfo['filetype'];
                $name = $name ?: $fileInfo['filename'];
                $title = $title ?: $fileInfo['name'];
                $this->utilities->updateFile($gal_info, $name, $size, $type, $data, $fileId, $asuser, $title, $description);
            } else {
                $fileId = $this->utilities->uploadFile($gal_info, $name, $size, $type, $data, $asuser, $image_x, $image_y, $description, '', $title, $directoryPattern);
            }
        } else {
            $fileId = false;
        }

        if ($fileId === false) {
            throw new Services_Exception(tr('File could not be uploaded'), 406);
        }

        if ($input->deleteAfter->int() && $input->deleteAfter_unit->int()) {
            TikiLib::lib('filegal')->updateDeleteAfter($fileId, $input->deleteAfter->int() * $input->deleteAfter_unit->int());
        }

        $cat_type = 'file';
        $cat_objid = $fileId;
        $cat_desc = null;
        $cat_name = $name;
        $cat_href = "tiki-download_file.php?fileId=$fileId";

        if ($categories) {
            $_REQUEST['cat_categories'] = $categories;
            $_REQUEST["cat_categorize"] = 'on';
        }
        include('categorize.php');

        $filegallib = TikiLib::lib('filegal');
        $info = $filegallib->get_file_info($fileId);
        // when stored in the database the file contents is here and should not be sent back to the client
        $info['data'] = null;

        $util->setTicket();
        return [
            'size' => $size,
            'name' => $name,
            'title' => $title,
            'description' => $description,
            'type' => $type,
            'fileId' => $fileId,
            'galleryId' => $gal_info['galleryId'],
            'md5sum' => md5($data),
            'ticket' => $util->getTicket(),
            'syntax' => $filegallib->getWikiSyntax($gal_info['galleryId'], $info, $input->asArray()),
            'info' => $info,
        ];
    }

    /**
     * Uploads several files at once
     *
     * @param JitFilter $input
     * @return array
     * @throws Services_Exception
     * @throws Services_Exception_NotAvailable
     */
    public function action_upload_multiple($input)
    {
        global $user;
        $filegallib = TikiLib::lib('filegal');
        $output = ['files' => []];
        $util = new Services_Utilities();

        if (isset($_FILES['files']) && is_array($_FILES['files']['tmp_name']) && $util->checkCsrf()) {
            // a few other params that are still arrays but shouldn't be (mostly)
            if (! empty($input->asArray('galleryId')) && is_array($input->galleryId->asArray())) {
                $input->offsetSet('galleryId', $input->asArray('galleryId')[0]);
            }
            if (! empty($input->asArray('hit_limit')) && is_array($input->hit_limit->asArray())) {
                $input->offsetSet('hit_limit', $input->asArray('hit_limit')[0]);
            }
            if (! empty($input->asArray('isbatch')) && is_array($input->isbatch->asArray())) {
                $input->offsetSet('isbatch', $input->asArray('isbatch')[0]);
            }
            if (! empty($input->asArray('deleteAfter')) && is_array($input->deleteAfter->asArray())) {
                $input->offsetSet('deleteAfter', $input->asArray('deleteAfter')[0]);
            }
            if (! empty($input->asArray('deleteAfter_unit')) && is_array($input->deleteAfter_unit->asArray())) {
                $input->offsetSet('deleteAfter_unit', $input->asArray('deleteAfter_unit')[0]);
            }
            if (! empty($input->asArray('author')) && is_array($input->author->asArray())) {
                $input->offsetSet('author', $input->asArray('author')[0]);
            }
            if (! empty($input->asArray('user')) && is_array($input->user->asArray())) {
                $input->offsetSet('user', $input->asArray('user')[0]);
            }
            if (! empty($input->asArray('listtoalert')) && is_array($input->listtoalert->asArray())) {
                $input->offsetSet('listtoalert', $input->asArray('listtoalert')[0]);
            }

            $gal_info = $this->checkTargetGallery($input);

            for ($i = 0; $i < count($_FILES['files']['tmp_name']); $i++) {
                if (is_uploaded_file($_FILES['files']['tmp_name'][$i])) {
                    if (! $this->isTypeUploadable($_FILES['files']['type'][$i], $gal_info['type'])) {
                        throw new Services_Exception_NotAvailable(tr('File could not be uploaded: Type %0 not supported', $_FILES['files']['type'][$i]));
                    }

                    $_FILES['data']['name'] = $_FILES['files']['name'][$i];
                    $_FILES['data']['size'] = $_FILES['files']['size'][$i];
                    $_FILES['data']['type'] = $_FILES['files']['type'][$i];
                    $_FILES['data']['tmp_name'] = $_FILES['files']['tmp_name'][$i];

                    // do the actual upload
                    $file = $this->action_upload($input);

                    if (! empty($input->asArray('isbatch')) && $input->isbatch->word() && stripos($_FILES['data']['type'], 'zip') !== false) {
                        $errors = [];
                        $perms = Perms::get(['type' => 'file', 'object' => $file['fileId']]);
                        if ($perms->batch_upload_files) {
                            try {
                                $filegallib->process_batch_file_upload(
                                    $file['galleryId'],
                                    $_FILES['files']['tmp_name'][$i],
                                    $user,
                                    '',
                                    $errors
                                );
                            } catch (Exception $e) {
                                Feedback::error($e->getMessage());
                            }
                            if ($errors) {
                                Feedback::error(['mes' => $errors]);
                            } else {
                                $file['syntax'] = tr('Batch file processed: "%0"', $file['name']);  // cheeky?
                            }
                        } else {
                            Feedback::error(tra('You don\'t have permission to upload zipped file packages'));
                        }
                    }


                    $output['files'][] = $file;
                } else {
                    $message = $this->getFileUploadErrorMessage($_FILES['files']['error'][$i]);
                    throw new Services_Exception_NotAvailable(tr($message));
                }
            }

            if (! empty($input->asArray('autoupload')) && $input->autoupload->word()) {
                TikiLib::lib('user')->set_user_preference($user, 'filegals_autoupload', 'y');
            } else {
                TikiLib::lib('user')->set_user_preference($user, 'filegals_autoupload', 'n');
            }
        } else {
            throw new Services_Exception_NotAvailable(tr($this->buildFailedUploadErrorMessage()));
        }
        $util->setTicket();
        $output['ticket'] = $util->getTicket();

        return $output;
    }

    public function action_browse($input)
    {
        try {
            $gal_info = $this->checkTargetGallery($input);
        } catch (Services_Exception $e) {
            $gal_info = null;
        }
        $input->replaceFilter('file', 'int');
        $type = $input->type->text();

        return [
            'title' => tr('Browse'),
            'galleryId' => $input->galleryId->int(),
            'limit' => $input->limit->int(),
            'files' => $this->getFilesInfo($input->asArray('file', ',')),
            'typeFilter' => $type,
            'canUpload' => (bool) $gal_info,
            'list_view' => (substr($type, 0, 6) == 'image/') ? 'thumbnail_gallery' : 'list_gallery',
        ];
    }

    /**
     * @param $input    string "galleryId" for gallery to find
     * @return array    gallery info
     */
    public function action_find_gallery($input)
    {
        $gal_info = $this->checkTargetGallery($input);

        return [
            'canUpload' => (bool) $gal_info,
            'type' => $gal_info['type'],
            'image_max_size_x' => (int) $gal_info['image_max_size_x'],
            'image_max_size_y' => (int) $gal_info['image_max_size_y'],
        ];
    }

    public function action_thumbnail_gallery($input)
    {
        // Same as list gallery, different template
        return $this->action_list_gallery($input);
    }

    public function action_list_gallery($input)
    {
        $galleryId = $input->galleryId->int();

        /** @var UnifiedSearchLib $lib */
        $lib = TikiLib::lib('unifiedsearch');
        $query = $lib->buildQuery([
            'type' => 'file',
            'gallery_id' => (string) $galleryId,
        ]);

        if ($search = $input->search->text()) {
            $query->filterContent($search);
        }

        if ($typeFilter = $input->type->text()) {
            $query->filterContent($typeFilter, 'filetype');
        }

        $query->setRange($input->offset->int());
        $query->setOrder('title_asc');
        $result = $query->search($lib->getIndex());

        return [
            'title' => tr('Gallery List'),
            'galleryId' => $galleryId,
            'results' => $result,
            'plain' => $input->plain->int(),
            'search' => $search,
            'typeFilter' => $typeFilter,
        ];
    }

    public function action_remote($input)
    {
        global $prefs;
        if ($prefs['fgal_upload_from_source'] != 'y') {
            throw new Services_Exception(tr('Upload from source disabled.'), 403);
        }

        $gal_info = $this->checkTargetGallery($input);
        $url = $input->url->url();

        if (! $url) {
            return [
                'galleryId' => $gal_info['galleryId'],
            ];
        } else {
            $AllowedDomains = explode("\n", $prefs['fgal_upload_from_source_domains']);
            $host = parse_url($url, PHP_URL_HOST);

            if (! in_array($host, $AllowedDomains)) {
                throw new Services_Exception(tr('The domain %0 is not allowed to upload from', $host), 401);
            }
        }

        $filegallib = TikiLib::lib('filegal');

        if ($file = $filegallib->lookup_source($url)) {
            return $file;
        }

        $info = $filegallib->get_info_from_url($url);

        if (! $info) {
            throw new Services_Exception(tr('Unable to retrieve file from remote site. Please try a different URL.'), 412);
        }

        if ($input->reference->int()) {
            $info['data'] = 'REFERENCE';
        }

        $fileId = $this->utilities->uploadFile($gal_info, $info['name'], $info['size'], $info['type'], $info['data']);

        if ($fileId === false) {
            throw new Services_Exception(tr('File could not be uploaded. Restrictions apply.'), 406);
        }

        $filegallib->attach_file_source($fileId, $url, $info, $input->reference->int());

        return [
            'size' => $info['size'],
            'name' => $info['name'],
            'type' => $info['type'],
            'fileId' => $fileId,
            'galleryId' => $gal_info['galleryId'],
            'md5sum' => md5($info['data']),
        ];
    }

    public function action_refresh($input)
    {
        global $prefs;
        if ($prefs['fgal_upload_from_source'] != 'y') {
            throw new Services_Exception(tr('Upload from source disabled.'), 403);
        }

        if ($prefs['fgal_source_show_refresh'] != 'y') {
            throw new Services_Exception(tr('Manual refresh disabled.'), 403);
        }

        $filegallib = TikiLib::lib('filegal');
        $ret = $filegallib->refresh_file($input->fileId->int());

        return [
            'success' => $ret,
        ];
    }

    /**
     * @param $input    string "name" for the filename to find
     * @return array    file info for most recent file by that name
     */
    public function action_find($input)
    {

        $filegallib = TikiLib::lib('filegal');
        $gal_info = $this->checkTargetGallery($input);

        $name = $input->name->text();

        $pos = strpos($name, '?');      // strip off get params
        if ($pos !== false) {
            $name = substr($name, 0, $pos);
        }

        $info = $filegallib->get_file_by_name($gal_info['galleryId'], $name);

        if (empty($info)) {
            $info = $filegallib->get_file_by_name($gal_info['galleryId'], $name, 'filename');
        }
        unset($info['data']);

        return $info;
    }

    /**
     * Creates or updates a gallery
     * @param $input
     * @return array
     * @throws Services_Exception
     */
    public function action_create_update_gallery($input)
    {
        global $user, $prefs;

        $fileGallery = TikiLib::lib('filegal');
        $name = $input->name->text();
        $parentId = $input->parentId->int();
        $type = $input->type->text();
        $description = $input->description->text();

        if ($input->create->int()) {
            if (empty($name)) {
                throw new Services_Exception_MissingValue('name');
            }

            $perms = Perms::get('file gallery', $parentId);
            if (! $perms->admin_file_galleries) {
                throw new Services_Exception_Denied();
            }

            $info = [
                'name' => $name,
                'user' => $user,
                'type' => $type ?: 'default',
                'description' => $description ?: '',
                'parentId' => $parentId ?: $prefs['fgal_root_id']
            ];
        } else {
            $galleryId = $input->galleryId->int();
            if (empty($galleryId)) {
                throw new Services_Exception_MissingValue('galleryId');
            }

            $info = $fileGallery->get_file_gallery_info($galleryId);
            if (! $info) {
                throw new Services_Exception_NotFound();
            }

            $perms = Perms::get('file gallery', $galleryId);
            if (! $perms->admin_file_galleries) {
                throw new Services_Exception_Denied();
            }

            $info = [
                'name' => $name ?: $info['name'],
                'type' => $type ?: $info['type'],
                'description' => $description ?: $info['description'],
                'galleryId' => $galleryId,
                'parentId' => $info['parentId']
            ];
        }

        $newGalleryId = $fileGallery->replace_file_gallery($info);

        return [
            'info' => $fileGallery->get_file_gallery_info($newGalleryId)
        ];
    }

    /**
     * @param $input     int "id" ID of gallery to be removed or file in the gallery to be removed
     * @param $input     int "galleryId" The parent gallery of the gallery to be removed
     * @param $input     bool "recurse"
     * @return array
     * @throws Services_Exception_Denied
     */
    public function action_remove_file_gallery($input)
    {
        $id = $input->id->int();
        $galleryId = $input->galleryId->int() ?: 0;
        $recurse = $input->recurse ?: true;

        $perms = Perms::get('file gallery', $galleryId);

        if (! $perms->admin_file_galleries) {
            throw new Services_Exception_Denied();
        }

        $fileGallery = TikiLib::lib('filegal');
        $removed = $fileGallery->remove_file_gallery($id, $galleryId, $recurse);
        if (! $removed) {
            $msg = tr('An error occured while deleting the file gallery: %0', $id);
        } else {
            $msg = tr('The file gallery %0 has been deleted', $id);
        }

        return [
            'title' => tr('Delete File Gallery'),
            'message' => $msg
        ];
    }

    public function action_remove_file($input)
    {
        $fileGallery = TikiLib::lib('filegal');
        $fileId = $input->fileId->int();
        $fileInfo = $fileGallery->get_file_info($fileId);

        if (! $fileInfo) {
            throw new Services_Exception_NotFound(tr('Requested file does not exist'));
        }

        $perms = Perms::get('file gallery', $fileInfo['galleryId']);

        if (! $perms->admin_file_galleries) {
            throw new Services_Exception_Denied();
        }

        $removed = $fileGallery->remove_file($fileInfo);

        if (! $removed) {
            $msg = tr('An error occured while deleting the file : %0', $fileId);
        } else {
            $msg = tr('The file %0 has been deleted', $fileId);
        }

        return [
            'title' => tr('Delete File'),
            'message' => $msg
        ];
    }

    public function action_info($input)
    {
        $galleryId = $input->galleryId->int();

        $perms = Perms::get('file gallery', $galleryId);

        if (! $perms->view_file_gallery) {
            throw new Services_Exception_Denied();
        }

        $fileGallery = TikiLib::lib('filegal');

        return $fileGallery->get_file_gallery_info($galleryId);
    }

    public function action_list_files($input)
    {
        $galleryId = $input->galleryId->int();
        $offset = $input->offset->int();
        $maxRecords = $input->maxRecords->int();
        $sort_mode = $input->sort_mode->text() ?: 'created_desc';
        $find = isset($input->find) ? $input->find->text() : null;

        $perms = Perms::get('file gallery', $galleryId);

        if (! $perms->view_file_gallery) {
            throw new Services_Exception_Denied();
        }

        $fileGallery = TikiLib::lib('filegal');

        $result = $fileGallery->list_files($offset, $maxRecords, $sort_mode, $find, $galleryId);

        return [
            'title' => tr('List files'),
            'galleryId' => $galleryId,
            'offset' => $offset,
            'maxRecords' => $maxRecords,
            'count' => $result['count'],
            'result' => $result['data'],
        ];
    }

    public function action_list_galleries($input)
    {
        global $prefs;

        $galleryId = $input->galleryId->int() ?: $prefs['fgal_root_id'];
        $offset = $input->offset->int();
        $maxRecords = $input->maxRecords->int();
        $sort_mode = $input->sort_mode->text() ?: 'created_desc';
        $user = $input->user->text() ?: '';
        $find = isset($input->find) ? $input->find->text() : null;

        $perms = Perms::get('file gallery', $galleryId);

        if (! $perms->view_file_gallery) {
            throw new Services_Exception_Denied();
        }

        $fileGallery = TikiLib::lib('filegal');

        $result = $fileGallery->list_file_galleries($offset, $maxRecords, $sort_mode, $user, $find, $galleryId);

        return [
            'title' => tr('List Galleries'),
            'parentId' => $galleryId,
            'offset' => $offset,
            'maxRecords' => $maxRecords,
            'count' => $result['count'],
            'result' => $result['data'],
        ];
    }

    public function action_file_view($input)
    {
        $fileId = $input->fileId->int();

        $perms = Perms::get('file', $fileId);

        if (! $perms->view_file) {
            throw new Services_Exception_Denied();
        }

        return [
            'title' => tr('File Info'),
            'fileId' => $fileId,
            'info' => TikiLib::lib('filegal')->get_file_info($fileId)
        ];
    }

    public function action_download($input)
    {
        $fileId = $input->fileId->int();

        // Check if the user has permission to download the file
        $perms = Perms::get('file', $fileId);
        if (! $perms->download_files) {
            throw new Services_Exception_Denied();
        }

        $fileGallery = TikiLib::lib('filegal');
        $fileInfo = $fileGallery->get_file_info($fileId);

        if (! $fileInfo) {
            throw new Services_Exception_NotFound();
        }

        $file = new \Tiki\FileGallery\File($fileInfo);

        // Set headers for file download
        header('Content-Type: ' . $file->filetype);
        header('Content-Disposition: attachment; filename="' . $file->filename . '"');
        header('Content-Length: ' . $file->filesize);

        $fileContents = $file->getContents();

        if (empty($fileContents)) {
            $fileContents = $file->data;
        }

        echo $fileContents;

        exit; //Make sure nothing else is sent after the file data
    }

    public function action_move_file_gallery($input)
    {
        $galleryId = $input->galleryId->int();
        $new_parent_id = $input->newParentId->int();

        if (empty($galleryId)) {
            throw new Services_Exception_MissingValue('galleryId');
        }

        if (empty($new_parent_id)) {
            throw new Services_Exception_MissingValue('newParentId');
        }

        $perms = Perms::get('file gallery', $galleryId);

        if (! $perms->admin_file_galleries) {
            throw new Services_Exception_Denied();
        }

        $fileGallery = TikiLib::lib('filegal');
        $moved = $fileGallery->move_file_gallery($galleryId, $new_parent_id);

        if (! $moved) {
            $msg = tr('An error occured while moving the file gallery: %0', $galleryId);
        } else {
            $msg = tr('The file gallery %0 has been moved', $galleryId);
        }

        return [
            'title' => tr('Move File Gallery'),
            'message' => $msg
        ];
    }

    public function action_duplicate_file($input)
    {
        $fileId = $input->fileId->int();
        $galleryId = $input->galleryId->int() ?: null;
        $newName = $input->newName->text() ?: false;
        $description = $input->description->text() ?: '';

        $perms = Perms::get('file gallery', $fileId);

        if (! $perms->admin_file_galleries) {
            throw new Services_Exception_Denied();
        }

        $fileGallery = TikiLib::lib('filegal');

        if ($galleryId) {
            $fileGalleryInfo = $fileGallery->get_file_gallery_info($galleryId);
            if (! $fileGalleryInfo) {
                throw new Services_Exception_NotFound(tr('Requested file gallery does not exist'));
            }
        }

        $newFileId = $fileGallery->duplicate_file($fileId, $galleryId, $newName, $description);

        return [
            'title' => tr('Duplicate file'),
            'message' => 'File duplicated successfully',
            'id' => $newFileId
        ];
    }

    public function action_duplicate_file_gallery($input)
    {
        $galleryId = $input->galleryId->int();
        $name = $input->name->text();
        $description = $input->description->text() ?: '';

        if (empty($galleryId)) {
            throw new Services_Exception_MissingValue('galleryId');
        }

        if (empty($name)) {
            throw new Services_Exception_MissingValue('name');
        }

        $perms = Perms::get('file gallery', $galleryId);

        if (! $perms->admin_file_galleries) {
            throw new Services_Exception_Denied();
        }

        $fileGallery = TikiLib::lib('filegal');
        $newGalleryId = $fileGallery->duplicate_file_gallery($galleryId, $name, $description);

        return [
            'title' => tr('Duplicate File Gallery'),
            'message' => 'File Gallery duplicated successfully',
            'id' => $newGalleryId
        ];
    }

    public function action_lock_files($input)
    {
        global $user;
        $fileIDs = $input->asArray('items');

        $fileGallery = TikiLib::lib('filegal');
        $result = [];

        foreach ($fileIDs as $id) {
            $perms = Perms::get('file gallery', $id);

            if ($perms->admin_file_galleries) {
                $locked = $fileGallery->lock_file($id, $user);
                if ($locked->numrows) {
                    $result[] = $id;
                }
            }
        }

        return [
            'title' => tr('Lock files'),
            'count' => count($result),
            'locked' => $result
        ];
    }

    public function action_unlock_files($input)
    {
        $fileIDs = $input->asArray('items');

        $fileGallery = TikiLib::lib('filegal');
        $result = [];

        foreach ($fileIDs as $id) {
            $perms = Perms::get('file gallery', $id);

            if ($perms->admin_file_galleries) {
                $unlocked = $fileGallery->unlock_file($id);
                if ($unlocked->numrows) {
                    $result[] = $id;
                }
            }
        }

        return [
            'title' => tr('Unlock files'),
            'count' => count($result),
            'unlocked' => $result
        ];
    }

    private function checkTargetGallery($input)
    {
        if ($input->fileId->int()) {
            $fileInfo = TikiLib::lib('filegal')->get_file_info($input->fileId->int());
            $galleryId = $fileInfo ? $fileInfo['galleryId'] : $this->defaultGalleryId;
        } else {
            $galleryId = $input->galleryId->int() ?: $this->defaultGalleryId;
        }

        // Patch for uninitialized utilities.
        //  The real problem is that setup is not called
        if ($this->utilities == null) {
            $this->utilities = new Services_File_Utilities();
        }

        return $this->utilities->checkTargetGallery($galleryId);
    }

    private function getFilesInfo($files)
    {
        return array_map(function ($fileId) {
            return TikiDb::get()->table('tiki_files')->fetchRow(['fileId', 'name' => 'filename', 'label' => 'name', 'type' => 'filetype'], ['fileId' => $fileId]);
        }, array_filter($files));
    }

    private function isTypeUploadable($mimeType, $galleryType)
    {
        $canUpload = true;

        if ($galleryType == 'vidcast' && ! preg_match('/^video\/./', $mimeType)) {
            $canUpload = false;
        } elseif ($galleryType == 'podcast' && ! preg_match('/^audio\/./', $mimeType)) {
            $canUpload = false;
        }

        return $canUpload;
    }

    private function getFileUploadErrorMessage($error)
    {
        $message = tr('File could not be uploaded:') . ' ';

        switch ($error) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                $message .= tr('No file arrived');
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $message .= tr('File too large');
                break;
            default:
                $message .= tr('Unknown errors');
                break;
        }

        return $message;
    }

    private function buildFailedUploadErrorMessage()
    {
        require_once __DIR__ . '/../../../smarty_tiki/modifier.kbsize.php';

        $tikilib = TikiLib::lib('tiki');
        $maxPostSize = $tikilib->return_bytes(ini_get('post_max_size'));

        if (isset($_SERVER['CONTENT_LENGTH']) && (int) $_SERVER['CONTENT_LENGTH'] > $maxPostSize) {
            $message = tr('Uploaded data is larger than the max post size, uploaded data should not exceed %0', smarty_modifier_kbsize($maxPostSize, true, 0));
        } else {
            $message = tr('File could not be uploaded.');
        }

        return $message;
    }
}
