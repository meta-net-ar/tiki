<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_altcha_list()
{
    return [
        'altcha_hmac_key' => [
            'name' => tra('Altcha Hmac Key'),
            'type' => 'text',
            'description' => tra('Your Altcha developer Secret Key'),
            'size' => 60,
            'default' => '',
            'keywords' => 'captcha altcha',
        ]
    ];
}
