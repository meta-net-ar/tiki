<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_translated_info()
{
    return [
        'name' => tra('Translated'),
        'documentation' => 'PluginTranslated',
        'description' => tra('Create multilingual links'),
        'prefs' => [ 'feature_multilingual', 'wikiplugin_translated' ],
        'body' => tra('[url] or ((wikiname)) or ((inter:interwiki)) (use wiki syntax)'),
        'iconname' => 'language',
        'introduced' => 1,
        'params' => [
            'lang' => [
                'required' => true,
                'name' => tra('Language'),
                'description' => tra('Two letter language code of the language, example:') . ' <code>fr</code>',
                'since' => '1',
                'filter' => 'alpha',
                'default' => '',
            ],
            'flag' => [
                'required' => false,
                'name' => tra('Flag'),
                'description' => tr('Country name, example:') . ' <code>France</code>',
                'since' => '1',
                'filter' => 'alpha',
                'default' => '',
            ],
        ],
    ];
}

function wikiplugin_translated($data, $params)
{
    extract($params, EXTR_SKIP);
    $img = '';

    if (empty($lang)) {
        return WikiParser_PluginOutput::error(tr('Plugin Translated error'), tr('Incorrect parameter.'));
    }

    $h = opendir("img/flags/");
    while ($file = readdir($h)) {
        if (! str_starts_with($file, '.') and str_ends_with($file, '.png')) {
            $avflags[] = substr($file, 0, strlen($file) - 4);
        }
    }
    if (in_array($flag, $avflags)) {
        $img = "<img src='img/flags/{$escapedFlag}.png' alt='{$escapedLang}' style='width:18px; height:13px; margin:0 3px 0 0; vertical-align:baseline;' />";
    }

    if (! $img) {
        $img = "( $lang ) ";
    }

    if (isset($data)) {
        $back = $img . $data;
    } else {
        $back = "''no data''";
    }

    return $back;
}
