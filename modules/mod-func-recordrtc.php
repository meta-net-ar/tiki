<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use Tiki\Package\VendorHelper;

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
	header('location: index.php');
	exit;
}

/**
 * @return array
 */
function module_recordrtc_info()
{
	return [
		'name' => tra('Record RTC'),
		'description' => tra('Multi-purpose search module (go or edit page by name and/or search site)'),
		'prefs' => ['fgal_use_record_rtc_screen'],
		'packages_required' => ['npm-asset/recordrtc' => VendorHelper::getAvailableVendorPath('recordrtc', '/npm-asset/recordrtc/RecordRTC.js')],
	];
}

/**
 * @param $mod_reference
 * @param $smod_params
 */
function module_recordrtc($mod_reference, $smod_params) 	// modifies $smod_params so uses & reference
{
	$smarty = TikiLib::lib('smarty');
	global $prefs, $user;

	$smarty->assign('show_recordrtc_module', true);
	if (! isset($user)) {
		$smarty->assign('show_recordrtc_module', false);
		return;
	}

	$https = $_SERVER['REQUEST_SCHEME'] === 'https' ? true : false;
	if (! $https) {
		$smarty->assign('module_error', tra('Record RTC requires https connection over SSL'));
		return;
	}

	$recordRtcService = new Services_Recordrtc_Controller();
	$recordRtcService->setUp();

	$recordRtcVendor = VendorHelper::getAvailableVendorPath('recordrtc', 'npm-asset/recordrtc/RecordRTC.js');
	if ($prefs['fgal_use_record_rtc_screen'] !== 'y' || empty($recordRtcVendor)) {
		$smarty->assign('module_error', tra('Record RTC is not available.'));
		return;
	}

	$headerlib = TikiLib::lib('header');
	$headerlib->add_jsfile('vendor/npm-asset/recordrtc/RecordRTC.js', true);
	$headerlib->add_jsfile('lib/jquery_tiki/recordrtc.js', true);
	$headerlib->add_jsfile('vendor_bundled/vendor/moment/moment/min/moment.min.js', true);

	$smarty->assign('module_error', '');
	$smarty->assign_by_ref('smod_params', $smod_params);
}
