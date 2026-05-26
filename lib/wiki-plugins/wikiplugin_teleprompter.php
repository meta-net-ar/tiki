<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function wikiplugin_teleprompter_info()
{
    return [
        'name' => tr('Teleprompter'),
        'documentation' => 'PluginTeleprompter',
        'description' => tr('Display vertical auto-scrolling teleprompter content with speed, mirror, timer, and focus controls.'),
        'prefs' => ['wikiplugin_teleprompter'],
        'body' => tr('Enter the teleprompter script. Plain text, Wiki Syntax and HTML are supported.'),
        'iconname' => 'align-justify',
        'introduced' => 30,
        'tags' => 'basic',
        'params' => [
            'fgalId' => [
                'required' => false,
                'name' => tr('File Gallery ID'),
                'description' => tr('Enter file gallery id for teleprompter media blocks'),
                'since' => '30.0',
                'separator' => ':',
                'profile_reference' => 'file_gallery',
            ],
            'fileIds' => [
                'required' => false,
                'name' => tr('File IDs'),
                'description' => tr('List of image file IDs from File Galleries separated by commas.'),
                'filter' => 'striptags',
                'default' => '',
            ],
            'background' => [
                'required' => false,
                'name' => tr('Background color'),
                'description' => tr('Teleprompter background color, for example #000.'),
                'filter' => 'text',
                'default' => '',
                'since' => '30.0',
            ],
            'width' => [
                'required' => false,
                'name' => tr('Width'),
                'description' => tr('Teleprompter width in px or %, default 100%.'),
                'filter' => 'word',
                'default' => '100%',
                'since' => '30.0',
            ],
            'height' => [
                'required' => false,
                'name' => tr('Height'),
                'description' => tr('Teleprompter height in px or %, default 80vh.'),
                'filter' => 'word',
                'default' => '80vh',
                'since' => '30.0',
            ],
            'headingsColor' => [
                'required' => false,
                'name' => tr('Headings color'),
                'description' => tr('Text color code for block headings, for example #ccc.'),
                'filter' => 'text',
                'default' => '',
                'since' => '30.0',
            ],
            'textColor' => [
                'required' => false,
                'name' => tr('Text color'),
                'description' => tr('Text color code for block text, for example #ccc.'),
                'filter' => 'text',
                'default' => '',
                'since' => '30.0',
            ],
            'textSize' => [
                'required' => false,
                'name' => tr('Text font size'),
                'description' => tr('For example 28px, default 28px.'),
                'filter' => 'word',
                'default' => '28px',
                'advanced' => true,
                'since' => '30.0',
            ],
            'slideContentBg' => [
                'required' => false,
                'name' => tr('Block background'),
                'description' => tr('Enter a valid CSS color code, or rgba value if opacity is desired; for example #000 or rgba(0, 0, 0, 0.5).'),
                'filter' => 'text',
                'default' => '',
                'since' => '30.0',
            ],
            'speed' => [
                'required' => false,
                'name' => tr('Speed'),
                'description' => tr('Scroll speed in pixels per second. Example: 35 (px/s).'),
                'filter' => 'digits',
                'default' => 35,
                'advanced' => true,
                'since' => '30.0',
            ],
            'acceleration' => [
                'required' => false,
                'name' => tr('Acceleration'),
                'description' => tr('Acceleration intensity applied when repeatedly changing speed. Example: 3 (level, 1 to 10).'),
                'filter' => 'digits',
                'default' => 3,
                'advanced' => true,
                'since' => '30.0',
            ],
            'mirror' => [
                'required' => false,
                'name' => tr('Mirror mode'),
                'description' => tr('Mirror text for physical teleprompters. Vertical mode also reverses scroll direction.'),
                'filter' => 'word',
                'default' => 'none',
                'advanced' => true,
                'options' => [
                    ['text' => tr('None'), 'value' => 'none'],
                    ['text' => tr('Horizontal'), 'value' => 'horizontal'],
                    ['text' => tr('Vertical'), 'value' => 'vertical'],
                ],
                'since' => '30.0',
            ],
            'focusMode' => [
                'required' => false,
                'name' => tr('Focus area'),
                'description' => tr('Highlight focus area while dimming non-reading areas.'),
                'filter' => 'word',
                'default' => 'top',
                'advanced' => true,
                'options' => [
                    ['text' => tr('None'), 'value' => 'none'],
                    ['text' => tr('Top'), 'value' => 'top'],
                    ['text' => tr('Middle'), 'value' => 'middle'],
                    ['text' => tr('Bottom'), 'value' => 'bottom'],
                ],
                'since' => '30.0',
            ],
            'timer' => [
                'required' => false,
                'name' => tr('Timer'),
                'description' => tr('Display running timer while prompting.'),
                'filter' => 'alpha',
                'default' => 'y',
                'advanced' => true,
                'options' => [
                    ['text' => tr('Yes'), 'value' => 'y'],
                    ['text' => tr('No'), 'value' => 'n'],
                ],
                'since' => '30.0',
            ],
            'controls' => [
                'required' => false,
                'name' => tr('Controls'),
                'description' => tr('Display touch-friendly Pause/Resume and Restart controls.'),
                'filter' => 'alpha',
                'default' => 'y',
                'advanced' => true,
                'options' => [
                    ['text' => tr('Yes'), 'value' => 'y'],
                    ['text' => tr('No'), 'value' => 'n'],
                ],
                'since' => '30.0',
            ],
            'fontScale' => [
                'required' => false,
                'name' => tr('Font scale'),
                'description' => tr('Initial font scale percentage. Example: 180 (%).'),
                'filter' => 'digits',
                'default' => 180,
                'advanced' => true,
                'since' => '30.0',
            ],
        ]
    ];
}

function wikiplugin_teleprompter($data, $params)
{
    $plugininfo = wikiplugin_teleprompter_info();

    if (empty($params['fileIds']) && empty($params['fgalId']) && empty($data)) {
        Feedback::error(tr('Parameters missing: Please either select file gallery, give file ids or enter teleprompter content in the body.'));
        return;
    }

    static $uid = 0;
    $uid++;

    $defaults = [];
    foreach ($plugininfo['params'] as $key => $param) {
        $defaults[$key] = $param['default'] ?? '';
    }
    $params = array_merge($defaults, $params);

    if (trim((string) $params['fileIds']) === '' && trim((string) $params['fgalId']) === '' && trim((string) $data) === '') {
        Feedback::error(tr('Parameters missing: Please either select file gallery, give file ids or enter teleprompter content in the body.'));
        return;
    }

    $heightCSS = 'height: 80vh;';
    if (! empty($params['height']) && preg_match('/^\d+(px|%|vh|vw)$/', $params['height'])) {
        $heightCSS = 'height: ' . $params['height'] . ';';
    }

    $headerlib = TikiLib::lib('header');
    $teleprompterBlocks = [];
    $files = ['data' => []];
    $filegallib = TikiLib::lib('filegal');
    if ($params['fgalId']) {
        $files = $filegallib->get_files(0, -1, '', '', $params['fgalId']);
        if (empty($files['data'])) {
            $files['data'] = [];
        }
    }
    if ($params['fileIds']) {
        foreach (explode(',', (string) $params['fileIds']) as $fileId) {
            $file = $filegallib->get_file($fileId);
            if (! is_null($file)) {
                $files['data'][] = $file;
            }
        }
    }

    if (! empty($files['data'])) {
        foreach ($files['data'] as $file) {
            $alt = htmlentities((string) ($file['description'] ?? ''), ENT_COMPAT);
            $imageHtml = '<img src="tiki-download_file.php?fileId=' . $file['fileId'] . '&amp;display" alt="' . $alt . '" />';
            $teleprompterBlocks[] = '<div class="teleprompter-block teleprompter-media">' . $imageHtml . '</div>';
        }
    }

    $parserlib = TikiLib::lib('parser');
    if (trim((string) $data) !== '') {
        $teleprompterBlocks[] = '<div class="teleprompter-block">' . $parserlib->parse_data($data, ['is_html' => true, 'parse_wiki' => true]) . '</div>';
    }
    if (empty($teleprompterBlocks)) {
        Feedback::error(tr('No teleprompter content was found.'));
        return;
    }

    $containerId = 'teleprompter-container' . $uid;
    $trackId = 'teleprompter-track' . $uid;
    $contentId = 'teleprompter-content' . $uid;
    $timerId = 'teleprompter-timer' . $uid;
    $teleprompterMinSpeed = 10;
    $teleprompterMaxSpeed = 500;
    $teleprompterSpeedStep = 10;
    $teleprompterMinAcceleration = 1;
    $teleprompterMaxAcceleration = 10;
    $teleprompterMinFontScale = 70;
    $teleprompterMaxFontScale = 300;
    $teleprompterFontScaleStep = 5;
    $teleprompterResizeDebounceMs = 120;

    $teleprompterSpeed = min($teleprompterMaxSpeed, max($teleprompterMinSpeed, (int) $params['speed']));
    $teleprompterAcceleration = min($teleprompterMaxAcceleration, max($teleprompterMinAcceleration, (int) $params['acceleration']));
    $teleprompterFontScale = min($teleprompterMaxFontScale, max($teleprompterMinFontScale, (int) $params['fontScale']));
    $showTimer = $params['timer'] === 'y';
    $timerClass = $params['timer'] === 'y' ? '' : ' teleprompter-timer-hidden';
    $showControls = $params['controls'] === 'y';
    $controlsClass = $showControls ? '' : ' teleprompter-controls-hidden';
    $mirrorMode = in_array($params['mirror'], ['none', 'horizontal', 'vertical'], true) ? $params['mirror'] : 'none';
    $mirrorClass = '';
    if ($mirrorMode === 'horizontal') {
        $mirrorClass = ' teleprompter-mirror-horizontal';
    } elseif ($mirrorMode === 'vertical') {
        $mirrorClass = ' teleprompter-mirror-vertical';
    }
    $focusMode = in_array($params['focusMode'], ['none', 'top', 'middle', 'bottom'], true) ? $params['focusMode'] : 'top';
    $focusClass = ' teleprompter-focus-' . $focusMode;
    $teleprompterHtml = implode('', $teleprompterBlocks);

    $teleprompterCssFile = 'lib/wiki-plugins/css/teleprompter.css';
    $teleprompterJsFile = 'lib/wiki-plugins/js/teleprompter.js';
    if (! file_exists(__DIR__ . '/css/teleprompter.css')) {
        Feedback::error(tr('File %0 is missing.', $teleprompterCssFile));
        return;
    }
    if (! file_exists(__DIR__ . '/js/teleprompter.js')) {
        Feedback::error(tr('File %0 is missing.', $teleprompterJsFile));
        return;
    }

    static $teleprompterAssetsLoaded = false;
    if (! $teleprompterAssetsLoaded) {
        $headerlib->add_cssfile($teleprompterCssFile)
            ->add_jsfile($teleprompterJsFile);
        $teleprompterAssetsLoaded = true;
    }

    $containerStyles = [
        '--teleprompter-font-scale:' . ($teleprompterFontScale / 100),
    ];
    $instanceStyleMap = [
        '--teleprompter-headings-color' => $params['headingsColor'],
        '--teleprompter-text-size' => $params['textSize'],
        '--teleprompter-text-color' => $params['textColor'],
        '--teleprompter-block-bg' => $params['slideContentBg'],
        'width' => $params['width'],
        'background' => $params['background'],
    ];
    foreach ($instanceStyleMap as $styleName => $styleValue) {
        if ((string) $styleValue !== '') {
            $containerStyles[] = $styleName . ':' . $styleValue;
        }
    }
    $containerStyles[] = rtrim($heightCSS, ';');
    $headerlib->add_css('#' . $containerId . '{' . implode(';', $containerStyles) . ';}');

    $teleprompterOptions = [
        'containerId' => $containerId,
        'trackId' => $trackId,
        'contentId' => $contentId,
        'timerId' => $timerId,
        'speed' => $teleprompterSpeed,
        'acceleration' => $teleprompterAcceleration,
        'fontScale' => $teleprompterFontScale,
        'showTimer' => $showTimer,
        'focusMode' => $focusMode,
        'showControls' => $showControls,
        'pauseLabel' => tr('Pause'),
        'resumeLabel' => tr('Resume'),
        'minSpeed' => $teleprompterMinSpeed,
        'maxSpeed' => $teleprompterMaxSpeed,
        'speedStep' => $teleprompterSpeedStep,
        'minFontScale' => $teleprompterMinFontScale,
        'maxFontScale' => $teleprompterMaxFontScale,
        'fontScaleStep' => $teleprompterFontScaleStep,
        'resizeDebounceMs' => $teleprompterResizeDebounceMs,
        'reverseScroll' => $mirrorMode === 'vertical',
    ];
    $headerlib->add_js(
        '$(function(){if(typeof window.tikiInitTeleprompter==="function"){window.tikiInitTeleprompter(' . json_encode($teleprompterOptions) . ');}});'
    );

    $teleprompterRegionLabel = htmlspecialchars(tr('Teleprompter content'), ENT_QUOTES);
    $teleprompterControlsLabel = htmlspecialchars(tr('Teleprompter controls'), ENT_QUOTES);
    $pauseButtonLabel = htmlspecialchars(tr('Pause'), ENT_QUOTES);
    $restartButtonLabel = htmlspecialchars(tr('Restart'), ENT_QUOTES);

    return '<div id="' . $containerId . '" class="teleprompter-container' . $mirrorClass . $focusClass . '" role="region" aria-label="' . $teleprompterRegionLabel . '" tabindex="0">' .
        '<div id="' . $trackId . '" class="teleprompter-track">' .
        '<div id="' . $contentId . '" class="teleprompter-content"><div class="teleprompter-spacer teleprompter-spacer-before" aria-hidden="true"></div>' . $teleprompterHtml . '<div class="teleprompter-spacer teleprompter-spacer-after" aria-hidden="true"></div></div>' .
        '</div>' .
        '<div class="teleprompter-focus-overlay" aria-hidden="true"><div class="tp-focus-mask tp-focus-mask-top"></div><div class="tp-focus-mask tp-focus-mask-bottom"></div><div class="tp-focus-guide"></div></div>' .
        '<div class="teleprompter-progress" aria-hidden="true"><div class="teleprompter-progress-thumb"></div></div>' .
        '<div class="teleprompter-controls' . $controlsClass . '" aria-label="' . $teleprompterControlsLabel . '">' .
        '<button type="button" class="teleprompter-btn teleprompter-btn-toggle" data-tp-toggle="1" aria-pressed="false">' . $pauseButtonLabel . '</button>' .
        '<button type="button" class="teleprompter-btn teleprompter-btn-restart" data-tp-restart="1">' . $restartButtonLabel . '</button>' .
        '</div>' .
        '<div id="' . $timerId . '" class="teleprompter-timer' . $timerClass . '" aria-live="polite" aria-atomic="true">00:00</div>' .
        '</div>';
}
