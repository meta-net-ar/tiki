<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_signaturepdf_list()
{
    return [
        'signaturepdf_dev_mode' => [
            'name' => tra('Signature PDF Development Mode'),
            'description' => tra('Enable development mode for SignaturePDF. This will redirect requests to a local development server.'),
            'type' => 'flag',
            'default' => 'n',
            'dependencies' => [
                'feature_signaturepdf'
            ]
        ],
        'signaturepdf_dev_port' => [
            'name' => tra('Signature PDF Development Port'),
            'description' => tra('Specify the port for the local development server when development mode is enabled.'),
            'type' => 'text',
            'default' => '5001',
            'dependencies' => [
                'feature_signaturepdf',
                'signaturepdf_dev_mode'
            ]
        ],
    ];
}
