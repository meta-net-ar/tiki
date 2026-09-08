<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @return array
 */
function module_xmpp_info()
{
    return [
        'description' => tra('Hold a chat session using XMPP (uses the ConverseJS client). Placed site-wide, this becomes a floating "chat with us" bubble: visitors who are not logged in get a direct, 1-1 conversation with the site admin (see the xmpp_anonymous_mode preference), logged-in users get their normal chat session.'),
        'name' => tra('XMPP'),
        'params' => [
            'show_controlbox_by_default' => [
                'name' => tra('Show controlbox on load'),
                'description' => tra('If controlbox should be shown after page load'),
                'default' => 'n',
                'filter' => 'alpha',
            ],
        ],
        'prefs' => ['xmpp_feature'],
        'title' => tra('XMPP'),
        'type' => 'function'
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_xmpp($mod_reference, &$module_params)
{
    global $user, $prefs, $page;

    if (($prefs['xmpp_chat_button'] ?? 'y') !== 'y') {
        return;
    }

    // PluginXMPP owns the single Converse instance on dedicated chat pages.
    // Checking the stored source also works when the parsed wiki body comes
    // from cache, where an in-process static guard alone is not sufficient.
    if (! empty($page)) {
        $pageInfo = TikiLib::lib('tiki')->get_page_info($page);
        if (! empty($pageInfo['data']) && preg_match('/\{xmpp\b/i', $pageInfo['data'])) {
            return;
        }
    }

    if (empty($user) && ! isset($module_params['anonymous'])) {
        $module_params['anonymous'] = 'y';
    }

    TikiLib::lib('xmpp')->render_xmpp_client($module_params);
}
