<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_access_list()
{
    return [
        'access_control_allow_origin' => [
            'name' => 'Access-Control-Allow-Origin',
            'description' => tra('Domains allowed to make "CORS" (Cross-Origin Resource Sharing or Cross-Domain Ajax) requests from this server.'),
            'type' => 'textarea',
            'hint' => tra('One URI per line, for example, "http://www.example.org" or "*" for any site'),
            'default' => '',
            'help' => 'https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Access-Control-Allow-Origin',
        ],
    ];
}
