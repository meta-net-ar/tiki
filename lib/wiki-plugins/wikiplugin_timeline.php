<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_timeline_info()
{
    return [
        'name' => tra('Timeline'),
        'format' => 'html',
        'documentation' => 'PluginTimeline',
        'description' => tra('Display a timeline'),
        'prefs' => [ 'wikiplugin_timeline' ],
        'filter' => 'wikicontent',
        'format' => 'html',
        'iconname' => 'history',
        'introduced' => 8,
        'tags' => [ 'experimental' ],
        'params' => [
            'lower' => [
                'required' => false,
                'name' => tra('Lower Bound'),
                'description' => tr('The initial start date for the axis of the timeline.
                    If not provided, the latest date present in the items set is taken as end date.
                    Date must be provided in %0YYYY-MM-DD HH:mm:ss%1 format.', '<code>', '</code>'),
                'since' => '3.0',
                'filter' => 'datetime',
                'default' => '',
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
                'default' => '',
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
                'default' => '',
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
                'default' => '',
                'accepted' => 'Date in YYYY-MM-DD HH:mm:ss format',
            ],
            'scale' => [
                'required' => false,
                'name' => tra('Primary Scale Unit'),
                'description' => tra("Set a fixed scale for the time axis of the Timeline. Choose from 'millisecond',
                    'second', 'minute', 'hour', 'weekday', 'week', 'day', 'month', 'year'"),
                'since' => '29.0',
                'filter' => 'alpha',
                'default' => 'month',
            ],
            'step' => [
                'required' => false,
                'name' => tra('Secondary Scale Unit'),
                'description' => tra("Set a fixed step size for the time axis. Only applicable when used together with scale parameter. Choose for example 1, 2, 5, or 10"),
                'since' => '29.0',
                'filter' => 'number',
                'default' => '1',
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
                'default' => ''
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
                'default' => 'bottom'
            ],
        ],
    ];
}

function wikiplugin_timeline($data, $params)
{
    $smarty = TikiLib::lib('smarty');
    $default = ['scale' => 'hour', 'width' => '100%', 'height' => '400px'];
    $params = array_merge($default, $params);
    $start = isset($params['lower']) ? strtotime($params['lower']) : null;
    $end = isset($params['upper']) ? strtotime($params['upper']) : null;
    $max = isset($params['max']) ? strtotime($params['max']) : null;
    $min = isset($params['min']) ? strtotime($params['min']) : null;
    $step = ! empty($params['step']) ? ($params['step']) : '1';

    $headerlib = TikiLib::lib('header');
    $headerlib->add_cssfile(NODE_PUBLIC_DIST_PATH . '/vis-timeline/dist/vis-timeline-graph2d.min.css');

    $js = "var data = []; const options = {};";
    $js .= ! empty($start) ? "let start = new Date(" . $start * 1000 . "); options.start = start;" : "";
    $js .= ! empty($end) ? "let end = new Date(" . $end * 1000 . "); options.end = end;" : "";
    $js .= ! empty($params['height']) ? "let height = '" . $params['height'] . "';options.height = height;" : "";
    $js .= ! empty($max) ? "let max = new Date(" . $max * 1000 . "); options.max = max;" : "";
    $js .= ! empty($min) ? "let min = new Date(" . $min * 1000 . "); options.min = min;" : "";
    $js .= ! empty($params['scale']) ? "let scale = '" . $params['scale'] . "'; options.timeAxis = {scale: scale, step: parseInt(" . $step . ")};" : "";
    $js .= ! empty($params['orientation']) ? "let orientation = '" . $params['orientation'] . "'; options.orientation = orientation;" : "";
    $js .= "const container = document.getElementById('timeline-container');\n";
    $js .= "const items = new DataSet(data);";
    $js .= "const timeline = new Timeline(container, items, options);";

    $headerlib->add_js_module('import { Timeline, DataSet } from "timeline";' . $js);

    return $smarty->fetch('wiki-plugins/wikiplugin_timeline.tpl');
}
