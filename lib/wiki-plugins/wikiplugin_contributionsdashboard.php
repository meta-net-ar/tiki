<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Lib\Logs\LogsQueryLib;
use Tiki\Lib\TikiDate;

function wikiplugin_contributionsdashboard_info()
{
    return [
        'name' => tra('Contributions Dashboard'),
        'documentation' => 'PluginContributionsDashboard',
        'description' => tra('List users\' contributions to a page'),
        'prefs' => [ 'feature_trackers', 'wikiplugin_contributionsdashboard' ],
        'tags' => [ 'basic' ],
        'body' => tra('Notice'),
        'format' => 'html',
        'introduced' => 9,
        'iconname' => 'dashboard',
        'filter' => 'text',
        'params' => [
            'start' => [
                'required' => false,
                'name' => tra('Start Date'),
                'description' => tra('Default Beginning Date'),
                'since' => '9.0',
                'filter' => 'date',
                'default' => 'Today - 7 days',
            ],
            'end' => [
                'required' => false,
                'name' => tra('End Date'),
                'description' => tra('Default Ending Date'),
                'since' => '9.0',
                'filter' => 'date',
                'default' => 'Today',
            ],
            'types' => [
                'required' => true,
                'name' => tra('Dashboard Types'),
                'description' => tra('The types of charts that will be rendered, separated by commas'),
                'since' => '9.0',
                'filter' => 'text',
                'default' => 'trackeritems',
            ],
        ],
    ];
}

function wikiplugin_contributionsdashboard($data, $params)
{
    global $user;
    $headerlib = TikiLib::lib('header');
    $tikilib = TikiLib::lib('tiki');
    $smarty = TikiLib::lib("smarty");

    static $iContributionsDashboard = 0;
    ++$iContributionsDashboard;
    $i = $iContributionsDashboard;

    $smarty->assign('iContributionsDashboard', $iContributionsDashboard);

    $default = [
        "start" => time() - (365 * 24 * 60 * 60),
        "end" => time(),
        "types" => "trackeritems,toptrackeritemsusers,toptrackeritemsusersip"
    ];

    $params = array_merge($default, $params);

    extract($params, EXTR_SKIP);

    $start = (! empty($_REQUEST["startDate$i"]) ? ($_REQUEST["startDate$i"]) : $start);
    $end = (! empty($_REQUEST["endDate$i"]) ? ($_REQUEST["endDate$i"]) : $end);

    $types = explode(',', $types);

    $headerlib->add_js_module("import handleMermaid from '@mermaidPack'; handleMermaid();");

    $usersTrackerItems = [];
    foreach ($tikilib->fetchAll("SELECT itemId FROM tiki_tracker_items") as $item) {
        $usersTrackerItems[] = $item['itemId'];
    }

    $result = "";
    foreach ($types as $type) {
        if ($type == "trackeritems") {
            $data = [];
            $dates = [];

            foreach (LogsQueryLib::trackerItem()->start($start)->end($end)->countByDateFilterId($usersTrackerItems) as $date => $count) {
                $data[] = $count * 1;
                $dates[] = $date;
            }

            // Format data for Mermaid xychart
            $xAxisLabels = array_map(function ($date) {
                return '"' . $date . '"';
            }, $dates);

            $maxValue = ! empty($data) ? max($data) : 10;
            $yAxisMax = ceil($maxValue * 1.2); // 20% marge au-dessus

            $mermaidDataTrackeritems = 'xychart-beta
                title "Tracker Item Activity Grouped By Date"
                x-axis [' . implode(', ', $xAxisLabels) . ']
                y-axis "Count" 0 --> ' . $yAxisMax . '
                bar [' . implode(', ', $data) . ']';

            $result .= "<div id='mermaid-diagram-trackeritems$i' class='mb-3'>
                <textarea class='code d-none'>" . htmlspecialchars($mermaidDataTrackeritems) . "</textarea>
                <div class='mermaid w-100'></div>
            </div>";
        }

        if ($type == "toptrackeritemsusers") {
            $hits = [];
            $users = [];

            foreach (LogsQueryLib::trackerItem()->start($start)->end($end)->countUsersFilterId($usersTrackerItems) as $user => $count) {
                $hits[] = $count;
                $users[] = empty($user) ? 'Anonymous' : $user;
            }

            // Format data for Mermaid xychart
            $xAxisLabels = array_map(function ($user) {
                return '"' . addslashes($user) . '"';
            }, $users);

            $maxValue = ! empty($hits) ? max($hits) : 10;
            $yAxisMax = ceil($maxValue * 1.2); // 20% marge au-dessus

            $mermaidDataToptrackeritemsusers = 'xychart-beta
                title "Tracker Item Activity Grouped By Users"
                x-axis [' . implode(', ', $xAxisLabels) . ']
                y-axis "Count" 0 --> ' . $yAxisMax . '
                bar [' . implode(', ', $hits) . ']';

            $result .= "<div id='mermaid-diagram-users$i' class='mb-3'>
                <textarea class='code d-none'>" . htmlspecialchars($mermaidDataToptrackeritemsusers) . "</textarea>
                <div class='mermaid w-100'></div>
            </div>";
        }

        if ($type == "toptrackeritemsusersip") {
            $hits = [];
            $users = [];

            foreach (LogsQueryLib::trackerItem()->start($start)->end($end)->countUsersIPFilterId($usersTrackerItems) as $data => $count) {
                $data = json_decode($data);

                $hits[] = $count;
                $userLabel = trim($data->user);
                if (empty($userLabel)) {
                    $userLabel = 'Anonymous';
                }
                $users[] = $userLabel . ' (' . $data->ip . ')';
            }

            // Format data for Mermaid xychart
            $xAxisLabels = array_map(function ($user) {
                return '"' . addslashes($user) . '"';
            }, $users);

            $maxValue = ! empty($hits) ? max($hits) : 10;
            $yAxisMax = ceil($maxValue * 1.2); // 20% marge au-dessus

            $mermaidDataToptrackeritemsusersip = 'xychart-beta
                title "Tracker Item Activity Grouped By Users & IP Address"
                x-axis [' . implode(', ', $xAxisLabels) . ']
                y-axis "Count" 0 --> ' . $yAxisMax . '
                bar [' . implode(', ', $hits) . ']';

            $result .= "<div id='mermaid-diagram-usersip$i' class='mb-3'>
                <textarea class='code d-none'>" . htmlspecialchars($mermaidDataToptrackeritemsusersip) . "</textarea>
                <div class='mermaid w-100'></div>
            </div>";
        }
    }

    $tikidateStart = new TikiDate();
    $tikidateStart->setDate($start);
    $tikidateEnd = new TikiDate();
    $tikidateEnd->setDate($end);
    $timezone = $tikilib->get_display_timezone();

    $fields = [
        "fieldname" => "startDate{$i}",
        "endfieldname" => "endDate{$i}",
        "date" => $start,
        "enddate" => $end,
        "timezone" => $timezone,
    ];
    return "
            <style>
                .header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    font-size: 16px;
                    padding: 1px 10px;
                    margin: 0;
                }
                .headerHelper {
                    display: flex;
                    align-items: center;
                    column-gap: 3px;
                    font-size: 12px;
                    flex-basis: 40%;
                }
                .headerAction {
                    align-self: normal;
                }
                .headerHelper > :nth-child(1) {
                    flex-basis: 20%;
                }
                .headerHelper > :nth-child(2) {
                    flex-basis: 75%;
                }
            </style>
            <div class='ui-widget ui-widget-content ui-corner-all'>
                <h3 class='header ui-state-default ui-corner-tl ui-corner-tr'>
                    " . tr("Contributions Dashboard") . "
                    <form class='headerHelper'>
                        <span>" . tr("Date Range") . "</span>
                        <span>" . smarty_function_jscalendar($fields, $smarty->getEmptyInternalTemplate()) . "</span>
                        <input type='hidden' name='refresh' value='1' />
                        <input type='submit' id='updateData$i' class='headerAction' value='" . tr("Update") . "' />
                    </form>
                </h3>
                $result
            </div>";
}
