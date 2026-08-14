<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_bruteforce_list()
{
    return [
        'bruteforce_protection' => [
            'name' => tra('Brute Force Protection'),
            'description' => tra('Enable or disable brute force protection'),
            'type' => 'flag',
            'default' => 'n',
            'tags' => ['experimental'],
        ],
        'bruteforce_initial_delay' => [
            'name' => tra('Initial delay (seconds)'),
            'description' => tra('Initial delay in seconds before allowing another attempt'),
            'type' => 'text',
            'default' => 5,
            'tags' => ['experimental'],
        ],
        'bruteforce_growth_rate' => [
            'name' => tra('Growth rate (%)'),
            'description' => tra('Growth rate as a percentage for delay increment'),
            'type' => 'text',
            'default' => 100,
            'tags' => ['experimental'],
        ],
        'bruteforce_forget_time' => [
            'name' => tra('Forget attempts after (minutes)'),
            'description' => tra('Time in minutes after which to forget previous attempts'),
            'type' => 'text',
            'default' => 60,
            'tags' => ['experimental'],
        ],
    ]; // TODO: Create help page for this
}
