<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_trackertimeline_info()
{
    return [
        'name' => tra('Tracker Timeline'),
        'documentation' => 'PluginTrackerTimeline',
        'description' => tra('Show a timeline view of a tracker'),
        'prefs' => [ 'wikiplugin_trackertimeline', 'feature_trackers' ],
        'iconname' => 'history',
        'introduced' => 3,
        'filter' => 'wikicontent',
        'format' => 'html',
        'params' => [
            'tracker' => [
                'required' => true,
                'name' => tra('Tracker ID'),
                'description' => tra('Numeric value representing the tracker ID'),
                'since' => '3.0',
                'filter' => 'digits',
                'profile_reference' => 'tracker',
            ],
            'title' => [
                'required' => true,
                'name' => tra('Title Field'),
                'description' => tra('Tracker Field ID containing the item title.'),
                'since' => '3.0',
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'tracker_field',
                'parent' => 'input[name="params[tracker]"]',
                'parentkey' => 'tracker_id',
            ],
            'color' => [
                'required' => false,
                'name' => tra('Color Field'),
                'description' => tra('Tracker Field ID containing the item color(that field must contains valid css color like : red, yellow,#FAEBD7,BlanchedAlmond , ... .'),
                'since' => '29.0',
                'filter' => 'digits',
                'profile_reference' => 'tracker_field',
                'parent' => 'input[name="params[tracker]"]',
                'parentkey' => 'tracker_id',
            ],
            'type' => [
                'required' => false,
                'name' => tra('Type Field'),
                'description' => tra("Tracker Field ID containing the type of item. The type of the item can be 'box' (default), 'point', 'range', or 'background'. Types 'box' and 'point' need a start date, the types 'range' and 'background' needs both a start and end date."),
                'since' => '29.0',
                'filter' => 'digits',
                'profile_reference' => 'tracker_field',
                'parent' => 'input[name="params[tracker]"]',
                'parentkey' => 'tracker_id',
            ],
            'summary' => [
                'required' => true,
                'name' => tra('Summary Field'),
                'description' => tra('Tracker Field ID containing the summary of the item. The summary will be displayed
                    on the timeline when the item is focused.'),
                'since' => '3.0',
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'tracker_field',
                'parent' => 'input[name="params[tracker]"]',
                'parentkey' => 'tracker_id',
            ],
            'start' => [
                'required' => true,
                'name' => tra('Start Date'),
                'description' => tra('Tracker Field ID containing the element start date. The field must be a
                    datetime/jscalendar field.'),
                'since' => '3.0',
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'tracker_field',
                'parent' => 'input[name="params[tracker]"]',
                'parentkey' => 'tracker_id',
            ],
            'end' => [
                'required' => false,
                'name' => tra('End Date'),
                'description' => tra('Tracker Field ID containing the element end date. The field must be a
                    datetime/jscalendar field.'),
                'since' => '3.0',
                'filter' => 'digits',
                'profile_reference' => 'tracker_field',
                'parent' => 'input[name="params[tracker]"]',
                'parentkey' => 'tracker_id',
            ],
            'group' => [
                'required' => false,
                'name' => tra('Element Group'),
                'description' => tra('Tracker Field ID containing the element\'s group. Elements of a same group are
                    displayed on the same row.'),
                'since' => '3.0',
                'filter' => 'digits',
                'profile_reference' => 'tracker_field',
                'parent' => 'input[name="params[tracker]"]',
                'parentkey' => 'tracker_id',
            ],
            'lower' => [
                'required' => false,
                'name' => tra('Lower Bound'),
                'description' => tr('The initial start date for the axis of the timeline.
                    If not provided, the latest date present in the items set is taken as end date.
                    Date must be provided in %0YYYY-MM-DD HH:mm:ss%1 format.', '<code>', '</code>'),
                'since' => '3.0',
                'filter' => 'datetime',
                'accepted' => 'Date in YYYY-MM-DD HH:mm:ss format',
            ],
            'upper' => [
                'required' => false,
                'name' => tra('Upper Bound'),
                'description' => tr('The initial end date for the axis of the timeline.
                    If not provided, the latest date present in the items set is taken as end date.
                    Date must be provided in %0YYYY-MM-DD HH:mm:ss%1 format.', '<code>', '</code>'),
                'since' => '3.0',
                'filter' => 'datetime',
                'accepted' => 'Date in YYYY-MM-DD HH:mm:ss format',
            ],
            'max' => [
                'required' => false,
                'name' => tra('Max Bound'),
                'description' => tr('TSet a maximum Date for the visible range.
                    It will not be possible to move beyond this maximum.Date must be provided
                    in %0YYYY-MM-DD HH:mm:ss%1 format.', '<code>', '</code>'),
                'since' => '29.0',
                'filter' => 'datetime',
                'accepted' => 'Date in YYYY-MM-DD HH:mm:ss format',
            ],
            'min' => [
                'required' => false,
                'name' => tra('Min Bound'),
                'description' => tr('TSet a minimum Date for the visible range.
                    It will not be possible to move beyond this minimum.Date must be provided
                    in %0YYYY-MM-DD HH:mm:ss%1 format.', '<code>', '</code>'),
                'since' => '29.0',
                'filter' => 'datetime',
                'accepted' => 'Date in YYYY-MM-DD HH:mm:ss format',
            ],
            'scale' => [
                'required' => false,
                'name' => tra('Primary Scale Unit'),
                'description' => tra("Set a fixed scale for the time axis of the Timeline. Choose from 'millisecond',
                    'second', 'minute', 'hour', 'weekday', 'week', 'day', 'month', 'year'"),
                'since' => '29.0',
                'filter' => 'alpha',
            ],
            'step' => [
                'required' => false,
                'name' => tra('Secondary Scale Unit'),
                'description' => tra("Set a fixed step size for the time axis. Only applicable when used together with scale parameter. Choose for example 1, 2, 5, or 10"),
                'since' => '29.0',
                'filter' => 'number',
            ],
            'height' => [
                'required' => false,
                'name' => tra('Timeline height'),
                'description' => tr(
                    'Height of the timeline band as a CSS unit (for example 250px, 75%,...)',
                    '<code>250p</code>'
                ),
                'since' => '9.0',
                'filter' => 'text',
            ],
            'orientation' => [
                'required' => false,
                'name' => tra('Timeline orientation'),
                'description' => tr(
                    "Orientation of the timeline axis and items. When orientation is a string, the value is applied
                    to both items and axis. Can be 'top', 'bottom' (default), 'both' or 'none'."
                ),
                'since' => '29.0',
                'filter' => 'text',
            ],
            'band2_height' => [
                'required' => false,
                'name' => tra('Lower band height'),
                'description' => tr(
                    'Height of the lower timeline band as a percentage (default: %0 -  - * SIMILE only)',
                    '<code>250p</code>'
                ),
                'since' => '9.0',
                'filter' => 'int',
                'default' => '30',
            ],
            'link_group' => [
                'required' => false,
                'name' => tra('Link Group Name'),
                'description' => tra('Convert the group name to a link'),
                'since' => '3.0',
                'filter' => 'alpha',
                'default' => '',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'link_page' => [
                'required' => false,
                'name' => tra('Page Link Field'),
                'description' => tra('Tracker Field ID containing the page name for item details.'),
                'since' => '3.0',
                'filter' => 'digits',
                'profile_reference' => 'tracker_field',
                'parent' => 'input[name="params[tracker]"]',
                'parentkey' => 'tracker_id',
            ],
            'image_field' => [
                'required' => false,
                'name' => tra('Image Field'),
                'description' => tra('Tracker Field ID containing the image file.'),
                'since' => '7.0',
                'filter' => 'digits',
                'profile_reference' => 'tracker_field',
                'parent' => 'input[name="params[tracker]"]',
                'parentkey' => 'tracker_id',
            ],
        ]
    ];
}

function wikiplugin_trackertimeline($data, $params)
{
    $trklib = TikiLib::lib('trk');
    $smarty = TikiLib::lib('smarty');
    $js = '';
    static $instance = 0;
    $instance++;

    $max = 0;
    $min = 0;
    $step = ! empty($params['step']) ? ($params['step']) : '1';
    $start = ! is_null($params['lower']) ? strtotime($params['lower']) : null;
    $end = ! is_null($params['upper']) ? strtotime($params['upper']) : null;

    if ($start && $end) {
        $size = $end - $start;
        if ($size <= 0) {
            return '{BOX(class="text-bg-light")}' . tr("Start date after end date.") . '{BOX}';
        }
    }

    if (! is_null($params['max'])) {
        $fieldIds[ $params['max'] ] = 'max';
        $max = strtotime($params['max']);
    }

    if (! is_null($params['min'])) {
        $fieldIds[ $params['min'] ] = 'min';
        $min = strtotime($params['min']);
    }

    if (! is_null($params['orientation'])) {
        $fieldIds[ $params['orientation'] ] = 'orientation';
    }

    if (! is_null($params['scale'])) {
        $fieldIds[ $params['scale'] ] = 'scale';
    }

    if (! is_null($params['step'])) {
        $fieldIds[ $params['step'] ] = 'step';
    }

    if (! is_null($params['type'])) {
        $fieldIds[ $params['type'] ] = 'type';
    }

    $fieldIds = [
        $params['title'] => 'title',
        $params['summary'] => 'summary',
        $params['start'] => 'start',
    ];

    if (! is_null($params['end'])) {
        $fieldIds[ $params['end'] ] = 'end';
    }

    if (! is_null($params['link_page'])) {
        $fieldIds[ $params['link_page'] ] = 'link_page';
    }

    if (! is_null($params['image_field'])) {
        $fieldIds[ $params['image_field'] ] = 'image';
    }

    if (! is_null($params['color'])) {
        $fieldIds[ $params['color'] ] = 'color';
    }

    if (! is_null($params['group'])) {
        $fieldIds[ $params['group'] ] = 'group';
    }

    $fields = [];
    foreach ($fieldIds as $id => $label) {
        $fields[$id] = $trklib->get_tracker_field($id);
    }

    $items = $trklib->list_items($params['tracker'], 0, -1, '', $fields);

    $data = [];
    foreach ($items['data'] as $item) {
        // Collect data
        $detail = [ 'item' => $item['itemId'] ];
        foreach ($item['field_values'] as $field) {
            $detail[ $fieldIds[$field['fieldId']] ] = $field['value'];
        }

        $detailGroup = $detail['group'] ?? null;
        $detail['encoded'] = json_encode($detail);
        // Add to data list
        if (! array_key_exists($detailGroup, $data)) {
            $data[$detailGroup] = [];
        }
        $data[ $detailGroup ][] = $detail;
    }

    $headerlib = TikiLib::lib('header');
    // prepare the data - to be included in the page for now (ajax feed to come)
    $headerlib->add_cssfile(NODE_PUBLIC_DIST_PATH . '/vis-timeline/dist/vis-timeline-graph2d.min.css');
    $events = [];
    $css = '';
    $groups = [];
    foreach ($data as $list) {
        foreach ($list as $item) {
            $itemStart = $item['start'] ?? null;
            $itemSummary = $item['summary'] ?? null;
            $itemImage = $item['image'] ?? null;

            $event = [
                'content' => $item['title'],
                'start' => date('r', $itemStart),
                'title' => $itemSummary,
            ];

            if (! empty($item['end'])) {
                $event['end'] = date('r', $item['end']);
                $event['isDuration'] = true;
            }

            if (! empty($item['link_page'])) {
                $event['link_page'] = $item['link_page'];
                $js .= "console.log('" . $item['link_page'] . "');";
                $event['content'] .= "<br><a href='./tiki-index.php?page=" . urlencode($item['link_page']) . "#View page'>click here</a>";
            }

            if (! empty($item['type'])) {
                $event['type'] = $item['type'];
            }

            if (! empty($item['group'])) {
                $groups[] = $item['group'];
                $event['group'] = $item['group'];
            }

            if (! empty($item['color'])) {
                $css .= ".vis-item." . $item['color'] . " {
                    color: black;
                    background-color: " . $item['color'] . ";
                    }";
                $event['className'] = $item['color'];
            }
            $image = $itemImage;
            if (! empty($image)) {
                if (str_contains($image, ',')) {
                    // just the first one
                    $image = substr($image, 0, strpos($image, ','));
                }
                if (is_numeric($image)) {
                    // a fileId
                    $image = \SmartyTiki\Modifier\Sefurl::apply($image, 'thumbnail');
                }
                $js .= "console.log('" . $image . "');";
                $event['content'] .= "<br><img src='./" . $image . "'>";
                $event['image'] = $image;
            }
            $events[] = $event;
        }
    }
    $js .= 'var data = ' . json_encode($events) . "; const options = {}; console.log(data);";
    $js .= ! is_null($start) ? "let start = new Date(" . $start * 1000 . "); options.start = start;" : "";
    $js .= ! is_null($end) ? "let end = new Date(" . $end * 1000 . "); options.end = end;" : "";
    $js .= ! is_null($params['height']) ? "let height = '" . $params['height'] . "';options.height = height;" : "";
    $js .= ! empty($max) ? "let max = new Date(" . $max * 1000 . "); options.max = max;" : "";
    $js .= ! empty($min) ? "let min = new Date(" . $min * 1000 . "); options.min = min;" : "";
    $js .= ! is_null($params['scale']) ? "let scale = '" . $params['scale'] . "'; options.timeAxis = {scale: scale, step: parseInt(" . $step . ")};" : "";
    $js .= ! is_null($params['orientation']) ? "let orientation = '" . $params['orientation'] . "'; options.orientation = orientation;" : "";
    $codeHandleGroupParameter = "const removeDuplicationInGroup = new Set(groups);
        let arrayOfGroupWithoutDuplication = [ ...removeDuplicationInGroup ];
        let groupsOption = [];\n
        for(let i = 0; i < arrayOfGroupWithoutDuplication.length; i++){
            groupsOption.push({id: arrayOfGroupWithoutDuplication[i], content: arrayOfGroupWithoutDuplication[i]})
        };\n;
    ";
    $js .= ! is_null($params['group']) ? "var groups = " . json_encode($groups) . ";console.log('franck'); console.log('$groups');\n" . $codeHandleGroupParameter : "";
    $js .= "const container = document.getElementById('container-timeline');\n";
    $js .= "const items = new DataSet(data);";
    $js .= ! is_null($params['group']) ? "const timeline = new Timeline(container, items, groupsOption, options);" : "const timeline = new Timeline(container, items, options);";
    $headerlib->add_js_module('import { Timeline, DataSet } from "timeline";' . $js);
    $smarty->assign('css', $css);
    return $smarty->fetch('wiki-plugins/wikiplugin_trackertimeline.tpl');
}
