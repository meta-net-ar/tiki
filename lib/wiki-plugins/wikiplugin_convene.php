<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use function Sabre\Uri\split;

function wikiplugin_convene_info(): array
{
    return [
        'name' => tra('Convene'),
        'documentation' => 'PluginConvene',
        'description' => tra('Suggest meeting dates and times and vote to select one'),
        'introduced' => 9,
        'prefs' => ['wikiplugin_convene','feature_calendar'],
        'body' => tra('Convene data generated from user input'),
        'iconname' => 'group',
        'filter' => 'rawhtml_unsafe',
        'tags' => [ 'basic' ],
        'format' => 'html',
        'params' => [
            'title' => [
                'required' => false,
                'name' => tra('Title'),
                'description' => tra('Title for the event'),
                'since' => '9.0',
                'default' => tra('Convene'),
            ],
            'calendarid' => [
                'required' => false,
                'name' => tra('Calendar ID'),
                'description' => tra('ID number of the site calendar in which to store the date for the events with the most votes'),
                'since' => '9.0',
                'filter' => 'digits',
                'default' => 1,
                'profile_reference' => 'calendar',
            ],
            'minvotes' => [
                'required' => false,
                'name' => tra('Minimum Votes'),
                'description' => tra('Minimum number of votes needed to show Add-to-Calendar icon, so that new users do
                    not see a potentially confusing icon before the convene has enough information on it'),
                'since' => '10.3',
                'filter' => 'digits',
                'default' => 3,
            ],
            'dateformat' => [
                'required' => false,
                'name' => tra('Date-Time Format'),
                'description' => tra('Display date and time in short or long format, according to the site wide setting (or use "other" to specify a custom format below)'),
                'since' => '9.0',
                'filter' => 'alpha',
                'default' => 'short',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Short'), 'value' => 'short'],
                    ['text' => tra('Long'), 'value' => 'long'],
                    ['text' => tra('Other'), 'value' => 'other'],
                ]
            ],
            'dateformatother' => [
                'required' => false,
                'name' => tra('Other Date-Time Format'),
                'description' => tr('Use a custom format string for the dates using PHP "DateTime::format" parameters, e.g. %0', 'D j M h:i'),
                'since' => '23.0',
                'filter' => 'text',
                'default' => '',
            ],
            'adminperms' => [
                'required' => false,
                'name' => tra('Observe Admin Permissions'),
                'description' => tra("Only admins can edit or delete other users' votes and dates. N.B. This is a guide only as if a user can edit the page they can change this setting, it is intended to make the plugin easier to use for most users."),
                'since' => '9.0',
                'filter' => 'alpha',
                'default' => 'y',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'avatars' => [
                'required' => false,
                'name' => tra('Show user profile pictures'),
                'description' => tra("Show user's profile pictures next to their names."),
                'since' => '9.0',
                'filter' => 'alpha',
                'default' => 'y',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'autolock' => [
                'required' => false,
                'name' => tra('Automatic Lock'),
                'description' => tra('Lock this plugin at a certain time and date (format YYYY-MM-DD hh:mm'),
                'since' => '20.2',
                'filter' => 'datetime',
                'default' => '',
            ],
            'locked' => [
                'required' => false,
                'name' => tra('Locked'),
                'description' => tra('Prevent further votes or changes from the interface.'),
                'since' => '20.2',
                'filter' => 'alpha',
                'default' => 'n',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ],
            ],
            'id' => [
                'required' => false,
                'name' => tra('Id of the form'),
                'description' => tra('Leave blank automatically generate.'),
                'since' => '23.0',
                'filter' => 'text',
                'default' => '',
            ],
            'voteoptions' => [
                'required' => false,
                'name' => tra('Vote options'),
                'description' => tra('Comma-separated for personalized set of vote options'),
                'since' => '24.0',
                'filter' => 'text',
                'default' => default_voteoptions(),
                'separator' => ',',
                'accepted' => tr('-1=Not OK,-0.5=No but...,0=Unconfirmed,0.5=OK but...,1=OK'),
            ],
        ]
    ];
}

function wikiplugin_convene($data, $params): string
{
    global $page;
    /** @var HeaderLib $headerlib */
    $headerlib = TikiLib::lib('header');
    /** @var TikiLib $tikilib */
    $tikilib = TikiLib::lib('tiki');
    /** @var Smarty_Tiki $smarty */
    $smarty = TikiLib::lib('smarty');

    if (! isset($params['voteoptions'])) {
        $params['voteoptions'] = default_voteoptions();
    }

    if (! is_array($params['voteoptions'])) {
        $params['voteoptions'] = explode(',', $params['voteoptions']);
    }

    //in case there is any feedback from a previous ajax action since this plugin does not refresh the page upon edit
    Feedback::sendHeaders();

    //Test if vote options are numeric value
    foreach ($params['voteoptions'] as $voteoption) {
        $optionvalue = explode('=', $voteoption);
        if (! is_numeric($optionvalue[0])) {
            Feedback::error(tr('Plugin convene: one or more of your option values must be numeric values'));
        }
    }

    static $convenePluginIndex = 0;
    ++$convenePluginIndex;

    //set defaults
    $plugininfo = wikiplugin_convene_info();
    $defaults = [];
    foreach ($plugininfo['params'] as $key => $param) {
        $defaults[$key] = $param['default'];
    }
    $params = array_merge($defaults, $params);

    $params['index'] = $convenePluginIndex;
    $params['id'] = empty($params['id']) ? 'pluginConvene' . $convenePluginIndex : $params['id'];

    /** For new data structure */
    if (substr($data, 0, 1) == "[") {
        $dataArrays = json_decode($data, true);
        $dataArray = $dataArrays[0]; // Default data votes
        $dataArrayComments = $dataArrays[1]; //Data comments
    } else { /** To support the old data structure */
        $dataArray = json_decode($data, true); // Default data votes
        $dataArrayComments = null; //Data comments
    }



    if (! is_array($dataArray)) {
        //start flat static text to prepared array
        $dataString = $data . '';
        $dataArray = [];

        $lines = explode("\n", trim($data));
        sort($lines);
        foreach ($lines as $line) {
            $line = trim($line);

            if (! empty($line) && str_contains($line, ':')) {
                $parts = explode(':', $line);
                $dataArray[trim($parts[0])] = trim($parts[1]);
            }
        }

        $data = TikiFilter_PrepareInput::delimiter('_')->prepare($dataArray);
        //end flat static text to prepared array

        $data = $data['dates'] ?? [];
    } else {
        $data = $dataArray;
    }

    $tikiDate = new TikiDate();
    $gmformat = str_replace($tikiDate->search, $tikiDate->replace, $tikilib->get_short_datetime_format());

    //start votes summed together
    $votes = [];
    $dateLabels = [];
    foreach ($data as $stamp => & $date) {
        foreach ($date as $user => $vote) {
            if (empty($votes[$stamp])) {
                $votes[$stamp] = 0;
            }
            $votes[$stamp] += (int)$vote;
        }
        $dateLabels[$stamp] = [];
        if ($params['dateformat'] === "long") {
            $dateLabels[$stamp]['formatted'] = $tikilib->get_long_datetime($stamp);
        } elseif ($params['dateformat'] === 'other') {
            $format = $params['dateformatother'];
            if (strpos($format, '%') === 0) {   // assuming a strftime format starts with %
                $dateLabels[$stamp]['formatted'] = TikiLib::date_format($format, $stamp);
            } else {
                $dateLabels[$stamp]['formatted'] = TikiLib::date_format2($format, $stamp);
            }
        } else {
            $dateLabels[$stamp]['formatted'] = $tikilib->get_short_datetime($stamp);
        }
        $dateLabels[$stamp]['gmdate'] = tr('UTC date time: %0', gmdate($gmformat, $stamp));
    }
    //end votes summed together


    //start find top vote stamps
    $topVoteStamps = [];
    if (! empty($votes)) {
        $topVotes = max($votes);
        foreach ($votes as $stamp => $vote) {
            if ($vote === $topVotes) {
                $topVoteStamps[] = $stamp;
            }
        }
    }
    //end find top vote stamp


    //start reverse array for easy listing as table
    $rows = [];
    unset($date);
    foreach ($data as $stamp => $date) {
        foreach ($date as $user => $vote) {
            if (isset($rows[$user][$stamp])) {
                $rows[$user][$stamp] = [];
            }

            $rows[$user][$stamp] = $vote;
        }
    }
    //end reverse array for easy listing as table

    $autolockMessage = '';

    if ($params['autolock']) {
        if (! preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d/', $params['autolock'])) {
            $autolock = TikiLib::date_format('%Y-%m-%d %H:%M', strtotime($params['autolock']));
            if (! $autolock) {
                Feedback::error(tr('Plugin convene: autolock date format not recognised'));
            } else {
                $params['autolock'] = $autolock;
            }
        }
        $lockDate = new TikiDate();
        $lockDate->setDate($params['autolock']);

        if ($lockDate < $tikiDate) {
            $params['locked'] = 'y';
            $autolockMessage = tr('Voting ended: %0', smarty_modifier_tiki_short_datetime($params['autolock']));
        } else {
            $autolockMessage = tr('Voting ends: %0', smarty_modifier_tiki_short_datetime($params['autolock']));
        }
    }

    $smarty->assign('autolockMessage', $autolockMessage);

    // perms for this object
    $currentObject = current_object();
    $perms = Perms::get($currentObject);
    $canEdit = $perms->edit;
    if ($params['adminperms'] !== 'y') {
        $canAdmin = $canEdit;
    } elseif (isset($currentObject['type'])) {
        if ($currentObject['type'] === 'wiki page') {
            $canAdmin = $perms->admin_wiki;
        } elseif ($currentObject['type'] === 'trackeritem') {
            $canAdmin = $perms->admin_trackers;
        } else {
            $canAdmin = $perms->admin;  // global for other object types
        }
    } else {
        $canAdmin = $perms->admin;  // global for other object types
    }

    if ($params['calendarid'] > 0) {
        $calperms = Perms::get([
            'type' => 'calendar',
            'object' => $params['calendarid'],
        ]);

        $canAddEvents = $calperms->add_events;
    }

    $smarty->assign('canEdit', $canEdit);
    $smarty->assign('canAdmin', $canAdmin);
    $smarty->assign('canAddEvents', $canAddEvents);

    $smarty->assign('dates', $data);
    $smarty->assign('dateLabels', $dateLabels);
    $smarty->assign('rows', $rows);
    $smarty->assign('votes', $votes);
    $smarty->assign('topVoteStamps', $topVoteStamps);
    $smarty->assign('params', $params);
    $smarty->assign('comments', $dataArrayComments);

    if ($params['locked'] === "y" && $currentObject) {
        $vote_infos = [
            "autolock" => $params['autolock'],
            "object" => $currentObject['object'],
            "users" => array_keys($rows),
            "votes" => $votes
        ];
        send_email_result_vote($vote_infos);
    }
    $conveneData = json_encode($params);

    $headerlib->add_jsfile('lib/jquery_tiki/wikiplugin-convene.js')
        ->add_js("$(document).ready(function() {
                \$('#{$params['id']}').setupConvene($conveneData);
            });
        ");

    return $smarty->fetch('wiki-plugins/wikiplugin_convene.tpl');
}

function default_voteoptions()
{
    return '-1=Not OK,0=Unconfirmed,1=OK';
}

/**
 * This module will help to send message to all member who participate on vote
 * when lock time reached.
 *
 * @param $vote_infos
 *
 * @throws Exception
 */
function send_email_result_vote($vote_infos)
{
    $date_start = TikiLib::date_format('%Y-%m-%d %H:%M', strtotime("now"));

    if ($vote_infos["autolock"] === $date_start) {
        $users = $vote_infos["users"];
        $votes = $vote_infos["votes"];

        include_once('lib/webmail/tikimaillib.php');

        $userlib = TikiLib::lib('user');
        $smarty = TikiLib::lib('smarty');
        $tikilib = TikiLib::lib('tiki');

        $mail = new TikiMail();

        $smarty->assign('mail_site', $_SERVER["SERVER_NAME"]);

        $object_event = $vote_infos['object'];
        $event_path = str_replace(" ", "-", $object_event);

        $max_vote = 0;
        $vote_result = "";
        arsort($votes);
        $i = 0;
        foreach ($votes as $date => $vote) {
            if ($i === 0) {
                $max_vote = $vote;
            }
            if ($max_vote === $vote) {
                $date_vote = TikiLib::date_format('%Y-%m-%d %H:%M', $date);

                if ($i === 0) {
                    $vote_result = $vote . tra("votes on date of ") . $date_vote . " ";
                } else {
                    $vote_result .= tra("and") . " " . $date_vote;
                }
            }
            $i++;
        }

        foreach ($users as $user) {
            $userinfo = $userlib->get_user_email($user);

            if ($userinfo) {
                $smarty->assign('mail_user', $user);
                $smarty->assign('event_link_path', $event_path);
                $smarty->assign('vote_result', $vote_result);
                $mail->setUser($userinfo);

                $subject = sprintf($smarty->fetch('mail/convene_vote_result_subject.tpl'), $object_event, $_SERVER['SERVER_NAME']);

                $mail->setSubject($subject);
                $txt = $smarty->fetch('mail/convene_vote_result.tpl');
                $mail->setText($txt);
                $mail->send([$userinfo]);
            }
        }
    }
}
