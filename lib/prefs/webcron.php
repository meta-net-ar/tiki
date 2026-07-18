<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_webcron_list()
{
    $cronHelp = 'Cron';

    return [
        'webcron_enabled' => [
            'name' => tra('Enabled'),
            'description' => tra('Cron jobs can be triggered from a URL or with JavaScript. A token is required to run the cron job manually.'),
            'help' => 'Cron#Web_Cron',
            'type' => 'flag',
            'default' => 'n',
        ],
        'webcron_type' => [
            'name' => tra('How to trigger Web Cron'),
            'type' => 'list',
            'description' => tra('How scheduled tasks are triggered: by an external service calling the Web Cron URL, by JavaScript on page loads, or both.'),
            'options' => [
                'url' => tra('Calling the Web Cron URL'),
                'js' => tra('Adding JavaScript that calls Web Cron'),
                'both' => tra('URL and JavaScript'),
            ],
            'default' => 'both',
            'help' => $cronHelp,
        ],
        'webcron_run_interval' => [
            'name' => tra('Run interval'),
            'description' => tra('The amount of time of each run'),
            'type' => 'text',
            'size' => 5,
            'filter' => 'digits',
            'units' => tra('seconds'),
            'default' => 60,
            'help' => $cronHelp,
        ],
        'webcron_token' => [
            'name' => tra('Token'),
            'description' => tra('The token to use when running the cron manually'),
            'type' => 'text',
            'default' => md5(phpseclib3\Crypt\Random::string(100)),
            'help' => $cronHelp,
        ],
    ]; // TODO: update cron help page
}
