<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_objectlink_info()
{
    return [
        'name' => tra('Object Link'),
        'description' => tra('Display a link to an object'),
        'prefs' => ['wikiplugin_objectlink'],
        'iconname' => 'link',
        'introduced' => 10,
        'tags' => [ 'basic' ],
        'format' => 'html',
        'inline' => true,
        'params' => [
            'type' => [
                'required' => true,
                'name' => tr('Type'),
                'description' => tr('The object type'),
                'since' => '10.0',
                'accepted' => 'wiki, user, external, relation_source, relation_target, freetag, trackeritem',
                'filter' => 'text',
                'type' => 'text',
            ],
            'id' => [
                'required' => true,
                'name' => tra('Object ID'),
                'description' => tra('The item to display'),
                'since' => '10.0',
                'filter' => 'text',
                'profile_reference' => 'type_in_param',
            ],
        ],
    ];
}

function wikiplugin_objectlink($data, $params)
{
    if (empty($params['type'])) {
        Feedback::error(tr('The %0 parameter is missing', 'Type'));
        return;
    }
    if (empty($params['id'])) {
        Feedback::error(tr('The %0 parameter is missing', 'Object ID'));
        return;
    }
    $smarty = TikiLib::lib('smarty');

    return smarty_function_object_link(
        [
            'type' => $params['type'],
            'id' => $params['id'],
        ],
        $smarty->getEmptyInternalTemplate()
    );
}
