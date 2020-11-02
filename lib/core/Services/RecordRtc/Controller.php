<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use Tiki\Package\VendorHelper;

class Services_RecordRtc_Controller
{
	public function setUp()
	{
		global $prefs;

		$recordRtcVendor = VendorHelper::getAvailableVendorPath('recordrtc', 'npm-asset/recordrtc/RecordRTC.js');

		if ($prefs['fgal_use_record_rtc_screen'] != 'y' || empty($recordRtcVendor)) {
			throw new Services_Exception_Disabled('fgal_use_record_rtc_screen');
		}
	}

	public function action_upload($input)
	{
		require_once('tiki-setup.php');

		$videoFilename = $input->videofilename->text();
		$audioFilename = $input->audiofilename->text();
		$ticket = $input->ticket->text();

		if (empty($audioFilename) && empty($videoFilename)) {
			throw new Services_Exception_NotFound('Empty file name.');
		}

		if (! empty($_FILES['audio-blob'])) {
			$fileName = $audioFilename;
			$tempName = $_FILES['audioblob']['tmp_name'];
		} else {
			$fileName = $videoFilename;
			$tempName = $_FILES['videoblob']['tmp_name'];
			$_FILES['data'] = $_FILES['videoblob'];
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
		$_FILES['data']['type'] = ($extension == 'webm') ? 'video/webm' : $_FILES['data']['type'];

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

		if (! empty($fileUpload['fileId'])) {
			return $result = [
				'fileId' => $fileUpload['fileId']
			];
		}
	}
}
