<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_permission_list()
{
    return [
        'permission_denied_url' => [
            'name' => tra('Send to URL'),
            'description' => tra('URL to redirect to on "permission denied"'),
            'type' => 'text',
            'size' => '50',
            'default' => '',
            // TODO: find the doc link or create one
            'tags' => ['basic'],
        ],
        'permission_denied_login_box' => [
            'name' => tra('On permission denied, display login module'),
            'description' => tra('If an anonymous visitor attempts to access a page for which permission is not granted, Tiki will automatically display the Log-in module. 
Alternatively, use the Send to URL field to display a specific page (relative to your Tiki installation) instead.'),
            'type' => 'flag',
            'default' => 'y',
            'help' => 'Login-General-Preferences',
            'tags' => ['basic'],
        ],
        'permission_scope_behavior' => [
            'name' => tra('Permission system behavior'),
            'description' => tra('Traditional strict scope based permission system locks specific object permissions to the closest defined permission set for the object bubbling up through category and parent object permissions to the global set of permissions. The blending scope based permission system allows different levels of permissions to combine on a single object. It is an additive-only permission set, so if you want to restrict specific level permissions over a more permissive global level, you should use the traditional strict scope.'),
            // TODO: add help icon, doc or dev help page to explain more and link from here
            'type' => 'list',
            'options' => [
                'strict' => tr('Strict scope'),
                'blending' => tr('Blending scope'),
            ],
            'default' => 'strict',
        ],
    ];
}
