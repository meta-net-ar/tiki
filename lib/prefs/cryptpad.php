<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function prefs_cryptpad_list($partial = false)
{
    return [
        'cryptpad_feature' => [
            'name' => tra('CryptPad Office Documents'),
            'description' => tra('CryptPad enables viewing and editing Microsoft Office and OpenDocument Format files (.docx, .xlsx, .pptx, .odt, .ods, .odp)'),
            'help' => 'CryptPad',
            'type' => 'flag',
            'default' => 'n',
            'dependencies' => [
                'feature_file_galleries',
            ],
            'tags' => ['basic'],
        ],
        'cryptpad_base_url' => [
            'name' => tra('CryptPad base URL'),
            'description' => tra('Base URL of your CryptPad instance (e.g., https://cryptpad.example.com). Required for embedding the editor.'),
            'type' => 'text',
            'size' => '60',
            'filter' => 'url',
            'default' => '',
            'dependencies' => [
                'cryptpad_feature',
            ],
            'tags' => ['basic'],
        ],
    ];
}
