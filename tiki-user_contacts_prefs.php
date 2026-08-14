<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$inputConfiguration = [
    [
        'staticKeyFilters'           => [
        'prefs'                      => 'bool',              //post
        'user_contacts_default_view' => 'alpha',              //post
        'ext_remove'                 => 'int',               //get
        'ext_add'                    => 'striptags',            //post
        'ext_show'                   => 'int',               //get
        'ext_hide'                   => 'int',               //get
        'ext_public'                 => 'int',               //get
        'ext_private'                => 'int',               //get
        'ext_up'                     => 'int',               //get
        'ext_down'                   => 'int',               //get
        ],
    ],
];
require_once('tiki-setup.php');
use Tiki\Sections;
$section = Sections::SECTION_MY_TIKI;
Sections::setCurrentSection($section);
$contactlib = TikiLib::lib('contact');

$access->check_feature('feature_contacts', '', 'community');
$access->check_user($user);

if (! isset($cookietab)) {
    $cookietab = '1';
}
if (isset($_REQUEST['prefs'])) {
    $tikilib->set_user_preference($user, 'user_contacts_default_view', $_REQUEST['user_contacts_default_view']);
    $cookietab = '1';
}
$smarty->assign('user_contacts_default_view', $tikilib->get_user_preference($user, 'user_contacts_default_view'));
if (isset($_REQUEST['ext_remove'])) {
    $ext = $contactlib->get_ext($_REQUEST['ext_remove']);
    $contactlib->remove_ext($user, $_REQUEST['ext_remove']);
    $cookietab = 2;
    Feedback::success(tr('Field %0 was successfully removed.', $ext['fieldname']));
}
if (isset($_REQUEST['ext_add'])) {
    $cookietab = 2;
    $rawField = trim($_REQUEST['ext_add']);
    if ($rawField === '') {
        Feedback::error(tra('Field name cannot be empty.'));
    } elseif (mb_strlen($rawField) > 100) {
        Feedback::error(tra('Field name is too long. Please use 100 characters or less.'));
    } elseif ($contactlib->get_ext_by_name($user, $rawField)) {
        Feedback::error(sprintf(tra('Field name %s already exists.'), $rawField));
    } else {
        $contactlib->add_ext($user, $rawField);
        Feedback::success(sprintf(tra('Field %s was added.'), $rawField));
    }
}
if (isset($_REQUEST['ext_show'])) {
    $contactlib->modify_ext($user, $_REQUEST['ext_show'], ['show' => 'y']);
    $cookietab = 2;
}
if (isset($_REQUEST['ext_hide'])) {
    $contactlib->modify_ext($user, $_REQUEST['ext_hide'], ['show' => 'n']);
    $cookietab = 2;
}
if (isset($_REQUEST['ext_public'])) {
    $contactlib->modify_ext($user, $_REQUEST['ext_public'], ['flagsPublic' => 'y']);
    $cookietab = 2;
}
if (isset($_REQUEST['ext_private'])) {
    $contactlib->modify_ext($user, $_REQUEST['ext_private'], ['flagsPublic' => 'n']);
    $cookietab = 2;
}
$extList = $contactlib->get_ext_list($user);
$exts = &$extList;
$nb_exts = count($exts);
// consistancy check
foreach ($exts as $k => $ext) {
    if ($ext['order'] != $k) {
        $contactlib->modify_ext($user, $ext['fieldId'], ['order' => $k]);
    }
}
if (isset($_REQUEST['ext_up'])) {
    if (is_array($exts)) {
        foreach ($exts as $k => $ext) {
            if ($k > 0 && $ext['fieldId'] == $_REQUEST['ext_up']) {
                $contactlib->modify_ext($user, $_REQUEST['ext_up'], ['order' => $k - 1]);
                $contactlib->modify_ext($user, $exts[$k - 1]['fieldId'], ['order' => $k]);
                break;
            }
        }
    }
    $cookietab = 2;
}
if (isset($_REQUEST['ext_down'])) {
    if (is_array($exts)) {
        foreach ($exts as $k => $ext) {
            if ($k < $nb_exts && $ext['fieldId'] == $_REQUEST['ext_down']) {
                $contactlib->modify_ext($user, $_REQUEST['ext_down'], ['order' => $k + 1]);
                $contactlib->modify_ext($user, $exts[$k + 1]['fieldId'], ['order' => $k]);
                break;
            }
        }
    }
    $cookietab = 2;
}
$exts = $contactlib->get_ext_list($user);
$smarty->assign('exts', $exts);

include_once('tiki-mytiki_shared.php');
include_once('tiki-section_options.php');
$smarty->assign('mid', 'tiki-user_contacts_prefs.tpl');
$smarty->display("tiki.tpl");
