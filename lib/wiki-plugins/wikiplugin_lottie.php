<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * Wiki Plugin: Lottie
 *
 * Embeds a Lottie animation in a wiki page using the dotLottie Web Component.
 * Supports both .json (legacy) and .lottie (compressed) formats.
 *
 * @see https://doc.tiki.org/PluginLottie
 * @see https://github.com/LottieFiles/dotlottie-web
 */

function wikiplugin_lottie_info()
{
    return [
        'name' => tra('Lottie'),
        'documentation' => 'PluginLottie',
        'description' => tra('Embed a Lottie animation in a page'),
        'prefs' => ['wikiplugin_lottie'],
        'iconname' => 'video',
        'tags' => ['basic'],
        'params' => [
            'src' => [
                'required' => false,
                'name' => tra('Source URL'),
                'description' => tra('URL to the Lottie animation file (.json or .lottie). Use either src or fileId.'),
                'filter' => 'url',
                'default' => '',
            ],
            'fileId' => [
                'required' => false,
                'name' => tra('File ID'),
                'description' => tra('ID of the Lottie file in the File Gallery. Use either fileId or src.'),
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'file',
            ],
            'loop' => [
                'required' => false,
                'name' => tra('Loop'),
                'description' => tra('Loop the animation continuously.'),
                'filter' => 'alpha',
                'default' => 'y',
                'options' => [
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'autoplay' => [
                'required' => false,
                'name' => tra('Autoplay'),
                'description' => tra('Start animation automatically when page loads.'),
                'filter' => 'alpha',
                'default' => 'y',
                'options' => [
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'speed' => [
                'required' => false,
                'name' => tra('Speed'),
                'description' => tra('Playback speed multiplier (e.g., 0.5 for half speed, 2 for double speed).'),
                'filter' => 'text',
                'default' => '1',
            ],
            'mode' => [
                'required' => false,
                'name' => tra('Play Mode'),
                'description' => tra('Animation playback mode.'),
                'filter' => 'alpha',
                'default' => 'forward',
                'options' => [
                    ['text' => tra('Forward'), 'value' => 'forward'],
                    ['text' => tra('Reverse'), 'value' => 'reverse'],
                    ['text' => tra('Bounce'), 'value' => 'bounce'],
                    ['text' => tra('Reverse Bounce'), 'value' => 'reverse-bounce'],
                ],
                'advanced' => true,
            ],
            'width' => [
                'required' => false,
                'name' => tra('Width'),
                'description' => tra('Width of the animation container (e.g., 300px, 50%, auto).'),
                'filter' => 'text',
                'default' => '100%',
            ],
            'height' => [
                'required' => false,
                'name' => tra('Height'),
                'description' => tra('Height of the animation container (e.g., 300px, auto).'),
                'filter' => 'text',
                'default' => 'auto',
            ],
            'background' => [
                'required' => false,
                'name' => tra('Background'),
                'description' => tra('Background color of the animation container (e.g., transparent, #ffffff, rgb(0,0,0)).'),
                'filter' => 'text',
                'default' => 'transparent',
                'advanced' => true,
            ],
        ],
    ];
}

function wikiplugin_lottie($data, $params)
{
    global $tikilib;

    // Validate: either src or fileId must be provided
    $src = $params['src'] ?? '';
    $fileId = $params['fileId'] ?? '';

    if (empty($src) && empty($fileId)) {
        Feedback::error(tra('Plugin Lottie error: either src or fileId parameter is required.'));
        return '<div class="alert alert-warning">'
            . tra('Plugin Lottie error: either src or fileId parameter is required.')
            . '</div>';
    }

    // If fileId is provided, generate the download URL
    if (! empty($fileId)) {
        $src = 'tiki-download_file.php?fileId=' . (int)$fileId;
    }

    // Validate URL format for external sources
    if (strpos($src, 'http') === 0) {
        $src = filter_var($src, FILTER_SANITIZE_URL);
        if (! filter_var($src, FILTER_VALIDATE_URL)) {
            Feedback::error(tra('Plugin Lottie error: Invalid URL provided.'));
            return '<div class="alert alert-warning">'
                . tra('Plugin Lottie error: Invalid URL provided.')
                . '</div>';
        }
    }

    // Parse parameters with defaults
    $loop = ($params['loop'] ?? 'y') === 'y';
    $autoplay = ($params['autoplay'] ?? 'y') === 'y';
    $speed = floatval($params['speed'] ?? 1);
    $mode = $params['mode'] ?? 'forward';
    $width = $params['width'] ?? '100%';
    $height = $params['height'] ?? 'auto';
    $background = $params['background'] ?? 'transparent';

    // Build HTML output
    $loopAttr = $loop ? 'loop' : '';
    $autoplayAttr = $autoplay ? 'autoplay' : '';

    // Generate unique ID for this animation instance
    static $instanceCount = 0;
    $instanceCount++;
    $elementId = 'tiki-lottie-' . $instanceCount;

    TikiLib::lib('header')->add_js_module('import "@tiki-lottie";');

    $html = '<div class="tiki-lottie-container" style="width: ' . htmlspecialchars($width) . '; height: ' . htmlspecialchars($height) . '; background: ' . htmlspecialchars($background) . ';">';
    $html .= '<dotlottie-wc id="' . $elementId . '" src="' . htmlspecialchars($src) . '" ' . $loopAttr . ' ' . $autoplayAttr . ' speed="' . htmlspecialchars($speed) . '" mode="' . htmlspecialchars($mode) . '" style="width: 100%; height: 100%;"></dotlottie-wc>';
    $html .= '</div>';

    return '~np~' . $html . '~/np~';
}
