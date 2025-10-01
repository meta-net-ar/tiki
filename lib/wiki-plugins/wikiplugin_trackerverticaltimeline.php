<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_trackerverticaltimeline_info()
{
    return [
        'name' => tra('Tracker Timeline'),
        'documentation' => 'PluginTrackerTimeline',
        'description' => tra('Show a timeline view of a tracker'),
        'prefs' => [ 'wikiplugin_trackertimeline', 'feature_trackers' ],
        'iconname' => 'history',
        'introduced' => 29,
        'filter' => 'wikicontent',
        'format' => 'html',
        'params' => [
            'tracker' => [
                'required' => true,
                'name' => tra('Tracker ID'),
                'description' => tra('Numeric value representing the tracker ID'),
                'since' => '29.0',
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'tracker',
            ],
            'title' => [
                'required' => true,
                'name' => tra('Title Field'),
                'description' => tra('Tracker Field ID containing the item title.'),
                'since' => '29.0',
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'tracker_field',
                'parent' => 'input[name="params[tracker]"]',
                'parentkey' => 'tracker_id',
            ],

            'color' => [
                'required' => true,
                'name' => tra('Title Field'),
                'description' => tra('Tracker Field ID containing the text color of the item.'),
                'since' => '29.0',
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'tracker_field',
                'parent' => 'input[name="params[tracker]"]',
                'parentkey' => 'tracker_id',
            ],

            'url' => [
                'required' => false,
                'name' => tra('Url Field'),
                'description' => tra('Tracker Field ID containing the url or link to go where when we click on the title of the item.'),
                'since' => '29.0',
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'tracker_field',
                'parent' => 'input[name="params[tracker]"]',
                'parentkey' => 'tracker_id',
            ],
            'urlTarget' => [
                'required' => false,
                'name' => tra('Url target'),
                'description' => tr(
                    "Customizes the target attribute when url are provided with a URL. Can be '_blank', '_self', '_parent' or '_top'."
                ),
                'since' => '29.0',
                'filter' => 'text',
                'default' => '_blank'
            ],
            'date' => [
                'required' => true,
                'name' => tra('Date of event'),
                'description' => tra('Tracker Field ID containing the element start date. The field must be a
                    datetime/jscalendar field.'),
                'since' => '29.0',
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'tracker_field',
                'parent' => 'input[name="params[tracker]"]',
                'parentkey' => 'tracker_id',
            ],
            'distribution' => [
                'required' => false,
                'name' => tra('Timeline distribution'),
                'description' => tr(
                    "Distribution of items on timeline axis. When distribution is a string, the value is applied
                    to the items. Can be 'left', 'right', 'left-right'(default) or 'none'."
                ),
                'since' => '29.0',
                'filter' => 'text',
                'default' => 'left-right'
            ],
            'height' => [
                'required' => false,
                'name' => tra('Height of Timeline'),
                'description' => tr(
                    "Define the height of the vertical timeline"
                ),
                'since' => '29.0',
                'filter' => 'text',
                'default' => '1000px'
            ],
            'aggregateBy' => [
                'required' => false,
                'name' => tra('Timeline distribution'),
                'description' => tr(
                    "Sets the aggregation interval for the event data, where interval can be one of 'second', 'minute', 'hour', 'day', 'week', 'month', 'quarter' or 'year'."
                ),
                'since' => '29.0',
                'filter' => 'text',
                'default' => 'left-right'
            ],
            'orientation' => [
                'required' => false,
                'name' => tra('Orientation of the timeline axis'),
                'description' => tr(
                    'By default the orientation of this timeline is vertical as the name of the plugin specified it. But if
                    you want, you can use it in horizontal orientation. The value of parameter orientation can be "vertical" or "horizontal"'
                ),
                'since' => '29.0',
                'filter' => 'text',
                'default' => 'vertical',
            ],
            'image_field' => [
                'required' => false,
                'name' => tra('Image Field'),
                'description' => tra('Tracker Field ID containing the image file.'),
                'since' => '29.0',
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'tracker_field',
                'parent' => 'input[name="params[tracker]"]',
                'parentkey' => 'tracker_id',
            ],
        ]
    ];
}

function wikiplugin_trackerverticaltimeline($data, $params)
{
    $trklib = TikiLib::lib('trk');
    $smarty = TikiLib::lib('smarty');
    $js = '';
    static $instance = 0;
    $instance++;

    if (! isset($params['tracker'])) {
        return '{BOX(class="text-bg-light")}' . tr("Missing parameter: %0", 'tracker') . '{BOX}';
    }

    if (! isset($params['date'])) {
        return '{BOX(class="text-bg-light")}' . tr("Missing parameter: %0", 'date') . '{BOX}';
    }

    $default = ['aggregateBy' => 'minute'];
    $params = array_merge($default, $params);

    $urlTarget = "";
    if (isset($params['urlTarget'])) {
        $fieldIds[ $params['urlTarget'] ] = 'urlTarget';

        $urlTarget = ".urlTarget('" . $params['urlTarget'] . "')";
    }

    $distribution = "";
    if (isset($params['distribution'])) {
        $fieldIds[ $params['distribution'] ] = 'distribution';
        $distribution = ".distribution('" . $params['distribution'] . "')";
    }

    $orientation = "";
    if (isset($params['orientation'])) {
        $fieldIds[ $params['orientation'] ] = 'orientation';
        $orientation = ".orientation('" . $params['orientation'] . "')";
    }

    $height = 'auto';
    if (isset($params['height'])) {
        $fieldIds[ $params['height'] ] = 'height';
        $height = $params['height'];
    }
    $aggregateBy = "";
    if (isset($params['aggregateBy'])) {
        $fieldIds[ $params['aggregateBy'] ] = 'aggregateBy';
        $aggregateBy = ".aggregateBy('" . $params['aggregateBy'] . "')";
    }

    $fieldIds = [
        $params['title'] => 'title',
        $params['date'] => 'date',
    ];

    if (! empty($params['image_field'])) {
        $fieldIds[ $params['image_field'] ] = 'image';
    }

    if (! empty($params['url'])) {
        $fieldIds[ $params['url'] ] = 'url';
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

        $detailStart = $detail['date'] ?? 0;
        $detailSummary = $detail['title'] ?? null;
        $detailGroup = $detail['url'] ?? null;
        $detail['encoded'] = json_encode($detail);

        // Add to data list
        if (! array_key_exists($detailGroup, $data)) {
            $data[$detailGroup] = [];
        }
        $data[ $detailGroup ][] = $detail;
    }

    $headerlib = TikiLib::lib('header');
    // prepare the data - to be included in the page for now (ajax feed to come)
    $headerlib->add_cssfile(NODE_PUBLIC_DIST_PATH . '/d3-milestones/build/d3-milestones.css');
    $headerlib->add_jsfile(NODE_PUBLIC_DIST_PATH . '/d3-milestones/build/d3-milestones.min.js');
    foreach ($data as $group => $list) {
        $counter = 0;  // ignoring group for now
        foreach ($list as $item) {
            $counter++;
            $itemDate = $item['date'] ?? null;
            $itemSummary = $item['url'] ?? null;
            $itemImage = $item['image'] ?? null;

            $event = [
                'title' => $item['title'],
                'date' => date('r', $itemDate),
                'id' => $counter,
            ];

            if (! empty($item['url'])) {
                $event['url'] = $item['url'];
            }

            $image = $itemImage;
            if (! empty($image)) {
                if (str_contains($image, ',')) {
                    // just the first one
                    $image = substr($image, 0, strpos($image, ','));
                }
                if (is_numeric($image)) {
                    // a fileId
                    $image = smarty_modifier_sefurl($image, 'thumbnail');
                }

                $event['img'] .= $event['title'] . "<br><img src='./" . $image . "'>";
            }
            $events[] = $event;
        }
    }

    $jsForTimeline = <<<EO
    const dataD = data.map((dat) => {
        return {title: dat.title, date: new Date(dat.date).toISOString(), id: dat.id, img: dat.img? dat.img : dat.title, url: dat.url}
        });
        console.log(dataD);
        milestones('#container-vertical-timeline')
        .mapping({
        'timestamp': 'date',
        'text': 'img',
        'id': 'id',
        'url': 'url'
        })
        .parseTime('%Y-%m-%dT%H:%M:%S.%LZ')
        $orientation
        $distributation
        $aggregateBy
        .optimize(true)
        $urlTarget
        .renderCallback(function() {
            let cos = $('.milestones').attr('style');
            let arr = cos.split(';')
            
            $('.milestones').attr('style', arr[0] + ';'+ arr[1]);
            const imgs = document.querySelectorAll('.milestones-label');
            for(let i = 0; i < imgs.length; i++) {
                let checkContent = imgs[i].innerHTML;
                if(checkContent.includes('img src=')) {
                    let replaceContent = checkContent.replace("&lt;br&gt;&lt;", "<br><");
                    replaceContent = replaceContent.replace("&gt;", ">");
                    replaceContent = replaceContent.replace("&amp;amp;thumbnail", "&amp;thumbnail");
                    imgs[i].innerHTML = replaceContent;
                }
            }
            
        })
        .render(dataD);
    EO;
    $js = 'const data = ' . json_encode($events) . ";";
    $headerlib->add_jq_onready($js . $jsForTimeline, 10);
    $smarty->assign('height', $height);
    return $smarty->fetch('wiki-plugins/wikiplugin_trackerverticaltimeline.tpl');
}
