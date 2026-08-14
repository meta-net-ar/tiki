<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_activity_list()
{
    return [
        'activity_basic_events' => [
            'name' => tr('Record basic events'),
            'description' => tr('Enable recording of basic internal Tiki events. This is primarily for entry level options. Using custom events is strongly encouraged.'),
            'help' => 'PluginActivityStream',
            'type' => 'flag',
            'default' => 'n',
        ],
        'activity_basic_tracker_update' => [
            'name' => tr('Record tracker item update'),
            'description' => tr('Enable recording of basic internal Tiki events.'),
            'dependencies' => ['activity_basic_events', 'feature_trackers'],
            'help' => 'PluginActivityStream',
            'type' => 'flag',
            'default' => 'n',
        ],
        'activity_basic_tracker_create' => [
            'name' => tr('Record tracker item creation'),
            'description' => tr('Enable recording of basic internal Tiki events.'),
            'dependencies' => ['activity_basic_events', 'feature_trackers'],
            'help' => 'PluginActivityStream',
            'type' => 'flag',
            'default' => 'n',
        ],
        'activity_basic_user_follow_add' => [
            'name' => tr('Record user following users'),
            'description' => tr('Enable recording of basic internal Tiki events.'),
            'dependencies' => ['activity_basic_events', 'feature_friends'],
            'help' => 'PluginActivityStream',
            'type' => 'flag',
            'default' => 'n',
        ],
        'activity_basic_user_follow_incoming' => [
            'name' => tr('Record user being followed by users'),
            'description' => tr('Enable recording of basic internal Tiki events.'),
            'dependencies' => ['activity_basic_events', 'feature_friends'],
            'help' => 'PluginActivityStream',
            'type' => 'flag',
            'default' => 'n',
        ],
        'activity_basic_user_friend_add' => [
            'name' => tr('Record user adding new friend'),
            'description' => tr('Enable recording of basic internal Tiki events.'),
            'dependencies' => ['activity_basic_events', 'feature_friends'],
            'help' => 'PluginActivityStream',
            'type' => 'flag',
            'default' => 'n',
        ],
        'activity_custom_events' => [
            'name' => tr('Custom activities'),
            'description' => tr('Allow the defining of custom behaviors in addition to internal events.'),
            'help' => 'PluginActivityStream',
            'type' => 'flag',
            'default' => 'n',
        ],
        'activity_notifications' => [
            'name' => tr('Enable notifications through activities'),
            'description' => tr('Allow users to create notifications using activities.'),
            'help' => 'Activity-Notifications',
            'type' => 'flag',
            'default' => 'n',
        ],
        'activity_stream_disable_indexing' => [
            'name' => tr('Disable indexing of activity stream log'),
            'description' => tr('Activity stream log in the search index is controlled by the presence of custom or basic activity events and also by enabling monitoring of events by users. Larger activity streams can cause problems with certain search engines. If you have such problems, you can disable indexing of activity stream here.'),
            'help' => 'PluginActivityStream',
            'type' => 'flag',
            'default' => 'n',
            'dependencies' => [
                'feature_search',
            ],
        ],
    ];
}
