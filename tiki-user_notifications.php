<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$section = 'mytiki';
$inputConfiguration = [
    [
        'staticKeyFilters'  => [
        'user_calendar_watch_editor'        => 'bool',              //post
        'user_article_watch_editor'         => 'bool',              //post
        'user_wiki_watch_editor'            => 'bool',              //post
        'user_blog_watch_editor'            => 'bool',              //post
        'user_tracker_watch_editor'         => 'bool',              //post
        'user_comment_watch_editor'         => 'bool',              //post
        'user_category_watch_editor'        => 'bool',              //post
        'user_plugin_approval_watch_editor' => 'bool',              //post
        ],
    ],
];
require_once('tiki-setup.php');

$auto_query_args = ['userId', 'view_user'];

$access->check_user($user);
$access->check_feature('feature_user_watches');
if ($access->checkCsrf()) {
    $watchFields = [
        'user_calendar_watch_editor',
        'user_article_watch_editor',
        'user_wiki_watch_editor',
        'user_blog_watch_editor',
        'user_tracker_watch_editor',
        'user_comment_watch_editor',
        'user_category_watch_editor',
        'user_plugin_approval_watch_editor',
    ];

    $atLeastOneSet = false;
    foreach ($watchFields as $field) {
        if (! empty($_REQUEST[$field])) {
            $atLeastOneSet = true;
        }
    }
    if (isset($_REQUEST['user_calendar_watch_editor']) && $_REQUEST['user_calendar_watch_editor'] == true) {
        $result[] = $tikilib->set_user_preference($user, 'user_calendar_watch_editor', 'y');
    } else {
        $result[] = $tikilib->set_user_preference($user, 'user_calendar_watch_editor', 'n');
    }
    if (isset($_REQUEST['user_article_watch_editor']) && $_REQUEST['user_article_watch_editor'] == true) {
        $result[] = $tikilib->set_user_preference($user, 'user_article_watch_editor', 'y');
    } else {
        $result[] = $tikilib->set_user_preference($user, 'user_article_watch_editor', 'n');
    }
    if (isset($_REQUEST['user_wiki_watch_editor']) && $_REQUEST['user_wiki_watch_editor'] == true) {
        $result[] = $tikilib->set_user_preference($user, 'user_wiki_watch_editor', 'y');
    } else {
        $result[] = $tikilib->set_user_preference($user, 'user_wiki_watch_editor', 'n');
    }
    if (isset($_REQUEST['user_blog_watch_editor']) && $_REQUEST['user_blog_watch_editor'] == true) {
        $result[] = $tikilib->set_user_preference($user, 'user_blog_watch_editor', 'y');
    } else {
        $result[] = $tikilib->set_user_preference($user, 'user_blog_watch_editor', 'n');
    }
    if (isset($_REQUEST['user_tracker_watch_editor']) && $_REQUEST['user_tracker_watch_editor'] == true) {
        $result[] = $tikilib->set_user_preference($user, 'user_tracker_watch_editor', 'y');
    } else {
        $result[] = $tikilib->set_user_preference($user, 'user_tracker_watch_editor', 'n');
    }
    if (isset($_REQUEST['user_comment_watch_editor']) && $_REQUEST['user_comment_watch_editor'] == true) {
        $result[] = $tikilib->set_user_preference($user, 'user_comment_watch_editor', 'y');
    } else {
        $result[] = $tikilib->set_user_preference($user, 'user_comment_watch_editor', 'n');
    }
    if (isset($_REQUEST['user_category_watch_editor']) && $_REQUEST['user_category_watch_editor'] == true) {
        $result[] = $tikilib->set_user_preference($user, 'user_category_watch_editor', 'y');
    } else {
        $result[] = $tikilib->set_user_preference($user, 'user_category_watch_editor', 'n');
    }
    if (
        isset($_REQUEST['user_plugin_approval_watch_editor'])
        && $_REQUEST['user_plugin_approval_watch_editor'] == true
    ) {
        $result[] = $tikilib->set_user_preference($user, 'user_plugin_approval_watch_editor', 'y');
    } else {
        $result[] = $tikilib->set_user_preference($user, 'user_plugin_approval_watch_editor', 'n');
    }
    if (! in_array(false, $result)) {
        if (! $atLeastOneSet) {
            Feedback::warning(tr('Notification preferences updated. No type of notification to watch activated.'));
        } else {
            Feedback::success(tr('Notification preferences set successfully'));
        }
    } else {
        Feedback::error(tr('Errors were encountered when setting notification preferences'));
    }
}

$access->redirect('tiki-user_watches.php');
