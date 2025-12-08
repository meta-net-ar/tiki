<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

require_once 'lib/wiki-plugins/shared/embed_helpers.php';

function wikiplugin_youtube_info()
{
    return [
        'name' => tra('YouTube'),
        'documentation' => 'PluginYouTube',
        'description' => tra('Embed a YouTube video in a page'),
        'prefs' => [ 'wikiplugin_youtube' ],
        'iconname' => 'youtube',
        'introduced' => 2,
        'tags' => [ 'basic' ],
        'params' => [
            'movie' => [
                'required' => true,
                'name' => tra('Movie'),
                'description' => tr('Complete URL to the YouTube video or last part (after %0www.youtube.com/v/%1 and
                    before the first question mark)', '<code>', '</code>'),
                'since' => '2.0',
                'filter' => 'url',
            ],
            'privacyEnhanced' => [
                'required' => false,
                'name' => tra('Privacy-Enhanced'),
                'description' => tra('Enable privacy-enhanced mode'),
                'default' => '',
                'filter' => 'alpha',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'width' => [
                'required' => false,
                'name' => tra('Width'),
                'description' => tra('Width in pixels (e.g., 560). Leave empty to use responsive behavior.'),
                'since' => '2.0',
                'filter' => 'digits',
                'default' => '',
            ],
            'height' => [
                'required' => false,
                'name' => tra('Height'),
                'description' => tra('Height in pixels (e.g., 315). Leave empty to use responsive behavior.'),
                'since' => '2.0',
                'filter' => 'digits',
                'default' => '',
            ],
            'start' => [
                'required' => false,
                'name' => tra('Start time'),
                'description' => tra('Start time offset in seconds'),
                'filter' => 'digits',
                'default' => 0,
            ],
            'quality' => [
                'required' => false,
                'name' => tra('Quality'),
                'description' => tr('Quality of the video. Default is %0high%1.', '<code>', '</code>'),
                'since' => '2.0',
                'default' => 'high',
                'filter' => 'alpha',
                'options' => [
                    ['text' => tra('High'), 'value' => 'high'],
                    ['text' => tra('Medium'), 'value' => 'medium'],
                    ['text' => tra('Low'), 'value' => 'low'],
                ],
                'advanced' => true
            ],
            'allowFullScreen' => [
                'required' => false,
                'name' => tra('Allow full-screen'),
                'description' => tra('Enlarge video to full screen size'),
                'since' => '5.0',
                'default' => 'y',
                'filter' => 'alpha',
                 'options' => [
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                 ],
                 'advanced' => true
            ],
            'related' => [
                'required' => false,
                'name' => tra('Related'),
                'description' => tra('Show related videos (shown by default)'),
                'since' => '6.1',
                'default' => 'y',
                'filter' => 'alpha',
                'options' => [
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
                'advanced' => true
            ],
            'bg' => [
                'required' => false,
                'name' => tra('Background'),
                'description' => tra('Object background color. Example:') . ' <code>#ffffff</code>, <code>rgb(255, 255, 255)</code>, <code>white</code>',
                'accepted' => tra('Any valid CSS color value, e.g., hex, rgb(a), or color names'),
                'since' => '6.1',
                'filter' => 'text',
                'default' => '',
                'advanced' => true
            ],
            'border' => [
                'required' => false,
                'name' => tra('Borders'),
                'description' => tra('Object border color. Example:') . ' <code>#ffffff</code>, <code>rgb(255, 255, 255)</code>, <code>white</code>',
                'accepted' => tra('Any valid CSS color value, e.g., hex, rgb(a), or color names'),
                'since' => '6.1',
                'filter' => 'text',
                'default' => '',
                'advanced' => true
            ],
            'borderRadius' => [
                'required' => false,
                'name' => tra('Border Radius'),
                'description' => tra('Apply rounded corners to the container. Default: ') . '<code>Yes</code>',
                'default' => 'y',
                'filter' => 'alpha',
                'options' => [
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
                'advanced' => true
            ],
        ],
    ];
}

function wikiplugin_youtube($data, $params)
{
    global $tikilib;

    $isShorts = ! empty($params['movie']) && str_contains($params['movie'], '/shorts/');

    if (empty($params['movie'])) {
        Feedback::error(tra('Plugin YouTube error: the movie parameter is empty.'));

        return '<div class="alert alert-warning">'
            . tra('Plugin YouTube error: the movie parameter is empty.')
            . '</div>';
    }

    $scheme = $tikilib->httpScheme();

    $sYoutubeId  = getYoutubeId($params['movie']);
    if (empty($sYoutubeId)) {
        Feedback::error(tra('Invalid YouTube URL provided'));

        return '<div class="alert alert-warning">'
            . tra('Plugin YouTube error: Invalid YouTube URL provided.')
            . '</div>';
    }

    $oEmbedData = getYoutubeOEmbedData('https://www.youtube.com/watch?v=' . $sYoutubeId);
    if ($oEmbedData === false) {
        $oEmbedData = ['width' => 16, 'height' => 9];
    }
    if ($isShorts) {
        $oEmbedData['width'] = 9;
        $oEmbedData['height'] = 16;
    }

    $fqdn = $params['privacyEnhanced'] === 'y' ? 'www.youtube-nocookie.com' : 'www.youtube.com';
    $src = $scheme . '://' . $fqdn . '/embed/' . $sYoutubeId;

    $queryParams = [];
    if ($params['related'] === 'n') {
        $queryParams[] = 'rel=0';
    }
    if (! empty($params['quality']) && in_array($params['quality'], ['high', 'medium', 'low'])) {
        $queryParams[] = 'vq=' . $params['quality'];
    }
    if (! empty($queryParams)) {
        $src .= '?' . implode('&', $queryParams);
    }

    $embedHtml = buildEmbedContainerAndIframe($src, $params, $oEmbedData);
    return '~np~' . $embedHtml . '~/np~';
}

function getYoutubeId($sYoutubeUrl)
{
    $aParsedUrl = parse_url($sYoutubeUrl);
    if ($aParsedUrl !== false && ! empty($aParsedUrl['host'])) {
        if (
            $aParsedUrl['host'] !== 'youtube.com'
            && $aParsedUrl['host'] !== 'www.youtube.com'
            && $aParsedUrl['host'] !== 'youtu.be'
            && $aParsedUrl['host'] !== 'www.youtu.be'
        ) {
            return false;
        }
        if ($aParsedUrl['host'] === 'youtu.be') {
            $sYoutubeId = str_replace('/', '', $aParsedUrl['path']);
            return $sYoutubeId;
        }
        if ($aParsedUrl['host'] === 'youtube.com' || $aParsedUrl['host'] === 'www.youtube.com') {
            if (! empty($aParsedUrl['path']) && preg_match('#^/shorts/([\w\-_]+)#', $aParsedUrl['path'], $matches)) {
                return $matches[1];
            }
            parse_str(parse_url($sYoutubeUrl, PHP_URL_QUERY), $aQueryString);
            return $aQueryString['v'] ?? false;
        }
    } elseif (preg_match('/^([\w\-_]+)$/', $sYoutubeUrl, $matches)) {
        $sYoutubeId = $sYoutubeUrl;
    } else {
        return false;
    }
    return $sYoutubeId;
}

function getYoutubeOEmbedData($youtubeUrl)
{
    $youtubeUrl = filter_var($youtubeUrl, FILTER_SANITIZE_URL);
    if (! filter_var($youtubeUrl, FILTER_VALIDATE_URL)) {
        return false;
    }

    $oEmbedUrl = 'https://www.youtube.com/oembed?url=' . urlencode($youtubeUrl) . '&format=json';
    $response = file_get_contents($oEmbedUrl);

    if ($response === false) {
        return false;
    }

    $oEmbedData = json_decode($response, true);
    if (isset($oEmbedData['html'], $oEmbedData['width'], $oEmbedData['height'])) {
        return $oEmbedData; // 'html', 'width', 'height', 'title', 'author_name', etc.
    }

    return false;
}
