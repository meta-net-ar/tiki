<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_captcha_list()
{
    $spamProtectionHelp = 'Spam-protection';

    return  [
        'captcha_type' => [
            'name' => tra('CAPTCHA type'),
            'description' => tra('Choose which CAPTCHA implementation is active.'),
            'help' => $spamProtectionHelp,
            'type' => 'list',
            'options' => [
                'default' => tra('Classic CAPTCHA'),
                'questions' => tra('Custom Questions CAPTCHA'),
                'recaptcha' => tra('Google reCAPTCHA'),
                'altcha' => tra('Altcha CAPTCHA'),
            ],
            'dependencies' => [
                'feature_antibot',
            ],
            'default' => 'default',
        ],
        'captcha_wordLen' => [
            'name' => tra('CAPTCHA image word length'),
            'description' => tra('Number of characters the CAPTCHA will display.'),
            'type' => 'list',
            'options' => [
                2 => 2,
                4 => 4,
                6 => 6,
                8 => 8,
                10 => 10,
            ],
            'default' => 6,
            'units' => tra('characters'),
            'help' => $spamProtectionHelp,
        ],
        'captcha_width' => [
            'name' => tra('CAPTCHA image width'),
            'description' => tra('Width of the CAPTCHA image in pixels.'),
            'type' => 'text',
            'units' => tra('pixels'),
            'default' => 180,
            'help' => $spamProtectionHelp,
        ],
        'captcha_noise' => [
            'name' => tra('CAPTCHA image noise'),
            'description' => tra('Level of noise of the CAPTCHA image.'),
            'hint' => tra('Choose a smaller number for less noise and easier reading.'),
            'type' => 'text',
            'default' => 100,
            'help' => $spamProtectionHelp,
        ],
        'captcha_questions' => [
            'name' => tra('CAPTCHA questions and answers'),
            'description' => tra('Add some simple questions that only humans should be able to answer, in the format: "Question?: Answer" with one per line'),
            'hint' => tra('One question per line with a colon separating the question and answer'),
            'type' => 'textarea',
            'size' => 6,
            'dependencies' => [
                'feature_antibot',
            ],
            'default' => '',
            'help' => $spamProtectionHelp,
        ],
    ];
}
