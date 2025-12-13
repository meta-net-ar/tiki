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
        'staticKeyFilters'                => [
            'calendarId'                  => 'int',               //post
            'offset'                      => 'digits',            //get
            'sort_mode'                   => 'alnumdash',         //get
            'description'                 => 'xss',               //post
            'drop'                        => 'int',               //get
            'remove_subscription'         => 'int',               //get
            'save'                        => 'alpha',             //post
            'customlanguages'             => 'alpha',             //post
            'customlocations'             => 'alpha',             //post
            'customparticipants'          => 'alpha',             //post
            'customcategories'            => 'alpha',             //post
            'custompriorities'            => 'alpha',             //post
            'customsubscription'          => 'alpha',             //post
            'personal'                    => 'alpha',             //post
            'private'                     => 'alpha',             //post
            'customstatus'                => 'alpha',             //post
            'startday_Meridian'           => 'digits',            //post
            'startday_Hour'               => 'digits',            //post
            'endday_Meridian'             => 'digits',            //post
            'endday_Hour'                 => 'digits',            //post
            'allday'                      => 'alpha',             //post
            'nameoneachday'               => 'alpha',             //post
            'copybuttononeachevent'       => 'alpha',             //post
            'name'                        => 'text',              //post
            'groupforAlert'               => 'groupname',         //post
            'showeachuser'                => 'alpha',             //post
            'clean'                       => 'alpha',             //post
            'days'                        => 'int',               //post
            'find'                        => 'text',              //post
            'savesub'                     => 'alpha',             //post
            'subscriptionId'              => 'int',               //post/get
            'sync_subscription'           => 'int',               //get
            'newstatus'                   => 'text',              //post
            'cookietab'                   => 'digits',            //get
        ],
        'staticKeyFiltersForArrays' => [
            'viewdays'              => 'int',     //post
            'show'                  => 'alpha',   //post
            'subscription'          => 'text',    //post
        ],
    ],
];
$section = 'admin';
require_once('tiki-setup.php');
$categlib = TikiLib::lib('categ');
$calendarlib = TikiLib::lib('calendar');
if ($prefs['feature_groupalert'] == 'y') {
    $groupalertlib = TikiLib::lib('groupalert');
}
$auto_query_args = ['calendarId', 'sort_mode', 'find', 'offset'];
$defaultstatus = ["Tentative", "Confirmed", "Cancelled"];
if (empty($_REQUEST["calendarId"])) {
    $access->check_permission_either(['tiki_p_admin_calendar', 'tiki_p_admin_private_calendar']);
    $_REQUEST['calendarId'] = 0;
} elseif ($_REQUEST['calendarId'] != 0) {
    if ($calendarlib->calendarExists($_REQUEST['calendarId'])) {
        $info = $calendarlib->get_calendar($_REQUEST['calendarId']);
        if (empty($info)) {
            Feedback::errorAndDie(tra('Incorrect param'), \Laminas\Http\Response::STATUS_CODE_409);
        }
        if (! $calendarlib->canAdminCalendar($info)) {
            $access->display_error('', tra('Permission denied') . ": " . 'tiki_p_admin_calendar', '403');
        }
    } else {
        Feedback::errorAndDie(tra('Incorrect param'), \Laminas\Http\Response::STATUS_CODE_409);
    }
}

$subscription_default = [
    'user'              => '',
    'subscriptionId'    => 0,
    'name'              => '',
    'source'            => '',
    'refresh_rate'      => 0,
    'order'             => 1,
    'color'             => '',
    'strip_todos'       => false,
    'strip_alarms'      => false,
    'strip_attachments' => false,
];

if (! empty($_REQUEST['subscriptionId'])) {
    $subscription = $calendarlib->get_subscription($_REQUEST['subscriptionId']);
    if ($subscription['user'] != $user) {
        $subscription = $subscription_default;
    }
    $smarty->assign('subscription', $subscription);
} else {
    $subscription = $subscription_default;
    $smarty->assign('subscription', $subscription);
}

if (isset($_REQUEST["drop"]) && $access->checkCsrf(true)) {
    $result = $calendarlib->drop_calendar($_REQUEST['calendarId']);
    if ($result->numRows()) {
        Feedback::success(tr('Calendar %0 deleted', (int) $_REQUEST['calendarId']));
    } else {
        Feedback::error(tr('Calendar %0 not deleted', (int) $_REQUEST['calendarId']));
    }
    $_REQUEST["calendarId"] = 0;
}
if (isset($_REQUEST["remove_subscription"]) && $access->checkCsrf(true)) {
    $subscription = $calendarlib->get_subscription($_REQUEST['remove_subscription']);
    if ($subscription['user'] == $user) {
        $client = new \Tiki\SabreDav\CaldavClient();
        $client->deleteSubscription($_REQUEST['remove_subscription']);
        Feedback::success(tr('Subscription %0 deleted', (int) $_REQUEST['remove_subscription']));
    }
}
if (isset($_REQUEST["save"]) && $access->checkCsrf()) {
    $customflags["customlanguages"] = $_REQUEST["customlanguages"];
    $customflags["customlocations"] = $_REQUEST["customlocations"];
    $customflags["customparticipants"] = $_REQUEST["customparticipants"];
    $customflags["customcategories"] = $_REQUEST["customcategories"];
    $customflags["custompriorities"] = $_REQUEST["custompriorities"];
    $customflags["customsubscription"] = $_REQUEST["customsubscription"] ?? 'n';
    if (isset($info)) {
        $objectperms = Perms::get('calendar', $info['calendarId']);

        if ($objectperms->admin_calendar) {
            $customflags["personal"] = $_REQUEST["personal"];
            $customflags["private"] = $_REQUEST["private"];
        } else {
            $customflags["personal"] = 'y';
            $customflags["private"] = 'y';
        }
    }
    $customflags['customstatus'] = $_REQUEST['customstatus'] ?? 'y';
    $options = $_REQUEST['options'];
    if (array_key_exists('customcolors', $options) && strPos($options['customcolors'], '-') > 0) {
        $customColors = explode('-', $options['customcolors']);
        if (! preg_match('/^[0-9a-fA-F]{3,6}$/', $customColors[0])) {
            $options['customfgcolor'] = '000000';
        } else {
            $options['customfgcolor'] = $customColors[0];
        }
        if (! preg_match('/^[0-9a-fA-F]{3,6}$/', $customColors[1])) {
            $options['custombgcolor'] = 'ffffff';
        } else {
            $options['custombgcolor'] = $customColors[1];
        }
    }
    if (! preg_match('/^[0-9a-fA-F]{3,6}$/', $options['customfgcolor'])) {
        $options['customfgcolor'] = '';
    }
    if (! preg_match('/^[0-9a-fA-F]{3,6}$/', $options['custombgcolor'])) {
        $options['custombgcolor'] = '';
    }
    //Convert 12-hour clock hours to 24-hour scale to compute time
    if (! empty($_REQUEST['startday_Meridian'])) {
        $_REQUEST['startday_Hour'] = date('H', strtotime($_REQUEST['startday_Hour'] . ':00 ' . $_REQUEST['startday_Meridian']));
    }
    if (! empty($_REQUEST['endday_Meridian'])) {
        $_REQUEST['endday_Hour'] = date('H', strtotime($_REQUEST['endday_Hour'] . ':00 ' . $_REQUEST['endday_Meridian']));
    }
    $options['startday'] = $_REQUEST['startday_Hour'] * 60 * 60;
    $options['endday'] = $_REQUEST['endday_Hour'] == 0 ? (24 * 60 * 60) - 1 : ($_REQUEST['endday_Hour'] * 60 * 60);
    $extra = [
        'calname',
        'description',
        'location',
        'description',
        'language',
        'category',
        'participants',
        'url',
        'status',
        'status_calview'
    ];
    foreach ($extra as $ex) {
        if (isset($_REQUEST['show'][$ex]) and $_REQUEST['show'][$ex] == 'on') {
            $options["show_$ex"] = 'y';
        } else {
            $options["show_$ex"] = 'n';
        }
    }
    if (isset($_REQUEST['viewdays'])) {
        $options['viewdays'] = $_REQUEST['viewdays'];
    }

    if (! empty($_REQUEST['newstatus'])) {
        if (trim($_REQUEST["newstatus"])) {
            $newstatus = trim($_REQUEST["newstatus"]);
            // the new custom status name must be different from default
            if (! in_array($newstatus, array_map('strtolower', $defaultstatus))) {
                // if it is an update
                if (isset($info["eventstatus"])) {
                    $info["eventstatus"][] = $newstatus;
                    $options["eventstatus"] = $info["eventstatus"];
                } else {
                    $defaultstatus[] = $newstatus;
                    $options["eventstatus"] = $defaultstatus;
                }
            }
        }
    } else {
        // if it is an update
        if (isset($info)) {
            if (! empty($info["eventstatus"])) {
                $options["eventstatus"] = $info["eventstatus"];
            } else {
                $options["eventstatus"] = $defaultstatus;
            }
        } else {
            $options["eventstatus"] = $defaultstatus;
        }
    }

    $options['allday'] = isset($_REQUEST['allday']) ? 'y' : 'n';
    $options['nameoneachday'] = isset($_REQUEST['nameoneachday']) ? 'y' : 'n';
    $options['copybuttononeachevent'] = isset($_REQUEST['copybuttononeachevent']) ? 'y' : 'n';
    $valid_custom_flags = array_map(function ($customflag) {
        $flag = strtolower(trim($customflag));
        if (! in_array($flag, ['n', 'y', 'yes'])) {
            $flag = 'n';
        }
        return substr($flag, 0, 1);
    }, $customflags);
    $_REQUEST["calendarId"] = $calendarlib->set_calendar($_REQUEST["calendarId"], $user, $_REQUEST["name"], $_REQUEST["description"], $valid_custom_flags, $options);
    $info = $calendarlib->get_calendar($_REQUEST['calendarId']);
    if ($prefs['feature_groupalert'] == 'y') {
        $groupalertlib->AddGroup('calendar', $_REQUEST["calendarId"], $_REQUEST['groupforAlert'], ! empty($_REQUEST['showeachuser']) ? $_REQUEST['showeachuser'] : 'n');
    }
    if ($info['personal'] === 'y' && $info['private'] === 'n') {
        $userlib->assign_object_permission("Registered", $_REQUEST["calendarId"], "calendar", "tiki_p_view_calendar");
        $userlib->assign_object_permission("Registered", $_REQUEST["calendarId"], "calendar", "tiki_p_view_events");
        $userlib->assign_object_permission("Registered", $_REQUEST["calendarId"], "calendar", "tiki_p_add_events");
        $userlib->assign_object_permission("Registered", $_REQUEST["calendarId"], "calendar", "tiki_p_change_events");
    }
    if ($prefs['feature_categories'] == 'y') {
        $cat_type = 'calendar';
        $cat_objid = $_REQUEST["calendarId"];
        $cat_desc = $_REQUEST["description"];
        $cat_name = $_REQUEST["name"];
        $cat_href = "tiki-calendar.php?calIds[]=" . $_REQUEST["calendarId"];
        include_once("categorize.php");
    }
    $cookietab = 1;
    $_REQUEST['calendarId'] = 0;
}
if (isset($_REQUEST['clean']) && isset($_REQUEST['days']) && $access->checkCsrf(true)) {
    $result = $calendarlib->cleanEvents($_REQUEST['calendarId'], $_REQUEST['days']);
    if ($result->numRows() === 1) {
        Feedback::success(tra('One calendar event deleted'));
    } elseif ($result->numRows() === 0) {
        Feedback::note(tra('No calendar events deleted'));
    } else {
        Feedback::success(tr('%0 calendar events deleted', $result->numRows()));
    }
}
if (isset($_REQUEST["savesub"]) && $access->checkCsrf()) {
    $subscription = $_REQUEST['subscription'];
    $client = new \Tiki\SabreDav\CaldavClient();
    if (empty($subscription['subscriptionId'])) {
        $subscription['user'] = $user;
        $client->createSubscription($subscription);
    } else {
        $existing = $calendarlib->get_subscription($subscription['subscriptionId']);
        if ($existing['user'] == $user) {
            $subscription['user'] = $user;
            $client->updateSubscription($subscription);
        }
    }
    $tasks = TikiLib::lib('scheduler')->get_scheduler(null, null, ['name' => 'System Synchronize Calendars']);
    if (! $tasks) {
        TikiLib::lib('scheduler')->set_scheduler('System Synchronize Calendars', 'System task to run calendar subscription synchornization task.', 'ConsoleCommandTask', '{"console_command":"calendar:sync"}', '* * * * *', 'active', 0, 0);
    }
    Feedback::success(tra('Calendar subscription saved.'));
}
if (! empty($_REQUEST['sync_subscription'])) {
    $subscriptionInfo = $calendarlib->get_subscription($_REQUEST['sync_subscription']);
    if (empty($subscriptionInfo)) {
        Feedback::error(tr('Calendar subscription not found.'));
    } else {
        try {
            $client = new \Tiki\SabreDav\CaldavClient();
            $client->syncSubscription($subscriptionInfo);
            Feedback::success(tra('Calendar data synchronized.'));
        } catch (Exception $e) {
            Feedback::error(tr('Error synchornizing remote calendar: %0', $e->getMessage()));
        }
    }
}
if ($prefs['feature_categories'] == 'y') {
    $cat_type = 'calendar';
    $cat_objid = $_REQUEST["calendarId"];
    include_once("categorize_list.php");
    $cs = $categlib->get_object_categories('calendar', $cat_objid);
    if (! empty($cs)) {
        for ($i = count($categories) - 1; $i >= 0; --$i) {
            if (in_array($categories[$i]['categId'], $cs)) {
                $categories[$i]['incat'] = 'y';
            }
        }
    }
}
if ($_REQUEST['calendarId'] != 0) {
    $cookietab = 2;
} else {
    $info = [];
    $info["name"] = '';
    $info["description"] = '';
    $info["customlanguages"] = 'n';
    $info["customlocations"] = 'n';
    $info["customparticipants"] = 'n';
    $info["customcategories"] = 'n';
    $info["custompriorities"] = 'n';
    $info["customsubscription"] = 'n';
    $info['customstatus'] = 'n';
    $info["customurl"] = 'n';
    $info["customfgcolor"] = '';
    $info["custombgcolor"] = '';
    $info["show_calname"] = 'y';
    $info["show_description"] = 'y';
    $info["show_category"] = 'n';
    $info["show_location"] = 'n';
    $info["show_language"] = 'n';
    $info["show_participants"] = 'n';
    $info["show_url"] = 'n';
    $info['show_status'] = 'n';
    $info['show_status_calview'] = '';
    $info["user"] = "$user";
    $info["personal"] = 'n';
    $info["private"] = 'n';
    $info["startday"] = ! empty($prefs['calendar_start_day']) ? $prefs['calendar_start_day'] : 25200;
    $info["endday"] = ! empty($prefs['calendar_end_day']) ? $prefs['calendar_end_day'] : 72000;
    $info["allday"] = '';
    $info["nameoneachday"] = '';
    $info["copybuttononeachevent"] = '';
    $info["defaulteventstatus"] = "Tentative";
    $info['viedays'] = $prefs['calendar_view_days'];
    $info["eventstatus"] = $defaultstatus;
    if (! empty($_REQUEST['show']) && $_REQUEST['show'] == 'mod') {
        $cookietab = 2;
    } else {
        if (! isset($cookietab)) {
            $cookietab = 1;
        }
    }
}
if ($prefs['feature_groupalert'] == 'y') {
    $info["groupforAlertList"] = [];
    $info["groupforAlert"] = $groupalertlib->GetGroup('calendar', $_REQUEST["calendarId"]);
    $all_groups = $userlib->list_all_groups();
    if (is_array($all_groups)) {
        foreach ($all_groups as $g) {
            $groupforAlertList[$g] = ($g == $info['groupforAlert']) ? 'selected' : '';
        }
    }
    $showeachuser = $groupalertlib->GetShowEachUser('calendar', $_REQUEST['calendarId'], $info['groupforAlert']);
    $smarty->assign('groupforAlert', $info['groupforAlert']);
    $smarty->assign_by_ref('groupforAlertList', $groupforAlertList);
    $smarty->assign_by_ref('showeachuser', $showeachuser);
}


if ($userlib->user_has_permission($user, 'tiki_p_add_events')) {
    TikiLib::lib('header')
         ->add_cssfile('themes/base_files/feature_css/calendar.css', 20)
         ->add_jsfile('lib/jquery_tiki/tiki-calendar_edit_item.js');
}

$smarty->assign('name', $info["name"]);
$smarty->assign('description', $info["description"]);
$smarty->assign('owner', $info["user"]);
$smarty->assign('customlanguages', $info["customlanguages"]);
$smarty->assign('customlocations', $info["customlocations"]);
$smarty->assign('customparticipants', $info["customparticipants"]);
$smarty->assign('customcategories', $info["customcategories"]);
$smarty->assign('custompriorities', $info["custompriorities"]);
$smarty->assign('customsubscription', $info["customsubscription"]);
$smarty->assign('customurl', $info["customurl"]);
$smarty->assign('customfgcolor', $info["customfgcolor"]);
$smarty->assign('custombgcolor', $info["custombgcolor"]);
$smarty->assign('customColors', $info["customfgcolor"] . "-" . $info["custombgcolor"]);
$smarty->assign('show_calname', $info["show_calname"]);
$smarty->assign('show_description', $info["show_description"]);
$smarty->assign('show_category', $info["show_category"]);
$smarty->assign('show_location', $info["show_location"]);
$smarty->assign('show_language', $info["show_language"]);
$smarty->assign('show_participants', $info["show_participants"]);
$smarty->assign('show_url', $info["show_url"]);
$smarty->assign('calendarId', $_REQUEST["calendarId"]);
$smarty->assign('personal', $info["personal"]);
$smarty->assign('private', $info["private"]);
$smarty->assign('startday', $info["startday"] < 0 ? 0 : $info['startday']);
$smarty->assign('endday', $info["endday"] < 0 ? 0 : $info['endday']);
//Use 12- or 24-hour clock for $publishDate time selector based on admin and user preferences
$userprefslib = TikiLib::lib('userprefs');
$smarty->assign('use_24hr_clock', $userprefslib->get_user_clock_pref($user));

$smarty->assign('defaulteventstatus', $info['defaulteventstatus']);
if (! isset($info["eventstatus"])) {
    $info["eventstatus"] = $defaultstatus;
}
$smarty->assign("eventstatus", $info["eventstatus"]);
//add translation tag to statuses for display
$info["eventstatusoutput"] = array_map(
    function ($status) {
        return tra($status);
    },
    $info["eventstatus"] ?? []
);
$smarty->assign("eventstatusoutput", $info["eventstatusoutput"]);
$smarty->assign_by_ref('info', $info);
if (! isset($_REQUEST["sort_mode"])) {
    $sort_mode = 'name_asc';
} else {
    $sort_mode = $_REQUEST["sort_mode"];
}
$smarty->assign_by_ref('sort_mode', $sort_mode);
$find = $_REQUEST["find"] ?? '';
$smarty->assign('find', $find);
$offset = $_REQUEST["offset"] ?? 0;
$smarty->assign_by_ref('offset', $offset);
$calendars = $calendarlib->list_calendars($offset, $maxRecords, $sort_mode, $find, '', true);
foreach (array_keys($calendars["data"]) as $i) {
    $calendars["data"][$i]["individual"] = $userlib->object_has_one_permission($i, 'calendar');
}
$smarty->assign_by_ref('count', $calendars['count']);
$smarty->assign_by_ref('calendars', $calendars["data"]);

$subscriptions = $calendarlib->get_subscriptions($user, $offset, $maxRecords, $sort_mode, $find);
$smarty->assign_by_ref('subscriptions', $subscriptions);

$days_names = [
    tra("Sunday"),
    tra("Monday"),
    tra("Tuesday"),
    tra("Wednesday"),
    tra("Thursday"),
    tra("Friday"),
    tra("Saturday")
];
$smarty->assign('days_names', $days_names);
include_once('tiki-section_options.php');
// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
// Display the template
$smarty->assign('mid', 'tiki-admin_calendars.tpl');
$smarty->display("tiki.tpl");
