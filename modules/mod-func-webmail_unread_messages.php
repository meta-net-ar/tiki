<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function module_webmail_unread_messages_info()
{
    return [
        'name' => tra('Unread emails'),
        'description' => tra('Display unread messages per email account/tracker item'),
        'prefs' => ['feature_webmail'],
        'params' => [
            'search_refresh_interval' => [
                'name' => tra('Data refresh interval'),
                'description' => tra('For performance reasons, wait n minutes before fetching new data again'),
                'filter' => 'int',
                'default' => 2
            ],
            'sources' => [
                'name' => tra('Show '),
                'description' => tra('Limit sources where to check for unread emails'),
                'filter' => 'alpha',
                'default' => 'all',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('All'), 'value' => 'true'],
                    ['text' => tra('Trackers'), 'value' => 'trackers'],
                    ['text' => tra('Pages'), 'value' => 'pages'],
                    ['text' => tra('Main webmail'), 'value' => 'main_webmail']
                ]
            ],
        ]
    ];
}

function module_webmail_unread_messages($mod_reference, $module_params)
{
    global $tikipath;

    $info = module_webmail_unread_messages_info();
    $defaults = [];
    foreach ($info['params'] as $key => $param) {
        $defaults[$key] = $param['default'];
    }
    $module_params = array_merge($defaults, $module_params);

    $smarty = TikiLib::lib('smarty');
    $cache = TikiLib::lib('cache');
    $cacheKey = 'tiki_unread_emails';

    if ($_SESSION['webmail_search_refresh_time'] > time() && $cache->isCached($cacheKey, 'system')) {
        $webmailUnreadMessages = $cache->getSerialized($cacheKey, 'system');
    } else {
        require_once $tikipath . '/lib/cypht/integration/Tiki_Hm_Functions.php';

        $webmailUnreadMessages = [];
        $sourcePref = $module_params['sources'];

        if (in_array($sourcePref, ['all', 'main_webmail'])) {
            $webmailUnreadMessages['pages'] = Tiki_Hm_Functions::unreadWebmailMessages();
        }
        if (in_array($sourcePref, ['all', 'pages'])) {
            $pages = Tiki_Hm_Functions::unreadPagesMessages();
            if (isset($webmailUnreadMessages['pages'])) {
                $pages = array_merge($webmailUnreadMessages['pages'], $pages);
            }
            $webmailUnreadMessages['pages'] = $pages;
        }
        if (in_array($sourcePref, ['all', 'trackers'])) {
            $webmailUnreadMessages['tracker_items'] = Tiki_Hm_Functions::unreadMessageTrackerItems();
        }

        $_SESSION['webmail_search_refresh_time'] = time() + ($module_params['search_refresh_interval'] * 60);
        $cache->cacheItem($cacheKey, serialize($webmailUnreadMessages), 'system');
    }

    $smarty->assign('webmailUnreadMessages', $webmailUnreadMessages);
}
