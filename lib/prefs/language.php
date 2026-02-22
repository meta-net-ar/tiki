<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_language_list($partial = false)
{
    $map = [];
    $adminMap = [];

    if (! $partial) {
        $langLib = TikiLib::lib('language');
        $languages = $langLib->list_languages(false, null, true);
        foreach ($languages as $lang) {
            $map[ $lang['value'] ] = $lang['name'];
        }
    }
    $adminMap[''] = tr('Default language');
    $adminMap = array_merge($adminMap, $map);

    return [
        'language' => [
            'name' => tra('Default language'),
            'description' => tra('The site language is used when no other language is specified by the user.'),
            'filter' => 'lang',
            'help' => 'I18n',
            'type' => 'list',
            'options' => $map,
            'default' => 'en',
            'tags' => ['basic'],
        ],
        'language_admin' => [
            'name' => tr('Default admin language'),
            'description' => tr('The site language is used in admin section when no other language is specified by the user.'),
            'filter' => 'lang',
            'help' => 'I18n',
            'type' => 'list',
            'options' => $adminMap,
            'default' => '',
            'tags' => ['basic'],
        ],
        'language_inclusion_threshold' => [
            'name' => tra('Language inclusion threshold'),
            'description' => tra('When the number of languages is restricted on the site, and is below this number, all languages will be added to the preferred language list, even if unspecified by the user. However, priority will be given to the specified languages.'),
            'help' => 'Internationalization',
            'type' => 'text',
            'filter' => 'digits',
            'units' => tra('languages'),
            'size' => 2,
            'dependencies' => ['restrict_language',],
            'default' => 3,
        ],

        // Language checking preferences (LanguageTool)
        'language_tool_url' => [
            'name' => tra('LanguageTool Server URL'),
            'description' => tra('URL of your LanguageTool HTTP server. LanguageTool is an open-source grammar and spell checker that can be run as a local HTTP server. For local installations, use http://localhost:8081 (default). For remote servers, enter the full URL. Documentation: https://dev.languagetool.org/http-server'),
            'type' => 'text',
            'size' => 50,
            'default' => Services_LanguageCheck_Controller::DEFAULT_LANGUAGE_TOOL_URL,
            'dependencies' => ['feature_language_check'],
            'tags' => ['basic'],
            'admin' => 'wysiwyg',
        ],
        'language_tool_username' => [
            'name' => tra('LanguageTool Username'),
            'description' => tra('Username for LanguageTool premium features (optional)'),
            'type' => 'text',
            'size' => 30,
            'default' => '',
            'dependencies' => ['feature_language_check'],
            'tags' => ['basic'],
            'admin' => 'wysiwyg',
        ],
        'language_check_auto_detect' => [
            'name' => tra('Auto-detect Language'),
            'description' => tra('When enabled, LanguageTool automatically detects the language of the text being checked. When disabled, the language is determined from the content language (if available), user language preference, or site default language, in that order.'),
            'type' => 'flag',
            'default' => 'n',
            'dependencies' => ['feature_language_check'],
            'tags' => ['basic'],
            'admin' => 'wysiwyg',
        ],
        'language_check_debounce_ms' => [
            'name' => tra('Check Debounce (ms)'),
            'description' => tra('Delay in milliseconds before checking text after user stops typing'),
            'type' => 'text',
            'size' => 10,
            'default' => '1000',
            'dependencies' => ['feature_language_check'],
            'tags' => ['advanced'],
            'admin' => 'wysiwyg',
        ],
    ];
}
