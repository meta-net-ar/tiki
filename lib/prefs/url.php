<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_url_list()
{
    return [
        'url_after_validation' => [
            'name' => tra('URL the user is redirected to after account validation'),
            'description' => tra('The default page a Registered user sees after account validation is "tiki-information.php?msg=Account validated successfully".'),
            'hint' => tra('Default') . ': tiki-information.php?msg=' . tra('Account validated successfully.'),
            'type' => 'text',
            'dependencies' => [
                'allowRegister',
            ],
            'default' => '',
            'help' => 'Login-General-Preferences',
        ],
        'url_anonymous_page_not_found' => [
            'name' => tra('The URL that the anonymous user is redirected to when a page is not found'),
            'description' => tra('Redirect anonymous visitors to this URL when a wiki page does not exist and no alias or similar-page redirect applies.'),
            'type' => 'text',
            'default' => '',
            'help' => 'Navigation',
        ],
        'url_only_ascii' => [
            'name' => tra('Use Only ASCII in SEFURLs'),
            'description' => tra('Do not use accented characters in short (search engine friendly) URLs.'),
            'help' => 'Apache-Clean-URLs',
            'type' => 'flag',
            'perspective' => false,
            'default' => 'n',
        ],
        'url_fragment_format' => [
            'name' => tra('URL Fragment format'),
            'description' => tra('Provides ability to change anchor format'),
            'detail' => tra('Set to "Complete" to change the encoding and allow anchors to contain other characters in addition to ASCII letters and digits.'),
            'type' => 'list',
                'options' => [
                'strict' => tra('Strict'),
                'complete' => tra('Complete'),
            ],
            'default' => 'strict',
            // 'help' => 'Apache-Clean-URLs',
        ],
        'url_fragment_guesser' => [
            'name' => tra('URL Fragment Guesser'),
            'description' => tra('Scroll to the closest anchor when the one indicated in the URL is missing in a page.'),
            'type' => 'flag',
            'default' => 'n',
        ],// TODO: Update documentation page for URL fragment format
    ];
}
