<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function wikiplugin_peertube_info()
{
    return [
        'name' => tra('PeerTube Video'),
        'documentation' => 'PluginPeerTube',
        'description' => tra('Embed a PeerTube video using the oEmbed protocol'),
        'prefs' => ['wikiplugin_peertube', 'feature_peertube'],
        'format' => 'html',
        'iconname' => 'video',
        'params' => [
            'url' => [
                'required' => true,
                'name' => tra('URL'),
                'description' => tra('Complete URL to the PeerTube video (e.g., https://videos.example.com/w/abc123xyz)'),
                'filter' => 'url',
                'default' => '',
            ],
            'width' => [
                'required' => false,
                'name' => tra('Width'),
                'description' => tra('Width in pixels (e.g., 560). Leave empty to use provider dimensions.'),
                'filter' => 'digits',
                'default' => '',
            ],
            'height' => [
                'required' => false,
                'name' => tra('Height'),
                'description' => tra('Height in pixels (e.g., 315). Leave empty to use provider dimensions.'),
                'filter' => 'digits',
                'default' => '',
            ],
            'privacyEnhanced' => [
                'required' => false,
                'name' => tra('Privacy-Enhanced'),
                'description' => tra('Enable privacy-enhanced mode (if applicable)'),
                'filter' => 'alpha',
                'default' => '',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'bg' => [
                'required' => false,
                'name' => tra('Background'),
                'description' => tra('Object background color. Example:') . ' <code>#ffffff</code>',
                'filter' => 'text',
                'default' => '',
                'advanced' => true,
            ],
            'border' => [
                'required' => false,
                'name' => tra('Border Color'),
                'description' => tra('Object border color. Example:') . ' <code>#ffffff</code>',
                'filter' => 'text',
                'default' => '',
                'advanced' => true,
            ],
            'borderRadius' => [
                'required' => false,
                'name' => tra('Border Radius'),
                'description' => tra('Apply rounded corners. Default: Yes'),
                'default' => 'y',
                'filter' => 'alpha',
                'options' => [
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
                'advanced' => true,
            ],
            'start' => [
                'required' => false,
                'name' => tra('Start Time'),
                'description' => tra('Start time offset in seconds'),
                'filter' => 'digits',
                'default' => 0,
            ],
            'allowFullScreen' => [
                'required' => false,
                'name' => tra('Allow Full-Screen'),
                'description' => tra('Enlarge video to full screen size'),
                'default' => 'y',
                'filter' => 'alpha',
                'options' => [
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
                'advanced' => true,
            ],
        ],
    ];
}

function wikiplugin_peertube($data, $params)
{
    if (! is_array($params)) {
        $params = [];
    }

    if (! function_exists('wikiplugin_oembed')) {
        require_once 'lib/wiki-plugins/wikiplugin_oembed.php';
    }

    if (empty($params['url'])) {
        Feedback::error(tra('PeerTube plugin error: the URL parameter is missing.'));
        return '';
    }

    $html = TikiLib::lib('parser')->invokePlugin('oembed', '', $params);

    return trim($html, '~/np');
}
