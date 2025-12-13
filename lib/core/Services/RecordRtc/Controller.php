<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Package\VendorHelper;

class Services_RecordRtc_Controller
{
    public function setUp()
    {
        Services_Exception_Disabled::check('fgal_use_record_rtc_screen');
    }

    public function action_upload($input)
    {
        require_once('tiki-setup.php');

        global $prefs;

        $videoFilename = $input->videofilename->text();
        $audioFilename = $input->audiofilename->text();
        $ticket = $input->ticket->text();
        $galleryId = $input->galleryId->text() ?: $prefs['fgal_use_record_rtc_screen_gallery_id'];

        if (empty($audioFilename) && empty($videoFilename)) {
            throw new Services_Exception_NotFound('Empty file name.');
        }

        if (! empty($_FILES['audioblob'])) {
            $fileName = $audioFilename;
            $tempName = $_FILES['audioblob']['tmp_name'];
            $_FILES['data'] = $_FILES['audioblob'];
            $_FILES['data']['type'] = 'audio/webm';
        } else {
            $fileName = $videoFilename;
            $tempName = $_FILES['videoblob']['tmp_name'];
            $_FILES['data'] = $_FILES['videoblob'];
            $_FILES['data']['type'] = 'video/webm';
        }

        if (empty($fileName) || empty($tempName)) {
            if (empty($tempName)) {
                throw new Services_Exception_NotFound('Invalid temp_name: ' . $tempName);
                return;
            }

            throw new Services_Exception_NotFound('Invalid file name: ' . $fileName);
            return;
        }

        // make sure that one can upload only allowed audio/video files
        $allowed = [
            'webm',
            'wav',
            'mp4',
            'mkv',
            'mp3',
            'ogg'
        ];
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        if (! $extension || empty($extension) || ! in_array($extension, $allowed)) {
            throw new Services_Exception_NotFound('Invalid file extension: ' . $extension);
            return;
        }
        $_FILES['data']['name'] = $fileName;

        if ($galleryId) {
            $_FILES['data']['galleryId'] = $galleryId;
        }

        $files = new Services_File_Controller();
        $input = new JitFilter($_FILES['data']);
        $_POST['ticket'] = $ticket;

        $util = new Services_Utilities();
        $util->setTicket($ticket);
        $_POST['ticket'] = $ticket;

        try {
            $fileUpload = $files->action_upload($input);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        TikiLib::lib('access')->setTicket();

        if (! empty($fileUpload['fileId'])) {
            return $result = [
                'fileId'        => $fileUpload['fileId'],
                'nextTicket'    => TikiLib::lib('access')->getTicket()
            ];
        }
    }
}
