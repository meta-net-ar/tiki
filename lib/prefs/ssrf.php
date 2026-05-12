<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_ssrf_list()
{
    return [
        'ssrf_whitelisted_hosts' => [
            'name' => tra('SSRF whitelisted hosts'),
            'description' => tra('Comma-separated list of hostnames that are allowed to be fetched server-side even if they resolve to private or reserved IP addresses. Leave empty to disallow all private ranges.'),
            'type' => 'textarea',
            'size' => 3,
            'filter' => 'text',
            'default' => '',
            'tags' => ['advanced', 'security'],
        ], // TODO: Add help for this preference
    ];
}
