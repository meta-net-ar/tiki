<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_html_info()
{
    return [
        'name' => tra('HTML'),
        'documentation' => 'PluginHTML',
        'description' => tra('Add HTML to a page'),
        'prefs' => ['wikiplugin_html'],
        'body' => tra('HTML code'),
        'validate' => 'all',
        'filter' => 'rawhtml_unsafe',
        'iconname' => 'file-code',
        'tags' => [ 'basic' ],
        'introduced' => 3,
        'params' => [
            'tohead' => [
                'required' => false,
                'name' => tra('Move to HTML head'),
                'description' => tra('Insert the code in the HTML head section rather than in the body.'),
                'since' => '17.0',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('No'), 'value' => 0],
                    ['text' => tra('Yes'), 'value' => 1],
                ],
                'filter' => 'digits',
                'default' => '0',
            ],
            'wiki' => [
                'required' => false,
                'name' => tra('Wiki Syntax'),
                'description' => tra('Parse wiki syntax within the HTML code.'),
                'since' => '3.0',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('No'), 'value' => 0],
                    ['text' => tra('Yes'), 'value' => 1],
                ],
                'filter' => 'digits',
                'default' => '0',
            ],
            'purify' => [
                'required' => false,
                'name' => tra('Purify HTML'),
                'description' => tra('Purify the HTML code.'),
                'since' => '30',
                'default' => 'y',
                'options' => [
                    ['text' => tra('No'), 'value' => 'n'],
                    ['text' => tra('Yes'), 'value' => 'y']
                ]
            ],
        ],
    ];
}

function wikiplugin_html($data, $params)
{
    // strip out sanitation which may have occurred when using nested plugins
    $html = str_replace('<x>', '', $data);

    // parse using is_html if wiki param set, or just decode html entities
    if ($params['wiki'] == 1) {
        $html = TikiLib::lib('parser')->parse_data($html, ['is_html' => true, 'parse_wiki' => true, 'is_plugin_html' => true]);
    } else {
        $html  = html_entity_decode($html, ENT_NOQUOTES, 'UTF-8');
    }
    // exit($html);
    if (isset($params['purify']) && $params['purify'] === 'n') {
        Feedback::warning(tr('HTML purification is off. Submitted HTML may cause XSS, bad links, or broken layout. Enable it by setting purify=\'y\' in PluginHtml.'));
    } else {
        // Purify $html unless explicitly disabled
        $html = TikiFilter::get('html')->filter($html);
    }

    if ($params['tohead'] == 1) {
        // Insert in HTML head rather than in body
        TikiLib::lib('header')->add_rawhtml($html);
    } else {
        return '~np~' . $html . '~/np~';
    }
}
