<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_lang_info()
{
    return [
        'searchable_by_default' => true,
        'name' => tra('Language'),
        'documentation' => 'PluginLang',
        'description' => tra('Show alternative content based on the page language'),
        'prefs' => [ 'feature_multilingual', 'wikiplugin_lang' ],
        'body' => tra('Content to show'),
        'iconname' => 'language',
        'introduced' => 1,
        'params' => [
            'lang' => [
                'required' => false,
                'name' => tra('Language'),
                'description' => tr('List of languages for which the block is displayed. Languages use the two letter
                    language codes (ex: en, fr, es, ...). Use %0 to separate multiple languages.', '<code>+</code>'),
                'since' => '1',
            ],
            'notlang' => [
                'required' => false,
                'name' => tra('Not Language'),
                'description' => tr('List of languages for which the block is not displayed. Languages use the two
                    letter language codes (ex: en, fr, es, ...). Use %0 to separate multiple languages.', '<code>+</code>'),
                'since' => '1',
            ],
        ],
    ];
}

function wikiplugin_lang($data, $params)
{
    global $prefs;

    $reqlang = $_REQUEST['lang'] ?? $prefs['language'];
    extract($params, EXTR_SKIP);
    if (! is_null($lang)) {
        return in_array($reqlang, explode('+', $lang)) ? $data : '';
    }
    if (! is_null($notlang)) {
        return in_array($reqlang, explode('+', $notlang)) ? '' : $data;
    }
    return $data;
}
