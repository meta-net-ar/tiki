<?php

function wikiplugin_oembed_info()
{
    return [
      'name' => 'oEmbed',
      'documentation' => 'PluginOEmbed',
      'description' => tra('Embed a video or media using oEmbed protocol'),
      'prefs' => [ 'wikiplugin_oembed' ],
      'iconname' => 'oembed',
      'introduced' => 29,
      'tags' => [ 'basic' ],
      'params' => [
        'url' => [
          'required' => true,
          'name' => tra('URL'),
          'description' => tra('Complete URL to the oEmbed video or media'),
          'filter' => 'url',
          'default' => '',
        ],
        'width' => [
          'required' => false,
          'name' => tra('Width'),
          'description' => tra('Width in pixels. Default: ') . '<code>560</code>',
          'filter' => 'digits',
          'default' => 560,
        ],
        'height' => [
          'required' => false,
          'name' => tra('Height'),
          'description' => tra('Height in pixels. Default: ') . '<code>315</code>',
          'filter' => 'digits',
          'default' => 315,
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
          'default' => '',
          'advanced' => true
        ],
        'border' => [
          'required' => false,
          'name' => tra('Borders'),
          'description' => tra('Object border color. Example:') . ' <code>#ffffff</code>, <code>rgb(255, 255, 255)</code>, <code>white</code>',
          'accepted' => tra('Any valid CSS color value, e.g., hex, rgb(a), or color names'),
          'filter' => 'text',
          'default' => '',
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
            ['text' => '', 'value' => ''],
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

    $plugininfo = wikiplugin_oembed_info();
    foreach ($plugininfo['params'] as $key => $param) {
        $default["$key"] = $param['default'];
    }
    $params = array_merge($default, $params);

    if (empty($params['url'])) {
        Feedback::error(tra('Plugin oEmbed error: the URL parameter is empty.'));
        return '';
    }

    $oEmbedData = getOEmbedData($params['url']);
    if (! $oEmbedData) {
        Feedback::error(tra('Invalid URL or no oEmbed data found.'));
        return '';
    }

    $embedHtml = $oEmbedData['html'];
    $dom = new DOMDocument();

    libxml_use_internal_errors(true);

    $dom->loadHTML($embedHtml);
    libxml_clear_errors();

    $iframe = $dom->getElementsByTagName('iframe')->item(0);
    if (! $iframe) {
        Feedback::error(tra('Plugin oEmbed error: no iframe found in oEmbed data.'));
        return '';
    }

    $newIframe = $dom->createElement('iframe');
    $newIframe->setAttribute('src', $iframe->getAttribute('src'));
    $newIframe->setAttribute('width', $params['width']);
    $newIframe->setAttribute('height', $params['height']);
    $newIframe->setAttribute('sandbox', 'allow-scripts allow-same-origin');

    if (! empty($params['bg'])) {
        $newIframe->setAttribute('style', 'background-color:' . $params['bg']);
    }

    if (! empty($params['border'])) {
        $style = $newIframe->getAttribute('style');
        $newIframe->setAttribute('style', $style . '; border: 1px solid ' . $params['border']);
    }

    if (isset($params['start']) && is_numeric($params['start']) && $params['start'] > 0) {
        $src = $newIframe->getAttribute('src');
        $newIframe->setAttribute('src', $src . (strpos($src, '?') === false ? '?' : '&') . 'start=' . $params['start']);
    }

    if ($params['allowFullScreen'] === 'y') {
        $newIframe->setAttribute('allowfullscreen', 'true');
    }

    $embedHtml = $dom->saveHTML($newIframe);

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
