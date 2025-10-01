<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

require_once('tiki-setup.php');
$access->check_feature('feature_trackers');

$trklib = TikiLib::lib('trk');
$definitions = Tracker_Definition::getAll();

if (empty($definitions)) {
    Feedback::errorAndDie(tra('No tracker found'), \Laminas\Http\Response::STATUS_CODE_404);
}

$requestedTrackerIds = $_REQUEST["trackerIds"] ?? [];
$smarty->assign('requestedTrackerIds', $requestedTrackerIds);
if (! is_array($requestedTrackerIds)) {
    Feedback::errorAndDie(tra('The trackerIds parameter must be an array of (possibly just one) tracker ids'), \Laminas\Http\Response::STATUS_CODE_400);
}

$skipAttributes = ! empty($_REQUEST["skipAttributes"]) ?? false;
$skipRelations = ! empty($_REQUEST["skipRelations"]) ?? false;
$includePermNames = ! empty($_REQUEST["includePermNames"]);

$skipAttributesChecked = $skipAttributes ? 'checked' : '';
$skipRelationsChecked = $skipRelations ? 'checked' : '';
$includePermNamesChecked = $includePermNames ? 'checked' : '';

$smarty->assign('skipAttributesChecked', $skipAttributesChecked);
$smarty->assign('skipRelationsChecked', $skipRelationsChecked);
$smarty->assign('includePermNamesChecked', $includePermNamesChecked);

$availableTrackers = [];

foreach ($definitions as $tracker) {
    if (is_object($tracker) && method_exists($tracker, 'getID') && method_exists($tracker, 'getInformation')) {
        $tikilib->get_perm_object($tracker->getID(), 'tracker', $tracker->getInformation());
        $access->check_permission('tiki_p_export_tracker', tra('Export Tracker'), 'tracker', $tracker->getID());
        $availableTrackers[$tracker->getID()] = $tracker;
    } else {
        Feedback::error("Invalid tracker detected: " . var_export($tracker, true));
    }
}
$urlParameterStringToGoBackToListOfTrackers = '';
if (! $requestedTrackerIds) {
    //If no specific trackers were requested, add them all
    $trackerIds = array_keys($availableTrackers);
} else {
    $trackerIds = $requestedTrackerIds;
    for ($i = 0; $i < count($requestedTrackerIds); $i++) {
        $urlParameterStringToGoBackToListOfTrackers .= "trackerIds[]=" . $requestedTrackerIds[$i] . "&";
    }
    //Remove the last &
    $urlParameterStringToGoBackToListOfTrackers = substr_replace($urlParameterStringToGoBackToListOfTrackers, "", -1);
}
$headerlib->add_jsfile('lib/jquery_tiki/tiki-export_tracker_schema.js');

$smarty = TikiLib::lib('smarty');
require_once("export-tracker_schema.php");
$mermaidText = exportMermaidER($title, $entities, $relationships, $skipAttributes, $includePermNames);
$mermaidOutput = handleMermaid($mermaidText);
if (isset($_REQUEST['export'])) {
    $export = $_REQUEST['export'];
} else {
    $export = 'svgFormat';
}

$mermaidText = exportMermaidER($title, $entities, $relationships, $skipAttributes, $includePermNames, true);
$smarty->assign('export', $export);
$smarty->assign('urlParameterStringToGoBackToListOfTrackers', $urlParameterStringToGoBackToListOfTrackers);
$smarty->assign('textPlain', $mermaidText);
$smarty->assign('contentmain', $mermaidOutput);
$smarty->assign('mid', 'tiki-export_tracker_schema.tpl');
$smarty->display('tiki.tpl');
