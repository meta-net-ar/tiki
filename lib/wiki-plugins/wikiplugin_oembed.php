<?php

function wikiplugin_oembed_info()
{
    return [
      'name' => 'oEmbed',
      'documentation' => 'PluginOEmbed',
      'description' => tra('Embed a video or media using oEmbed protocol'),
      'prefs' => [ 'wikiplugin_oembed' ],
      'iconname' => 'share-square',
      'introduced' => 28,
      'tags' => [ 'basic' ],
      'params' => [
        'url' => [
          'required' => true,
          'name' => tra('URL'),
          'description' => tra('Complete URL to the oEmbed video or media'),
          'filter' => 'url',
        ],
        'width' => [
          'required' => false,
          'name' => tra('Width'),
          'description' => tra('Width in pixels (e.g., 560). Leave empty to use provider dimensions.'),
          'filter' => 'digits',
        ],
        'height' => [
          'required' => false,
          'name' => tra('Height'),
          'description' => tra('Height in pixels (e.g., 315). Leave empty to use provider dimensions.'),
          'filter' => 'digits',
        ],
        'privacyEnhanced' => [
          'required' => false,
          'name' => tra('Privacy-Enhanced'),
          'description' => tra('Enable privacy-enhanced mode (if applicable)'),
          'default' => '',
          'filter' => 'alpha',
          'options' => [
            ['text' => '', 'value' => ''],
            ['text' => tra('Yes'), 'value' => 'y'],
            ['text' => tra('No'), 'value' => 'n'],
          ],
        ],
        'bg' => [
          'required' => false,
          'name' => tra('Background'),
          'description' => tra('Object background color. Example:') . ' <code>#ffffff</code>, <code>rgb(255, 255, 255)</code>, <code>white</code>',
          'accepted' => tra('Any valid CSS color value, e.g., hex, rgb(a), or color names'),
          'filter' => 'text',
          'advanced' => true
        ],
        'border' => [
          'required' => false,
          'name' => tra('Borders'),
          'description' => tra('Object border color. Example:') . ' <code>#ffffff</code>, <code>rgb(255, 255, 255)</code>, <code>white</code>',
          'accepted' => tra('Any valid CSS color value, e.g., hex, rgb(a), or color names'),
          'filter' => 'text',
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
        'start' => [
          'required' => false,
          'name' => tra('Start time'),
          'description' => tra('Start time offset in seconds'),
          'filter' => 'digits',
          'default' => 0,
        ],
        'allowFullScreen' => [
          'required' => false,
          'name' => tra('Allow full-screen'),
          'description' => tra('Enlarge video to full screen size'),
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

function wikiplugin_oembed($data, $params)
{
    if (! is_array($params)) {
        $params = [];
    }

    $oEmbedData = getOEmbedData($params['url']);
    if (! $oEmbedData) {
        Feedback::error(tra('Invalid URL or no oEmbed data found.'));
        return '';
    }

    $embedHtml = $oEmbedData['html'];
    $dom = new DOMDocument();

    libxml_use_internal_errors(true);

    $dom->loadHTML('<?xml encoding="UTF-8">' . $embedHtml);
    libxml_clear_errors();

    $iframe = $dom->getElementsByTagName('iframe')->item(0);
    if (! $iframe) {
        Feedback::error(tra('Plugin oEmbed error: no iframe found in oEmbed data.'));
        return '';
    }

    $newIframe = $dom->createElement('iframe');
    $newIframe->setAttribute('src', $iframe->getAttribute('src'));
    $newIframe->setAttribute('frameborder', '0');
    $newIframe->setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
    $newIframe->setAttribute('sandbox', 'allow-scripts allow-same-origin');
    $newIframe->setAttribute('title', tra('Embedded media content'));

    if (is_numeric($params['start']) && $params['start'] > 0) {
        $src = $newIframe->getAttribute('src');
        $newIframe->setAttribute('src', $src . (strpos($src, '?') === false ? '?' : '&') . 'start=' . $params['start']);
    }

    if ($params['allowFullScreen'] === 'y') {
        $newIframe->setAttribute('allowfullscreen', '');
    }

    $containerStyles = [
        'position' => 'relative',
        'overflow' => 'hidden',
        'max-width' => '100%',
        'max-height' => '75vh',
    ];

    $useResponsive = true;
    $aspectRatio = null;

    // Check for oEmbed-provided dimensions and compute aspect ratio if available
    if (
        isset($oEmbedData['width']) && is_numeric($oEmbedData['width']) && $oEmbedData['width'] > 0 &&
        isset($oEmbedData['height']) && is_numeric($oEmbedData['height']) && $oEmbedData['height'] > 0
    ) {
        $aspectRatio = $oEmbedData['width'] / $oEmbedData['height'];
    }

    if (! is_null($params['width']) && is_numeric($params['width']) && $params['width'] > 0) {
        $containerStyles['width'] = $params['width'] . 'px';
        $containerStyles['margin'] = '0 auto';
        $useResponsive = false;
        if (! empty($params['height']) && is_numeric($params['height']) && $params['height'] > 0) {
            $containerStyles['height'] = $params['height'] . 'px';
        } elseif ($aspectRatio) {
            $containerStyles['aspect-ratio'] = $aspectRatio;
        }
    } elseif (! is_null($params['height']) && is_numeric($params['height']) && $params['height'] > 0) {
        $containerStyles['height'] = $params['height'] . 'px';
        $useResponsive = false;
        if ($aspectRatio) {
            $containerStyles['width'] = (int)($params['height'] * $aspectRatio) . 'px';
            $containerStyles['margin'] = '0 auto';
        }
    }

    if ($useResponsive) {
        $containerStyles['width'] = '100%';
        if ($aspectRatio) {
            $containerStyles['aspect-ratio'] = $aspectRatio;
        } elseif (isset($oEmbedData['type']) && $oEmbedData['type'] === 'video') {
            $containerStyles['aspect-ratio'] = '16/9'; // Fall back
        }
    }

    if ($params['borderRadius'] === 'y') {
        $containerStyles['border-radius'] = '8px';
    }

    if (! is_null($params['bg'])) {
        $containerStyles['background-color'] = $params['bg'];
    }

    if (! is_null($params['border'])) {
        $containerStyles['border'] = '1px solid ' . $params['border'];
    }

    $iframeStyles = $useResponsive
        ? ['position' => 'absolute', 'top' => '0', 'left' => '0', 'width' => '100%', 'height' => '100%']
        : ['width' => '100%', 'height' => '100%'];

    $containerStyle = implode(';', array_map(function ($key, $value) {
        return "$key:$value";
    }, array_keys($containerStyles), $containerStyles));

    $iframeStyle = implode(';', array_map(function ($key, $value) {
        return "$key:$value";
    }, array_keys($iframeStyles), $iframeStyles));

    $newIframe->setAttribute('style', $iframeStyle);

    $embedHtml = '<div style="' . $containerStyle . '">' . $dom->saveHTML($newIframe) . '</div>';

    return '~np~' . $embedHtml . '~/np~';
}

function getOEmbedData($url)
{
    $url = filter_var($url, FILTER_SANITIZE_URL);

    if (! filter_var($url, FILTER_VALIDATE_URL)) {
        throw new Exception(tr('The provided URL is not a valid URL.'));
    }
    $parsedUrl = parse_url($url);
    $protocol = $parsedUrl['scheme'];
    $domain = $parsedUrl['host'];
    // TODO: use some discovery mechanism to be compatible with other oEmbed implementation
    $oEmbedUrl = "$protocol://$domain/services/oembed?url=" . urlencode($url);

    $response = @file_get_contents($oEmbedUrl);

    if ($response === false) {
        Feedback::error(tr('Error fetching oEmbed data for URL: %0', $oEmbedUrl));
        return false;
    }

    $oEmbedData = json_decode($response, true);

    if (isset($oEmbedData['html'])) {
        return $oEmbedData;
    }

    Feedback::error(tr('Invalid or malformed oEmbed data: %0', print_r($oEmbedData, true)));
    return false;
}
