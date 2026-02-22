<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_security_list($partial = false)
{
    return [
        'security_warn_htaccess_mismatch' => [
            'name' => tra('Warn when .htaccess diverges'),
            'description' => tra('Alert admins if the active .htaccess differs from the bundled _htaccess.'),
            'type' => 'flag',
            'default' => 'y',
            'tags' => ['advanced', 'htaccess', 'security'],
        ],
    ];
}
