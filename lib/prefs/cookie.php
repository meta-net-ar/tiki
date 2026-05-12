<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Lib\CookieConsent\CookieConsentLib;

function prefs_cookie_list()
{

    $consentCategories = CookieConsentLib::getCookieCategories();
    $keyToName = function (array $categoryInfo): string {
        return $categoryInfo['name'];
    };
    $cookieConsentDisableBuiltinCategoriesOptions = array_map($keyToName, $consentCategories);
    unset($cookieConsentDisableBuiltinCategoriesOptions[CookieConsentLib::BUILTIN_COOKIE_CATEGORY_ESSENTIAL]);

    $cookieConsentHelp = 'Cookie-Consent';

    return [
        'cookie_name' => [
            'name' => tra('Cookie name'),
            'description' => tra("Name of the cookie to remember the user's login"),
            'hint' => tra('Changing the cookie name forces an instant logout for all user sessions. Including yours.'),
            'type' => 'text',
            'help' => $cookieConsentHelp,
            'size' => 35,
            'perspective' => false,
            'default' => 'tikiwiki',
        ],
        'cookie_domain' => [
            'name' => tra('Domain'),
            'description' => tra('The domain that the cookie is available to.'),
            'type' => 'text',
            'help' => $cookieConsentHelp,
            'size' => 35,
            'perspective' => false,
            'default' => '',
        ],
        'cookie_path' => [
            'name' => tra('Path'),
            'description' => tra('The path on the server in which the cookie will be available on. Tiki will detect if it is installed in a subdirectory and will use that automatically.'),
            'hint' => tra('N.B. Needs to start with a / character to work properly in Safari'),
            'type' => 'text',
            'help' => $cookieConsentHelp,
            'size' => 35,
            'perspective' => false,
            'default' => $GLOBALS['tikiroot'] ?? '',
        ],
        'cookie_consent_feature' => [
            'name' => tra('Cookie Consent'),
            'description' => tra('Ask permission of the user before setting any cookies, and comply with the response.'),
            'hint' => tra('Complies with EU Privacy and Electronic Communications Regulations.'),
            'type' => 'flag',
            'help' => $cookieConsentHelp,
            'default' => 'n',
            'tags' => ['experimental'],
        ],
        'cookie_consent_expires' => [
            'name' => tra('Cookie consent expiration'),
            'description' => tra('Expiration date of the cookie to record consent (in days).'),
            'type' => 'text',
            'help' => $cookieConsentHelp,
            'filter' => 'int',
            'units' => tra('days'),
            'default' => 365,
            'tags' => ['experimental'],
            'dependencies' => [
                'cookie_consent_feature',
            ],
        ],
        'cookie_consent_description' => [
            'name' => tra('Cookie consent text'),
            'description' => tra('Description for the dialog.'),
            'hint' => tra('Wiki-parsed'),
            'type' => 'textarea',
            'help' => $cookieConsentHelp,
            'size' => 6,
            'default' => tra('This website would like to place cookies on your computer to improve the quality of your experience of the site. To find out more about the cookies, see our ((privacy notice)).'),
            'tags' => ['experimental'],
            'dependencies' => [
                'cookie_consent_feature',
            ],
        ],
        'cookie_consent_mode' => [
            'name' => tra('Cookie consent display mode'),
            'description' => tra('Appearance of consent dialog'),
            'hint' => tra(''),
            'type' => 'list',
            'help' => $cookieConsentHelp,
            'options' => [
                '' => tra('Plain'),
                'banner' => tra('Banner'),
                'dialog' => tra('Dialog'),
            ],
            'default' => '',
            'tags' => ['experimental'],
            'dependencies' => [
                'cookie_consent_feature',
            ],
        ],
        'cookie_consent_dom_id' => [
            'name' => tra('Cookie consent dialog ID'),
            'description' => tra('DOM id for the dialog container div.'),
            'type' => 'text',
            'help' => $cookieConsentHelp,
            'size' => 35,
            'default' => 'cookie_consent_div',
            'tags' => ['experimental'],
            'dependencies' => [
                'cookie_consent_feature',
            ],
        ],
        'cookie_consent_disable' => [
            'name' => tra('Cookie consent disabled'),
            'description' => tra('Do not give the option to refuse cookies but still inform the user about cookie usage.'),
            'type' => 'flag',
            'help' => $cookieConsentHelp,
            'default' => 'n',
            'tags' => ['experimental'],
            'dependencies' => [
                'cookie_consent_feature',
            ],
        ],
        'cookie_consent_disable_builtin_categories' => [
            'name' => tra('Deactivate specific cookie categories'),
            'description' => tra('Interim pref.  Allows disabling a built-in consent category.  Tiki will not show the consent at all for that category, and will act as if the user refused consent for that category.  This pref will be removed once tiki collects what categories were actually requested by calling tiki code, and will only ask for those that were requested.'),
            'type' => 'multilist',
            'options' => $cookieConsentDisableBuiltinCategoriesOptions,
            'default' => [],
            'tags' => ['experimental'],
            'dependencies' => [
                'cookie_consent_feature',
            ],
            'help' => $cookieConsentHelp,
        ], // TODO: Add in the help page for this preference
        'cookie_refresh_rememberme' => [
            'name' => tr('Refresh the remember-me cookie expiration'),
            'description' => tr('Each time a user is logged in with a cookie set in a previous session, the cookie expiration date is updated.'),
            'type' => 'flag',
            'help' => $cookieConsentHelp,
            'default' => 'y',
            'tags' => ['advanced'],
            'dependencies' => [
                'rememberme',
            ],
        ],
    ];
}
