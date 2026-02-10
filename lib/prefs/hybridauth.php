<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function prefs_hybridauth_list()
{
    return [
        'hybridauth_login_enabled' => [
            'name' => tra('Enable Hybridauth Social Login'),
            'description' => tra('Allow users to log in using OAuth2 providers (Google, Facebook, GitHub, LinkedIn, etc.) via Hybridauth. This is independent of other social network features.'),
            'type' => 'flag',
            'default' => 'n',
            'tags' => ['experimental'],
            'admin' => 'socialnetworks',
            'dependencies' => [
                'feature_socialnetworks',
            ],
        ],
    ];
}
