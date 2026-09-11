<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//this script may only be included - so its better to die if called directly.

if (str_contains($_SERVER['SCRIPT_NAME'], basename(__FILE__))) {
    header('location: index.php');
    exit;
}

//*** begin state-changing actions
if (! empty($_POST['createRelationsTracker'])) {
    $creator = new Tiki\Relation\SystemTrackerCreator();
    if ($creator->createRelationshipTracker($_POST['relationshipTrackerType'])) {
        Feedback::success(tr('Relationship metadata system tracker created.'));
    } else {
        Feedback::error(tr('Relationship type cannot be empty.'));
    }
}
//*** end state-changing actions

$headerlib->add_cssfile('themes/base_files/feature_css/admin.css');

$fieldPreferences = [];

foreach (Tracker_Field_Factory::getFieldTypes() as $type) {
    $fieldPreferences[] = array_shift($type['prefs']);
}

$smarty->assign('fieldPreferences', $fieldPreferences);
$smarty->assign('relationshipBehaviourList', array_keys(Tiki\Relation\Semantics::BEHAVIOUR_LIST));
