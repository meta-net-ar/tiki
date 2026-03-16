<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_trackerfield_list($partial = false)
{
    $types = Tracker_Field_Factory::getFieldTypes();

    $prefs = [];
    foreach ($types as $type) {
        $name = array_shift($type['prefs']);
        $prefs[$name] = [
            'name' => $type['name'],
            'description' => $type['description'],
            'tags' => $type['tags'] ?? '',
            'default' => $type['default'],
            'dependencies' => $type['prefs'],
            'type' => 'flag',
            'warning' => $type['warning'] ?? false,
            'help' => isset($type['help']) ? urlencode($type['help']) : false,
        ];
    }

    $prefs['trackerfield_url_strict_validation'] = [
        'name' => tr('Strict validation for URL fields'),
        'description' => tr('Validate URL tracker field values with the stricter syntax checks introduced for wiki-link aware URL fields. Disable this to preserve legacy values such as UNC/network paths, and hide the wiki-link syntax help text.'),
        'type' => 'flag',
        'default' => 'n',
        'dependencies' => ['feature_trackers', 'trackerfield_url'],
    ];

    return $prefs;
}
