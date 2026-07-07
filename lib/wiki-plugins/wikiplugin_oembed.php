<?php

require_once 'lib/wiki-plugins/shared/embed_helpers.php';

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

    $iframeSrc = $iframe->getAttribute('src');

    $embedHtml = buildEmbedContainerAndIframe($iframeSrc, $params, $oEmbedData);

    return '~np~' . $embedHtml . '~/np~';
}

function getOEmbedData($url)
{
    $url = filter_var($url, FILTER_SANITIZE_URL);

    if (! filter_var($url, FILTER_VALIDATE_URL)) {
        throw new Exception(tr('The provided URL is not a valid URL.'));
    }

    // Prevent SSRF: the user-supplied URL determines which host the server contacts
    // for oEmbed discovery. Block private/reserved IPs and non-http(s) schemes.
    $ssrf = \Tiki\Security\SsrfLib::fromPrefs();
    if (! $ssrf->isUrlAllowed($url)) {
        throw new Exception(tra('URL is not allowed (targets a private or reserved address).'));
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

    Feedback::error(tr('Invalid or malformed oEmbed data: %0', print_r($oEmbedData, true))); // @phpstan-ignore disallowedFunctions.printR (returns malformed response as string for error feedback, never prints)
    return false;
}
