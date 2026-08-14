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
    return $prefs;
}
