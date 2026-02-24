<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\WikiPlugin\Options\BooleanEnglishLetter;

function wikiplugin_swiper_info()
{
    return [
        'name' => tr('Swiper'),
        'documentation' => 'PluginSwiper',
        'description' => tr('Embed swiper in content, support file galleries, files id and custom content'),
        'prefs' => ['wikiplugin_swiper'],
        'body' => tr('Enter custom slides data separated by "|". Wiki Syntax / HTML supported in slide text, to include wiki page in slide text use pluginInclude.<code>title:Slide 1 title;text:HTML/Wiki Syntax Supported Slide 1 text;image:Slide Image URL;bgcolor:#colorcode;color: #color code for text | title:Slide 2 title;text:Slide 2 text;image:Slide Image URL;bgcolor:#colorcode</code> '),
        'iconname' => 'tv',
        'introduced' => 19,
        'tags' => 'basic',
        'params' => [
            'fgalId' => [
                'required' => false,
                'name' => tr('File Gallery ID'),
                'description' => tr('Enter file gallery id for slider'),
                'since' => '19',
                'separator' => ':',
                'profile_reference' => 'file_gallery',
            ],
            'fileIds' => [
                'required' => false,
                'name' => tr('File IDs'),
                'description' => tr('List of IDs of images from the File Galleries separated by commas.'),
                'filter' => 'striptags',
                'default' => '',
            ],
            'effect' => [
                'required' => false,
                'name' => tr('Transition Effect'),
                'description' => tr('Transition effect. Could be "slide", "fade", "cube", "coverflow" or "flip"'),
                'filter' => 'word',
                'default' => 'slide',
                'since' => '19.0',
                'options' => [
                    ['text' => 'Slide', 'value' => 'slide'],
                    ['text' => 'Fade', 'value' => 'fade'],
                    ['text' => 'Cube', 'value' => 'cube'],
                    ['text' => 'Coverflow', 'value' => 'coverflow'],
                    ['text' => 'Flip', 'value' => 'flip'],
                    ['text' => 'Creative', 'value' => 'creative'],
                    ['text' => 'Cards', 'value' => 'cards'],
                ],
            ],
            'sliderPosition' => [
                'required' => false,
                'name' => tr('Slider Placement on Page'),
                'description' => tr('Placement of slider, above topbar, below topbar, above menus and content or inside content'),
                'filter' => 'word',
                'default' => '',
                'since' => '19.0',
                'options' => [
                    ['text' => tr(''), 'value' => ''],
                    ['text' => tr('Inside Content'), 'value' => ''],
                    ['text' => tr('Above top bar / Top of page'), 'value' => 'abovetopbar'],
                    ['text' => tr('Above Content/Under top bar'), 'value' => 'undertopbar']
                ],
            ],
            'pagination' => [
                'required' => false,
                'name' => tr('Pagination'),
                'description' => tr('Slider pagination, default bullets'),
                'filter' => 'word',
                'default' => 'bullets',
                'since' => '19.0',
                'advanced' => true,
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => 'Off', 'value' => 'n'],
                    ['text' => tr('Bullets'), 'value' => 'bullets'],
                    ['text' => tr('Fraction'), 'value' => 'fraction'],
                    ['text' => tr('Progress bar'), 'value' => 'progressbar'],
                ],
            ],
            'navigation' => [
                'required' => false,
                'name' => tr('Navigation'),
                'description' => tr('Display navigation arrows'),
                'filter' => 'alpha',
                'default' => BooleanEnglishLetter::Yes->value,
                'since' => '19.0',
                'options' => BooleanEnglishLetter::options(),
            ],
            'background' => [
                'required' => false,
                'name' => tr('Slider Background Color'),
                'description' => tr('Slider background color, enter color code for example #000'),
                'since' => '19.0'
            ],
            'parallaxBgImg' => [
                'required' => false,
                'name' => tr('Slider Parallax Background Image'),
                'description' => tr('Enter image url for parallax background behind swiper'),
                'filter' => 'text',
                'default' => '',
                'advanced' => true,
                'since' => '19.0'
            ],
            'width' => [
                'required' => false,
                'name' => tr('Width'),
                'description' => tr('Enter width of slider in px or %, default 100%'),
                'filter' => 'word',
                'default' => '100%',
                'since' => '19.0'
            ],
            'height' => [
                'required' => false,
                'name' => tr('Height'),
                'description' => tr('Enter height of slider in px or %, default min height 100px, max height will adjust with content'),
                'filter' => 'word',
                'default' => '',
                'since' => '19.0'
            ],
            'titleColor' => [
                'required' => false,
                'name' => tr('Slide title color'),
                'description' => tr('Enter text color code of slide title, for example #ccc'),
                'filter' => 'text',
                'default' => '',
                'since' => '19.0'
            ],
            'titleSize' => [
                'required' => false,
                'name' => tr('Title font size'),
                'description' => tr('For example 42px, default 32 px'),
                'filter' => 'word',
                'default' => '32px',
                'advanced' => true,
                'since' => '19.0'
            ],
            'descriptionColor' => [
                'required' => false,
                'name' => tr('Slide description color'),
                'description' => tr('Enter text color code of slide description, for example #ccc'),
                'filter' => 'text',
                'default' => '',
                'since' => '19.0'
            ],
            'descriptionSize' => [
                'required' => false,
                'name' => tr('Description font size'),
                'description' => tr('For example 24px, default 16 px'),
                'filter' => 'word',
                'default' => '16px',
                'advanced' => true,
                'since' => '19.0'
            ],
            'slideContentBg' => [
                'required' => false,
                'name' => tr('Slide content background'),
                'description' => tr('Enter a valid CSS color code, or an rgba value if opacity is desired; for example: #000 or rgba(00, 00, 00, 0.5).'),
                'filter' => 'text',
                'default' => '',
                'since' => '19.0'
            ],
            'slideContentPostion' => [
                'required' => false,
                'name' => tr('Slide content position'),
                'description' => tr('Enter position for example top:20%;left:20% or bottom:20%;right:10%'),
                'filter' => 'text',
                'default' => 'top:20%;left:20%',
                'since' => '19.0'
            ],
            'autoPlay' => [
                'required' => false,
                'name' => tr('Auto Play'),
                'description' => tr('Autoplay slider, on by default'),
                'filter' => 'alpha',
                'default' => 'y',
                'since' => '19.0',
                'advanced' => true,
                'options' => BooleanEnglishLetter::options(),
            ],
            'autoPlayDelay' => [
                'required' => false,
                'name' => tr('Auto Play Delay'),
                'description' => tr('Time interval to pause before moving to next slide in seconds.'),
                'filter' => 'digits',
                'default' => '5',
                'since' => '19.0'
            ],
            'displayThumbnails' => [
                'required' => false,
                'name' => tr('Display Thumbnails'),
                'description' => tr('Show thumbnails under main slider'),
                'filter' => 'alpha',
                'default' => BooleanEnglishLetter::No->value,
                'since' => '19.0',
                'options' => BooleanEnglishLetter::options(),
            ],
            'speed' => [
                'required' => false,
                'name' => tr('Speed'),
                'description' => tr('Duration of transition between slides (in milliseconds)'),
                'filter' => 'digits',
                'default' => 300,
                'advance' => true,
                'since' => '19.0'
            ],
            'autoHeight' => [
                'required' => false,
                'name' => tr('Auto Height'),
                'description' => tr('Set to true and slider wrapper will adapt its height to the height of the currently active slide'),
                'filter' => 'alpha',
                'default' => 'n',
                'since' => '19.0',
                'advanced' => true,
                'options' => BooleanEnglishLetter::options(),
            ],
            //Slides grid
            'spaceBetween' => [
                'required' => false,
                'name' => tr('Space Between'),
                'description' => tr('Distance between slides in px.'),
                'filter' => 'digits',
                'default' => 0,
                'advanced' => true,
                'since' => '19.0'
            ],
            'slidesPerView' => [
                'required' => false,
                'name' => tr('Slides Per View'),
                'description' => tr('Slides visible at the same time on slider\'s container. Coverflow transition works best with 3 slides per view'),
                'filter' => 'digits',
                'default' => 1,
                'since' => '19.0'
            ],
            'slidesPerViewMobile' => [
                'required' => false,
                'name' => tr('Slides Per View Mobile Screen'),
                'description' => tr('Slides visible at the same time on small screens'),
                'filter' => 'digits',
                'default' => 0,
                'advanced' => true,
                'since' => '19.0'
            ],
            'slidesPerViewTab' => [
                'required' => false,
                'name' => tr('Slides Per View Tablet'),
                'description' => tr('Slides visible at the same time on low resolution tablets'),
                'filter' => 'digits',
                'default' => 0,
                'advanced' => true,
                'since' => '19.0'
            ],
            'slidesPerColumn' => [
                'required' => false,
                'name' => tr('Slides Per Column'),
                'description' => tr('Number of slides per column, for multirow layout'),
                'filter' => 'digits',
                'default' => 1,
                'advanced' => true,
                'since' => '19.0'
            ],
            'slidesPerColumnFill' => [
                'required' => false,
                'name' => tr('Slides Per Column Fill'),
                'description' => tr('Could be \'column\' or \'row\'. Defines how slides should fill rows, by column or by row'),
                'filter' => 'word',
                'default' => 'column',
                'since' => '19.0',
                'advanced' => true,
                'options' => [
                    ['text' => 'Column', 'value' => 'column'],
                    ['text' => 'Row', 'value' => 'row'],
                ],
            ],
            'centeredSlides' => [
                'required' => false,
                'name' => tr('Centered Slides'),
                'description' => tr('If true, then active slide will be centered, not always on the left side.'),
                'filter' => 'alpha',
                'default' => BooleanEnglishLetter::Yes->value,
                'since' => '19.0',
                'advanced' => true,
                'options' => BooleanEnglishLetter::options(),
            ],
            'slidesOffsetBefore' => [
                'required' => false,
                'name' => tr('Slides Offset Before'),
                'description' => tr('Add (in px) additional slide offset in the beginning of the container (before all slides)'),
                'filter' => 'digits',
                'default' => 0,
                'advanced' => true,
                'since' => '19.0'
            ],
            'slidesOffsetAfter' => [
                'required' => false,
                'name' => tr('Slides Offset After'),
                'description' => tr('Add (in px) additional slide offset in the end of the container (after all slides)'),
                'filter' => 'digits',
                'default' => 0,
                'advanced' => true,
                'since' => '19.0'
            ],
            'slideToClickedSlide' => [
                'required' => false,
                'name' => tr('Slide To Clicked Slide'),
                'description' => tr('Set to true and click on any slide will produce transition to this slide.'),
                'filter' => 'word',
                'since' => '19.0',
                'advanced' => true,
                'default' => BooleanEnglishLetter::No->value,
                'options' => BooleanEnglishLetter::options(),
            ],
            //freemode
            'freeMode' => [
                'required' => false,
                'name' => tr('Free Mode'),
                'description' => tr('If true then slides will not have fixed positions.'),
                'filter' => 'word',
                'since' => '19.0',
                'advanced' => true,
                'default' => BooleanEnglishLetter::No->value,
                'options' => BooleanEnglishLetter::options(),
            ],
            //Images
            'preloadImages' => [
                'required' => false,
                'name' => tr('Preload Images'),
                'description' => tr('When enabled Swiper will force to load all images.'),
                'filter' => 'word',
                'default' => BooleanEnglishLetter::Yes->value,
                'since' => '19.0',
                'advanced' => true,
                'options' => BooleanEnglishLetter::options(),
            ],
            'updateOnImagesReady' => [
                'required' => false,
                'name' => tr('Update On Images Ready'),
                'description' => tr('When enabled Swiper will be reinitialized after all inner images (<img> tags) are loaded. Required preloadimages: true.'),
                'filter' => 'word',
                'default' => BooleanEnglishLetter::Yes->value,
                'since' => '19.0',
                'advanced' => true,
                'options' => BooleanEnglishLetter::options(),
            ],
            //Loop
            'loop' => [
                'required' => false,
                'name' => tr('Loop Slider'),
                'description' => tr('Set to true to enable continuous loop mode (If you use it along with slidesperView: \'auto\' then you need to specify loopedslides parameter with amount of slides to loop (duplicate)).'),
                'filter' => 'word',
                'default' => BooleanEnglishLetter::No->value,
                'since' => '19.0',
                'options' => BooleanEnglishLetter::options(),
            ],
            'teleprompter' => [
                'required' => false,
                'name' => tr('Teleprompter mode'),
                'description' => tr('Scroll slides vertically as a teleprompter instead of swapping slides.'),
                'filter' => 'alpha',
                'default' => 'n',
                'advanced' => true,
                'options' => [
                    ['text' => 'No', 'value' => 'n'],
                    ['text' => 'Yes', 'value' => 'y'],
                ],
            ],
            'teleprompterSpeed' => [
                'required' => false,
                'name' => tr('Teleprompter speed'),
                'description' => tr('Scroll speed in pixels per second when teleprompter is enabled. Example: 60 (px/s).'),
                'filter' => 'digits',
                'default' => 60,
                'advanced' => true,
            ],
            'teleprompterAcceleration' => [
                'required' => false,
                'name' => tr('Teleprompter acceleration'),
                'description' => tr('Acceleration intensity applied when repeatedly changing speed. Example: 3 (level, 1 to 10).'),
                'filter' => 'digits',
                'default' => 3,
                'advanced' => true,
            ],
            'teleprompterMirror' => [
                'required' => false,
                'name' => tr('Mirror teleprompter'),
                'description' => tr('Flip text horizontally for physical teleprompter use.'),
                'filter' => 'alpha',
                'default' => 'n',
                'advanced' => true,
                'options' => [
                    ['text' => 'No', 'value' => 'n'],
                    ['text' => 'Yes', 'value' => 'y'],
                ],
            ],
            'teleprompterPauseOnHover' => [
                'required' => false,
                'name' => tr('Pause teleprompter on hover'),
                'description' => tr('Stop automatic scrolling while the teleprompter is hovered.'),
                'filter' => 'alpha',
                'default' => 'y',
                'advanced' => true,
                'options' => [
                    ['text' => 'Yes', 'value' => 'y'],
                    ['text' => 'No', 'value' => 'n'],
                ],
            ],
            'teleprompterFocusMode' => [
                'required' => false,
                'name' => tr('Teleprompter focus area'),
                'description' => tr('Highlight focus area while dimming non-reading areas. Example: middle (none, top, middle, bottom).'),
                'filter' => 'word',
                'default' => 'top',
                'advanced' => true,
                'options' => [
                    ['text' => tr('None'), 'value' => 'none'],
                    ['text' => tr('Top'), 'value' => 'top'],
                    ['text' => tr('Middle'), 'value' => 'middle'],
                    ['text' => tr('Bottom'), 'value' => 'bottom'],
                ],
            ],
            'teleprompterTimer' => [
                'required' => false,
                'name' => tr('Teleprompter timer'),
                'description' => tr('Display running timer while prompting.'),
                'filter' => 'alpha',
                'default' => 'y',
                'advanced' => true,
                'options' => [
                    ['text' => 'Yes', 'value' => 'y'],
                    ['text' => 'No', 'value' => 'n'],
                ],
            ],
            'teleprompterFontScale' => [
                'required' => false,
                'name' => tr('Teleprompter font scale'),
                'description' => tr('Initial font scale percentage for teleprompter mode. Example: 120 (%).'),
                'filter' => 'digits',
                'default' => 120,
                'advanced' => true,
            ],
        ]
    ];
}

function wikiplugin_swiper($data, $params)
{
    if (empty($params['fileIds']) && empty($params['fgalId']) && empty($data)) {
        Feedback::error(tr('Parameters missing: Please either select file gallery, give file ids or custom slide code in body.'));
        return;
    }
    static $uid = 0;
    $uid++;

    $plugininfo = wikiplugin_swiper_info();
    $defaults = [];
    foreach ($plugininfo['params'] as $key => $param) {
        $defaults[$key] = $param['default'] ?? '';
    }
    $params = array_merge($defaults, $params);
    $params += [
        'teleprompter' => 'n',
        'teleprompterSpeed' => 60,
        'teleprompterAcceleration' => 3,
        'teleprompterMirror' => 'n',
        'teleprompterPauseOnHover' => 'y',
        'teleprompterFocusMode' => 'middle',
        'teleprompterTimer' => 'y',
        'teleprompterFontScale' => 120,
    ];
    if (trim((string) $params['fileIds']) === '' && trim((string) $params['fgalId']) === '' && trim((string) $data) === '') {
        Feedback::error(tr('Parameters missing: Please either select file gallery, give file ids or custom slide code in body.'));
        return;
    }

    $isTeleprompter = $params['teleprompter'] === 'y';
    $swiperDigitsParams = [];
    foreach ($plugininfo['params'] as $key => $param) {
        if (! empty($param['filter']) && $param['filter'] === 'digits') {
            $swiperDigitsParams[] = $key;
        }
    }

    $heightMode = 'tallest';
    $heightCSS = '';
    if ($params['autoHeight'] === 'y') {
        $heightMode = 'variable';
        $heightCSS = 'height: auto;';
    } elseif (! empty($params['height']) && preg_match('/^\d+(px|%)$/', $params['height'])) {
        $heightMode = 'custom';
        $heightCSS = 'height: ' . $params['height'] . ';';
    } elseif ($isTeleprompter) {
        $heightMode = 'custom';
        $heightCSS = 'height: 60vh;';
    }

    $headerlib = TikiLib::lib('header');
    $slides = explode('|', ($data ?: ''));
    $teleprompterBlocks = [];
    $slidesHtml = '';
    $slideClass = 'swiper-slide';
    $parallaxAttr = 'data-swiper-parallax="-300"';

    $files = ['data' => []];
    $filegallib = TikiLib::lib('filegal');
    if ($params['fgalId']) {
        $files = $filegallib->get_files(0, -1, '', '', $params['fgalId']);
        if (empty($files['data'])) {
            $files['data'] = [];
        }
    }
    if ($params['fileIds']) {
        foreach (explode(',', $params['fileIds']) as $fileId) {
            $file = $filegallib->get_file($fileId);
            if (! is_null($file)) {
                $files['data'][] = $file;
            }
        }
    }

    if (! empty($files['data'])) {
        foreach ($files['data'] as $file) {
            $slidesHtml .= '<div class="' . $slideClass . '"><img src="tiki-download_file.php?fileId=' . $file['fileId'] . '&amp;display';
            if (! empty($params['displaySize'])) {
                if ($params['displaySize'] > 10) {
                    $slidesHtml .= '&amp;max=' . $params['displaySize'];
                } elseif ($params['displaySize'] <= 1) {
                    $slidesHtml .= '&amp;scale=' . $params['displaySize'];
                }
            }
            $alt = htmlentities((string) ($file['description'] ?? ''), ENT_COMPAT);
            $imageHtml = '<img src="tiki-download_file.php?fileId=' . $file['fileId'] . '&amp;display" alt="' . $alt . '" />';
            $slidesHtml .= '" alt="' . $alt . '" /></div>';
            $teleprompterBlocks[] = '<div class="teleprompter-block teleprompter-media">' . $imageHtml . '</div>';
        }
    }

    $parserlib = TikiLib::lib('parser');
    foreach ($slides as $slide) {
        if (trim($slide)) {
            $slideArr = array_filter(explode(';', str_replace(["\n", "\r"], '', $slide)));
            $parsedSlide = [];
            if (count($slideArr) > 0) {
                foreach ($slideArr as $slideValue) {
                    $slideData = explode(':', $slideValue, 2);
                    if (count($slideData) === 2) {
                        $parsedSlide[trim($slideData[0])] = trim($slideData[1]);
                    }
                }
            }
            if (empty($parsedSlide['text']) && ! empty($slideArr[0])) {
                $parsedSlide['text'] = $slideArr[0];
            }

            $textHtml = '';
            if (! empty($parsedSlide['text'])) {
                $textHtml = '<div>' . $parserlib->parse_data($parsedSlide['text'], ['is_html' => true, 'parse_wiki' => true]) . '</div>';
            }
            $color = $parsedSlide['color'] ?? '';
            $bgColor = $parsedSlide['bgcolor'] ?? '';
            $title = $parsedSlide['title'] ?? '';
            $image = $parsedSlide['image'] ?? '';

            $slidesHtml .= '<div ' . $parallaxAttr . ' class="' . $slideClass . '" style="color:' . $color . ';background-color:' . $bgColor . ';background-image:url(' . htmlspecialchars($image, ENT_QUOTES) . ')"><div class="slide-content' . $uid . '"><h1>' . $title . '</h1>' . $textHtml . '</div></div>';
            $teleprompterBlocks[] = '<div class="teleprompter-block"><h1>' . $title . '</h1>' . $textHtml . '</div>';
        }
    }

    if ($isTeleprompter) {
        if (empty($teleprompterBlocks) && trim((string) $data) !== '') {
            $teleprompterBlocks[] = '<div class="teleprompter-block">' . $parserlib->parse_data($data, ['is_html' => true, 'parse_wiki' => true]) . '</div>';
        }
        if (empty($teleprompterBlocks)) {
            Feedback::error(tr('No teleprompter content was found.'));
            return;
        }

        $containerId = 'swiper-container' . $uid;
        $trackId = 'teleprompter-track' . $uid;
        $contentId = 'teleprompter-content' . $uid;
        $timerId = 'teleprompter-timer' . $uid;
        $teleprompterSpeed = max(10, (int) $params['teleprompterSpeed']);
        $teleprompterAcceleration = min(10, max(1, (int) $params['teleprompterAcceleration']));
        $teleprompterFontScale = min(300, max(70, (int) $params['teleprompterFontScale']));
        $pauseOnHover = $params['teleprompterPauseOnHover'] === 'y' ? 'true' : 'false';
        $mirrorClass = $params['teleprompterMirror'] === 'y' ? ' teleprompter-mirror' : '';
        $showTimer = $params['teleprompterTimer'] === 'y' ? 'true' : 'false';
        $timerClass = $params['teleprompterTimer'] === 'y' ? '' : ' teleprompter-timer-hidden';
        $focusMode = in_array($params['teleprompterFocusMode'], ['none', 'top', 'middle', 'bottom'], true) ? $params['teleprompterFocusMode'] : 'middle';
        $focusClass = ' teleprompter-focus-' . $focusMode;
        $teleprompterHtml = implode('', $teleprompterBlocks);

        $headerlib->add_css(
            '#' . $containerId . '{--teleprompter-font-scale:' . ($teleprompterFontScale / 100) . ';position:relative;overflow:hidden;' . $heightCSS . 'width:' . $params['width'] . ';background:' . $params['background'] . ';margin-bottom:20px;}' .
            '#' . $containerId . '.teleprompter-mirror{transform:scaleX(-1);}' .
            '#' . $containerId . ' .teleprompter-track{position:relative;z-index:1;will-change:transform;}' .
            '#' . $containerId . ' .teleprompter-content{padding:1.5rem 2rem;}' .
            '#' . $containerId . ' .teleprompter-block{line-height:1.6;font-size:calc(' . $params['descriptionSize'] . ' * var(--teleprompter-font-scale));color:' . $params['descriptionColor'] . ';text-align:left;background:' . $params['slideContentBg'] . ';padding:0.75rem 0;margin:0;}' .
            '#' . $containerId . ' .teleprompter-block h1{font-size:calc(' . $params['titleSize'] . ' * var(--teleprompter-font-scale));color:' . $params['titleColor'] . ';margin:0 0 .5em;}' .
            '#' . $containerId . ' .teleprompter-media img{max-width:100%;height:auto;display:block;margin:0 auto 1rem;}' .
            '#' . $containerId . ' .teleprompter-focus-overlay{position:absolute;inset:0;pointer-events:none;z-index:2;}' .
            '#' . $containerId . ' .tp-focus-mask{position:absolute;left:0;right:0;display:block;background:rgba(0,0,0,.35);}' .
            '#' . $containerId . ' .tp-focus-mask-top{top:0;height:0;}' .
            '#' . $containerId . ' .tp-focus-mask-bottom{bottom:0;height:0;}' .
            '#' . $containerId . ' .tp-focus-guide{position:absolute;left:0;right:0;height:2px;background:rgba(255,255,255,.6);top:50%;display:block;}' .
            '#' . $containerId . '.teleprompter-focus-none .teleprompter-focus-overlay{display:none;}' .
            '#' . $containerId . ' .teleprompter-timer{position:absolute;right:12px;bottom:10px;z-index:3;padding:.25rem .55rem;border-radius:4px;background:rgba(0,0,0,.55);color:#fff;font-size:12px;font-family:monospace;line-height:1;font-variant-numeric:tabular-nums;}' .
            '#' . $containerId . ' .teleprompter-timer-hidden{display:none;}'
        );

        $headerlib->add_js(
            '$(function(){' .
            'var $container=$("#' . $containerId . '");' .
            'var $track=$("#' . $trackId . '");' .
            'var $first=$("#' . $contentId . '");' .
            'var $timer=$("#' . $timerId . '");' .
            'if(!$container.length||!$track.length||!$first.length){return;}' .
            'var speed=' . $teleprompterSpeed . ';' .
            'var acceleration=' . $teleprompterAcceleration . ';' .
            'var fontScale=' . $teleprompterFontScale . ';' .
            'var showTimer=' . $showTimer . ';' .
            'var focusMode=' . json_encode($focusMode) . ';' .
            'var pauseOnHover=' . $pauseOnHover . ';' .
            'var $focusOverlay=$container.find(".teleprompter-focus-overlay");' .
            'var $focusMaskTop=$container.find(".tp-focus-mask-top");' .
            'var $focusMaskBottom=$container.find(".tp-focus-mask-bottom");' .
            'var $focusGuide=$container.find(".tp-focus-guide");' .
            'var minSpeed=10;var maxSpeed=500;var speedStep=10;var minFontScale=70;var maxFontScale=300;var fontScaleStep=5;' .
            'var pausedByUser=false;var pausedByHover=false;var offset=0;var lastTs=null;var loopHeight=1;var lastSpeedInputTs=0;var elapsedMs=0;var lastTimerText="";' .
            'var isPaused=function(){return pausedByUser||pausedByHover;};' .
            'var formatElapsed=function(totalMs){var totalSeconds=Math.floor(totalMs/1000);var hours=Math.floor(totalSeconds/3600);var minutes=Math.floor((totalSeconds%3600)/60);var seconds=totalSeconds%60;var pad=function(value){return value<10?"0"+value:String(value);};if(hours>0){return pad(hours)+":"+pad(minutes)+":"+pad(seconds);}return pad(minutes)+":"+pad(seconds);};' .
            'var updateTimerDisplay=function(){if(!showTimer||!$timer.length){return;}var timerText=formatElapsed(elapsedMs);if(timerText!==lastTimerText){$timer.text(timerText);lastTimerText=timerText;}};' .
            'var refreshLoopHeight=function(){loopHeight=Math.max($first.outerHeight(true),1);};' .
            'var getSampleLineHeight=function(){var $sample=$first.find(".teleprompter-block").first();if(!$sample.length){$sample=$first;}var lineHeight=parseFloat($sample.css("line-height"));if(!isFinite(lineHeight)){var fontSize=parseFloat($sample.css("font-size"))||24;lineHeight=fontSize*1.4;}return Math.max(12,lineHeight);};' .
            'var refreshFocusOverlay=function(){if(focusMode==="none"||!$focusOverlay.length){return;}var containerHeight=$container.innerHeight();if(!containerHeight||containerHeight<=0){return;}var lineHeight=getSampleLineHeight();var focusHeight=Math.min(containerHeight*0.30,Math.max(lineHeight+8,lineHeight*1.35));var anchorRatio=0.5;if(focusMode==="top"){anchorRatio=0.22;}else if(focusMode==="bottom"){anchorRatio=0.78;}var start=(containerHeight*anchorRatio)-(focusHeight/2);var maxStart=Math.max(0,containerHeight-focusHeight);start=Math.max(0,Math.min(maxStart,start));var bottomHeight=Math.max(0,containerHeight-(start+focusHeight));$focusMaskTop.css("height",start+"px");$focusMaskBottom.css("height",bottomHeight+"px");$focusGuide.css("top",(start+(focusHeight/2))+"px");};' .
            'var refreshFontScale=function(){var scaleFactor=Math.max(minFontScale,Math.min(maxFontScale,fontScale))/100;$container.css("--teleprompter-font-scale",String(scaleFactor));refreshLoopHeight();refreshFocusOverlay();};' .
            'var applySpeedStep=function(direction,stepCount){stepCount=Math.max(1,stepCount||1);var now=Date.now();var elapsed=lastSpeedInputTs?now-lastSpeedInputTs:1000;lastSpeedInputTs=now;var accelerationFactor=1;if(elapsed<500){accelerationFactor=1+((500-elapsed)/500)*Math.max(0,acceleration-1);}var delta=direction*speedStep*stepCount*accelerationFactor;speed=Math.max(minSpeed,Math.min(maxSpeed,speed+delta));};' .
            'var applyFontScaleStep=function(direction,stepCount){stepCount=Math.max(1,stepCount||1);fontScale=Math.max(minFontScale,Math.min(maxFontScale,fontScale+(direction*fontScaleStep*stepCount)));refreshFontScale();};' .
            'var animate=function(ts){if(lastTs===null){lastTs=ts;}var dt=(ts-lastTs)/1000;lastTs=ts;if(!isPaused()){offset+=speed*dt;elapsedMs+=(dt*1000);if(offset>=loopHeight){offset=offset%loopHeight;}$track.css("transform","translate3d(0,-"+offset+"px,0)");}updateTimerDisplay();window.requestAnimationFrame(animate);};' .
            'refreshFontScale();refreshLoopHeight();refreshFocusOverlay();updateTimerDisplay();$(window).on("resize",function(){refreshLoopHeight();refreshFocusOverlay();});window.requestAnimationFrame(animate);' .
            'if(pauseOnHover){$container.on("mouseenter",function(){pausedByHover=true;}).on("mouseleave",function(){pausedByHover=false;});}' .
            '$container.attr("tabindex","0").on("keydown",function(e){var target=e.target;var tagName=target&&target.tagName?target.tagName.toLowerCase():"";if(tagName==="input"||tagName==="textarea"||(target&&target.isContentEditable)){return;}var key=e.key||"";var lowerKey=key.toLowerCase();if(key===" "||key==="Spacebar"){pausedByUser=!pausedByUser;e.preventDefault();return;}if(key==="Backspace"){elapsedMs=0;updateTimerDisplay();e.preventDefault();return;}if(key==="ArrowDown"||lowerKey==="s"){applySpeedStep(1,1);e.preventDefault();return;}if(key==="ArrowUp"||lowerKey==="w"){applySpeedStep(-1,1);e.preventDefault();return;}if(key==="ArrowRight"||lowerKey==="d"){applyFontScaleStep(1,1);e.preventDefault();return;}if(key==="ArrowLeft"||lowerKey==="a"){applyFontScaleStep(-1,1);e.preventDefault();}}).on("wheel",function(e){var originalEvent=e.originalEvent||e;if(!originalEvent||typeof originalEvent.deltaY==="undefined"||originalEvent.deltaY===0){return;}var direction=originalEvent.deltaY>0?1:-1;var stepCount=Math.max(1,Math.min(5,Math.round(Math.abs(originalEvent.deltaY)/100)));applySpeedStep(direction,stepCount);e.preventDefault();});' .
            '});'
        );

        return '<div id="' . $containerId . '" class="teleprompter-container' . $mirrorClass . $focusClass . '">' .
            '<div id="' . $trackId . '" class="teleprompter-track">' .
            '<div id="' . $contentId . '" class="teleprompter-content">' . $teleprompterHtml . '</div>' .
            '<div class="teleprompter-content" aria-hidden="true">' . $teleprompterHtml . '</div>' .
            '</div>' .
            '<div class="teleprompter-focus-overlay" aria-hidden="true"><div class="tp-focus-mask tp-focus-mask-top"></div><div class="tp-focus-mask tp-focus-mask-bottom"></div><div class="tp-focus-guide"></div></div>' .
            '<div id="' . $timerId . '" class="teleprompter-timer' . $timerClass . '" aria-live="polite">00:00</div>' .
            '</div>';
    }

    $swiper_js_path = SWIPER_DIST_PATH . '/swiper-bundle.min.js';
    $swiper_css_path = SWIPER_DIST_PATH . '/swiper-bundle.min.css';
    if (! file_exists($swiper_js_path)) {
        Feedback::error(tr('File %0 is missing, you need to execute npm run build', $swiper_js_path));
        return;
    }
    if (! file_exists($swiper_css_path)) {
        Feedback::error(tr('File %0 is missing, you need to execute npm run build', $swiper_css_path));
        return;
    }
    $headerlib->add_cssfile($swiper_css_path)
        ->add_jsfile($swiper_js_path);

    if ($params['autoPlay'] !== 'n') {
        $autoplayDelay = max(0, (int) $params['autoPlayDelay']) * 1000;
        $autoPlay = 'autoplay: {delay: ' . $autoplayDelay . ',disableOnInteraction: false},';
    } else {
        $autoPlay = '';
    }
    if ($params['pagination'] !== 'n' && $params['pagination'] !== '') {
        $pagination = 'pagination: {el: ".swiper-pagination",clickable: true,type:"' . $params['pagination'] . '"},';
        $paginationDiv = '<div class="swiper-pagination"></div>';
    } else {
        $pagination = '';
        $paginationDiv = '';
    }
    $fadeEffectCSS = $params['effect'] === 'fade' ? '#swiper-container' . $uid . ' .swiper-slide:not(.swiper-slide-active){opacity:0!important;}' : '';
    if ($params['navigation'] !== 'n') {
        $navigation = 'navigation: {nextEl: "#swiper' . $uid . '-button-next",prevEl: "#swiper' . $uid . '-button-prev"},';
        $navigationDiv = '<div id="swiper' . $uid . '-button-prev" class="swiper-button-prev"></div><div class="swiper-button-next" id="swiper' . $uid . '-button-next"></div>';
    } else {
        $navigation = '';
        $navigationDiv = '';
    }

    $swiperSettings = '';
    $swiperParams = ['effect', 'autoHeight', 'speed', 'spaceBetween', 'slidesPerView', 'slidesPerColumn', 'slidesPerColumnFill', 'centeredSlides', 'slidesOffsetBefore', 'slidesOffsetAfter', 'loop', 'preloadImages', 'slideToClickedSlide', 'freeMode', 'updateOnImagesReady'];
    foreach ($swiperParams as $swiperParam) {
        if (in_array($swiperParam, $swiperDigitsParams, true)) {
            $swiperSettings .= $swiperParam . ':' . ((int) ($params[$swiperParam] ?: 0)) . ',';
        } else {
            if ($params[$swiperParam] === 'y' || $params[$swiperParam] === 'n') {
                $swiperSettings .= $swiperParam . ':' . ($params[$swiperParam] === 'y' ? 'true' : 'false') . ',';
            } else {
                $swiperSettings .= $swiperParam . ':' . json_encode((string) $params[$swiperParam]) . ',';
            }
            if ($params[$swiperParam] === 'creative') {
                $swiperSettings .= 'creativeEffect: generateRandomCreativeEffect(),';
            }
        }
    }

    $breakpoints = '';
    if ($params['slidesPerViewMobile'] > 0 || $params['slidesPerViewTab'] > 0) {
        $slidesPerView = $params['slidesPerView'] === '' ? 1 : (int) $params['slidesPerView'];
        $slidesPerViewTab = $params['slidesPerViewTab'] === '' ? $slidesPerView : (int) $params['slidesPerViewTab'];
        $slidesPerViewMobile = $params['slidesPerViewMobile'] === '' ? $slidesPerViewTab : (int) $params['slidesPerViewMobile'];
        $breakpoints = 'breakpoints: {320:{slidesPerView:' . $slidesPerViewMobile . '},768:{slidesPerView:' . $slidesPerViewTab . '},1024:{slidesPerView:' . $slidesPerView . '}},';
    }

    $headerlib->add_css('#swiper-container' . $uid . '{position:relative;display:flex;align-items:center;overflow:hidden;' . $heightCSS . 'width:' . $params['width'] . ';background:' . $params['background'] . ';margin-bottom:20px;}#swiper-container' . $uid . ' .swiper-slide{font-size:' . $params['descriptionSize'] . ';color:' . $params['descriptionColor'] . ';text-align:center;width:100%;overflow:hidden;}.gallery-top{height:80%;width:100%;}.gallery-thumbs{height:20%;box-sizing:border-box;padding:10px 0;overflow:hidden;}.gallery-thumbs img{max-height:120px;height:120px;width:auto;margin-bottom:2%;cursor:pointer;}.gallery-thumbs .swiper-slide{width:25%;height:100%;opacity:.4;}.gallery-thumbs .swiper-slide-active{opacity:1;}#swiper-container' . $uid . ' .swiper-slide h1{font-size:' . $params['titleSize'] . ';color:' . $params['titleColor'] . ';}.slide-content' . $uid . '{min-width:60%;position:absolute;' . $params['slideContentPostion'] . ';background:' . $params['slideContentBg'] . ';padding:1%;text-align:left;}.parallax-bg{position:absolute;left:0;top:0;width:130%;height:100%;-webkit-background-size:cover;background-size:cover;background-position:center;}.swiper-slide img{max-width:100%;}' . $fadeEffectCSS);

    $thumbnails = '';
    $thumbclass = '';
    $swiperOpts = '';
    $thumbAfter = '';
    $thumbsSettings = '';
    if ($params['displayThumbnails'] === 'y') {
        $thumbnails = '<div id="gallery-thumbs' . $uid . '" class="swiper-container gallery-thumbs"><div class="swiper-wrapper">' . $slidesHtml . '</div></div>';
        $thumbclass = 'gallery-top';
        $swiperOpts = 'var galleryThumbs' . $uid . ' = new Swiper("#gallery-thumbs' . $uid . '", {spaceBetween: 10,
            slidesPerView: 3,
            loopedSlides:1,
            loop:true,
            watchSlidesVisibility: true,
            watchSlidesProgress: true,
            slideToClickedSlide: true
        });';
        $thumbsSettings = '';
        $thumbAfter = 'swiper' . $uid . '.controller.control=galleryThumbs' . $uid . ';galleryThumbs' . $uid . '.controller.control=swiper' . $uid . ';';
    }

    $creativeRandomEffect = 'function generateRandomCreativeEffect(){const creativeEffectList=[{prev:{translate:[0,0,-100],rotate:[0,0,0]},next:{translate:["80%","-100%",-1000],rotate:[0,0,0]}},{prev:{translate:["100%",0,-200],rotate:[0,0,90]},next:{translate:["-100%",0,-200],rotate:[0,0,-90]}},{prev:{translate:["100%","-200",-1000],rotate:[0,0,40],shadow:true},next:{translate:["-100%","200",-1000],rotate:[0,0,40],shadow:true}},{prev:{translate:["-100%","-200",-1000]},next:{translate:["100%","200",-1000]}},{prev:{translate:["-100%","-100",-300],rotate:[-45,-45,-45]},next:{translate:["100%","100",-300],rotate:[45,45,45]}}];return creativeEffectList[Math.floor(Math.random()*creativeEffectList.length)];}';
    $swiperOpts .= $creativeRandomEffect . 'var swiper' . $uid . ' = new Swiper("#swiper-container' . $uid . '",{'
        . $swiperSettings
        . 'init:false,'
        . $thumbsSettings
        . $pagination
        . 'keyboard:{enabled:true,onlyInViewport:false},'
        . $breakpoints
        . $autoPlay
        . $navigation
        . '});' . $thumbAfter;

    if ($heightMode === 'tallest') {
        $swiperOpts .= '
        var setTallestHeight' . $uid . ' = function() {
            var $swiper = $("#swiper-container' . $uid . '");
            var $images = $swiper.find(".swiper-slide img");
            if ($images.length === 0) {
                return;
            }
            var maxHeight = 0;
            var loadedCount = 0;
            var checkAllLoadedAndSetHeight = function() {
                loadedCount++;
                if (loadedCount >= $images.length) {
                    $images.each(function() {
                        maxHeight = Math.max(maxHeight, $(this).height() || 0);
                    });
                    if (maxHeight > 0) {
                        $swiper.css("height", maxHeight + "px");
                    }
                }
            };
            $images.each(function() {
                var $img = $(this);
                if (this.complete) {
                    checkAllLoadedAndSetHeight();
                } else {
                    $img.on("load", checkAllLoadedAndSetHeight)
                        .on("error", function() {
                            checkAllLoadedAndSetHeight();
                        });
                }
            });
        };
        setTallestHeight' . $uid . '();
        ';
    }

    if ($params['sliderPosition'] === 'abovetopbar') {
        $headerlib->add_css("#swiper-container" . $uid . "{visibility:hidden;}");
        $swiperOpts .= 'var container=$(".container").first();$("#swiper-container' . $uid . '").insertBefore( container );$("#gallery-thumbs' . $uid . '").insertAfter( "#swiper-container' . $uid . '" );';
    } elseif ($params['sliderPosition'] === 'undertopbar') {
        $headerlib->add_css("#swiper-container" . $uid . "{visibility:hidden;}");
        $swiperOpts .= '$("#swiper-container' . $uid . '").insertAfter("#page-header");$("#gallery-thumbs' . $uid . '").insertAfter("#swiper-container' . $uid . '");';
    }

    $swiperOpts .= 'swiper' . $uid . '.init();$("#swiper-container' . $uid . '").css("visibility","visible");' .
        'var updateSwiper' . $uid . ' = function(){swiper' . $uid . '.update();$("#swiper-container' . $uid . '").css("max-width",$("#swiper-container' . $uid . '").parent().width());};' .
        '$(window).on("resize", updateSwiper' . $uid . ');updateSwiper' . $uid . '();';

    $headerlib->add_js(
        '$(document).ready(function(){' . $swiperOpts . '})'
    );

    return '<div id="swiper-container' . $uid . '" class="swiper-container ' . $thumbclass . '"><div class="parallax-bg" style="background-image:url(' . $params['parallaxBgImg'] . ')" data-swiper-parallax="-23%"></div><div class="swiper-wrapper">' . $slidesHtml . '</div>' . $paginationDiv . $navigationDiv . '</div>' . $thumbnails;
}
