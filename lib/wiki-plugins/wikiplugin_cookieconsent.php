<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Lib\CookieConsent\CookieConsentLib;

function wikiplugin_cookieconsent_info()
{
    return [
        'name' => tra('Cookie Consent'),
        'documentation' => 'PluginCookieConsent',
        'description' => tra('Display content based on whether cookie consent has been granted by the user.'),
        'prefs' => ['wikiplugin_cookieconsent', 'cookie_consent_feature'],
        'body' => tr('Wiki syntax containing the content that can be hidden or shown. The body may contain %0{ELSE}%1.
            Text after the marker will be displayed if consent has not been granted.', '<code>', '</code>'),
        'filter' => 'wikicontent',
        'introduced' => 10,
        'iconname' => 'information',
        'params' => [
            'element' => [
                'required' => false,
                'name' => tra('Containing Element'),
                'description' => tr('DOM element to contain everything (div, span, etc). The default is %0,
                    set to %1 for no container.', '<code>div</code>', '<code>none</code>'),
                'since' => '10.0',
                'default' => 'div',
                'filter' => 'word',
            ],
            'element_class' => [
                'required' => false,
                'name' => tra('Element CSS Class'),
                'description' => tra('CSS class for above.'),
                'since' => '10.0',
                'default' => '',
                'filter' => 'text',
            ],
            'no_consent_class' => [
                'required' => false,
                'name' => tra('No Consent CSS Class'),
                'description' => tr('CSS class for no consent message. Default: %0', '<code>wp-cookie-consent-required</code>'),
                'since' => '11.1',
                'default' => 'wp-cookie-consent-required',
                'filter' => 'text',
            ],
            'cookie_category_needed' => [
                'required' => false,
                'name' => tra('Cookie category needed'),
                'description' => tra('Category of cookies needed to be consented to for this content to be displayed. Defaults to "Essential"'),
                'options' => [
                    ['text' => tra('Essential'), 'value' => CookieConsentLib::BUILTIN_COOKIE_CATEGORY_ESSENTIAL],
                    ['text' => tra('Functional'), 'value' => CookieConsentLib::BUILTIN_COOKIE_CATEGORY_FUNCTIONAL],
                    ['text' => tra('Analytics'), 'value' => CookieConsentLib::BUILTIN_COOKIE_CATEGORY_ANALYTICS],
                    ['text' => tra('Marketing'), 'value' => CookieConsentLib::BUILTIN_COOKIE_CATEGORY_MARKETING],
                ],
                'since' => '30.0',
                'default' => CookieConsentLib::BUILTIN_COOKIE_CATEGORY_ESSENTIAL,
                'filter' => 'text',
            ],
        ]
    ];
}

function wikiplugin_cookieconsent($body, $params)
{
    global $prefs;

    if ($prefs['cookie_consent_feature'] !== 'y') {
        return $body;
    }

    $class = $params['element_class'];

    $parts = explode('{ELSE}', $body);
    if (! CookieConsentLib::isCategoryAllowed($params['cookie_category_needed'])) {
        if (count($parts) > 1) {
            $body = $parts[1];
        } else {
            $body = '';
        }
        $class .= ($class ? ' ' : '') . $params['no_consent_class'];
    } else {
        $body = $parts[0];
    }

    $tag1 = $tag2 = '';
    if ($params['element'] !== 'none') {
        if ($class) {
            $class = " class=\"{$class}\"";
        }
        $tag1 = "<{$params['element']}$class>";
        $tag2 = "</{$params['element']}>";
    }

    return $tag1 . $body . $tag2;
}
