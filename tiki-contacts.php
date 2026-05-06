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
        'staticKeyFilters'            => [
            'contactId'               => 'int',           // post
            'remove'                  => 'int',           // post
            'save'                    => 'bool',          // post
            'firstName'               => 'striptags',     // post
            'lastName'                => 'striptags',     // post
            'email'                   => 'email',         // post
            'nickname'                => 'striptags',     // post
            'sort_mode'               => 'word',           // get
            'offset'                  => 'int',           // get
            'find'                    => 'string',     // post
            'maxRecords'              => 'int',           // get
            'initial'                 => 'string',         // get
            'view'                    => 'striptags',           // get
        ],
        'staticKeyFiltersForArrays'   => [
            'groups'                  => 'groupname',     // post
        ],
    ],
];
require_once('tiki-setup.php');
use Tiki\Sections;
$section = Sections::SECTION_CONTACTS;
Sections::setCurrentSection($section);
$access->check_feature('feature_contacts');
$contactlib = TikiLib::lib('contact');

$auto_query_args = [
    'contactId',
    'view',
    'maxRecords',
    'find',
    'sort_mode',
    'offset',
    'initial'
];

if (! isset($_REQUEST["contactId"])) {
    $_REQUEST["contactId"] = 0;
}
$smarty->assign('contactId', $_REQUEST["contactId"]);

$exts = $contactlib->get_ext_list($user);
$traducted_exts = [];
foreach ($exts as $ext) {
    $traducted_exts[$ext['fieldId']] = [
        'tra' => tra($ext['fieldname']),
        'art' => $ext['fieldname'],
        'id' => $ext['fieldId'],
        'show' => $ext['show'],
        'public' => $ext['flagsPublic']
    ];
}

if ($_REQUEST["contactId"]) {
    $info = $contactlib->get_contact($_REQUEST["contactId"], $user);
    foreach ($info['ext'] as $k => $v) {
        if (! in_array($k, array_keys($exts))) {
            // okay, we need to grab the name from exts[], where fieldId = $k
             $ext = $contactlib->get_ext($k);
            $traducted_exts[$k]['tra'] = $ext['fieldname'];
            $traducted_exts[$k]['art'] = $ext['fieldname'];
            $traducted_exts[$k]['id'] = $k;
            $traducted_exts[$k]['public'] = $ext['flagsPublic'];
        }
    }
} else {
    $info = [];
    $info["firstName"] = '';
    $info["lastName"] = '';
    $info["email"] = '';
    $info["nickname"] = '';
    $info["groups"] = [];
}
$smarty->assign('info', $info);
$smarty->assign('exts', $traducted_exts);

if (isset($_REQUEST["remove"]) && $access->checkCsrf()) {
    $access->check_user($user);
    $contactlib->remove_contact($_REQUEST["remove"], $user);
}

if (isset($_REQUEST["save"])) {
    $access->check_user($user);
    $access->checkCsrf();
    $ext_result = [];
    foreach ($exts as $ext) {
        $ext_result[$ext['fieldId']] = $_REQUEST['ext_' . $ext['fieldId']] ?? '';
    }
    if (empty($_REQUEST["firstName"])) {
        $feedback = tra('You must specify the contact name.');
        Feedback::error($feedback);
    } else {
        $contactlib->replace_contact($_REQUEST["contactId"], $_REQUEST["firstName"], $_REQUEST["lastName"], $_REQUEST["email"], $_REQUEST["nickname"], $user, $_REQUEST['groups'] ?? [], $ext_result);
        if ($_REQUEST["contactId"]) {
            $feedback = tr('Contact for %0 %1 was edited.', $_REQUEST['firstName'], $_REQUEST['lastName']);
        } else {
            $feedback = tr('Contact for %0 %1 was created.', $_REQUEST['firstName'], $_REQUEST['lastName']);
        }
        Feedback::success($feedback);
        $info["firstName"] = '';
        $info["lastName"] = '';
        $info["email"] = '';
        $info["nickname"] = '';
        $info["groups"] = [];
        $smarty->assign('info', $info);
        $smarty->assign('contactId', 0);
    }
}
$sort_mode = $_REQUEST["sort_mode"] ?? 'email_asc';
$offset = $_REQUEST["offset"] ?? 0;
$find = $_REQUEST["find"] ?? '';
if (! empty($_REQUEST['maxRecords'])) {
    $maxRecords = $_REQUEST['maxRecords'];
} else {
    $maxRecords = $prefs['maxRecords'] ?? 20;
}
$initial = $_REQUEST["initial"] ?? '';

$smarty->assign_by_ref('sort_mode', $sort_mode);
$smarty->assign_by_ref('offset', $offset);
$smarty->assign('find', $find);

$contacts = $contactlib->list_contacts($user, $offset, $maxRecords, $sort_mode, $find, true, $initial);
$count = $contactlib->list_contacts($user, -1, -1, $sort_mode, $find, true, $initial);
$count = is_array($count) ? count($count) : 0;

$_SESSION['UserContactsView'] = $_REQUEST['view'] ??
($_SESSION['UserContactsView'] ?? $userlib->get_user_preference($user, 'user_contacts_default_view'));

$smarty->assign('view', $_SESSION['UserContactsView']);

if (is_array($contacts)) {
    foreach ($contacts as $key => $contact) {
        if (array_key_exists('ext', $contact)) {
            foreach ($contact['ext'] as $extVal) {
                // Checking for work email
                if (filter_var($extVal, FILTER_VALIDATE_EMAIL)) {
                    $contacts[$key]['workEmail'] = $extVal;
                    break;
                }
            }
        }
    }

    if ($_SESSION['UserContactsView'] == 'list') {
        $smarty->assign('all', [$contacts]);
    } else {
        // ordering contacts by groups
        $all = [];
        $all_personnal = [];

        foreach ($contacts as $c) {
            if (array_key_exists('groups', $c) && is_array($c['groups'])) {
                foreach ($c['groups'] as $g) {
                    $all[$g][] = $c;
                }
            }

            if ($c['user'] == $user) {
                $all_personnal[] = $c;
            }
        }

        // sort contacts by group name
        ksort($all);

        // this group needs to be the last one
        $all['user_personal_contacts'] =& $all_personnal;

        $smarty->assign('all', $all);
    }
}

$groups = $userlib->get_user_groups($user);
$smarty->assign('groups', $groups);

$smarty->assign('initial', range('a', 'z'));
$smarty->assign('setInitial', $initial);
$smarty->assign('maxRecords', $maxRecords);
$smarty->assign('total_contact', $count);

include_once('tiki-section_options.php');

$smarty->assign('myurl', 'tiki-contacts.php');
$smarty->assign('mid', 'tiki-contacts.tpl');
$smarty->display('tiki.tpl');
