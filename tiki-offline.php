<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Package\VendorHelper;

require_once('tiki-setup.php');
use Tiki\Sections;
$section = Sections::SECTION_TRACKERS;
Sections::setCurrentSection($section);
$access->check_feature('feature_trackers');

if ($prefs['pwa_feature'] !== 'y') {
    $headerlib->add_jsfile(VendorHelper::getAvailableVendorPath('dexie', 'npm-asset/dexie/dist/dexie.min.js'), true);
    $headerlib->add_jsfile("lib/pwa/app.js");
    $smarty->assign('pagespwa', json_encode(['trackers' => [], 'wiki' => [], 'urls' => []]));
}

$headerlib->add_jsfile("lib/pwa/trackers.js");

TikiLib::lib('header')->add_js_module('
    import "@vue-mf/root-config";
    import "@vue-mf/tiki-offline";
    import "@vue-widgets/el-date-picker";
    import handleDatePicker from "@tiki/ui-utils";
');

$smarty->assign('mid', 'tiki-offline.tpl');
$smarty->display('tiki.tpl');
