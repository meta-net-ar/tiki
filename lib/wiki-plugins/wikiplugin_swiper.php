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
                'description' => tr('Scroll speed in pixels per second when teleprompter is enabled.'),
                'filter' => 'digits',
                'default' => 60,
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
        'teleprompterMirror' => 'n',
        'teleprompterPauseOnHover' => 'y',
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
        $teleprompterSpeed = max(10, (int) $params['teleprompterSpeed']);
        $pauseOnHover = $params['teleprompterPauseOnHover'] === 'y' ? 'true' : 'false';
        $mirrorClass = $params['teleprompterMirror'] === 'y' ? ' teleprompter-mirror' : '';
        $teleprompterHtml = implode('', $teleprompterBlocks);

        $headerlib->add_css(
            '#' . $containerId . '{position:relative;overflow:hidden;' . $heightCSS . 'width:' . $params['width'] . ';background:' . $params['background'] . ';margin-bottom:20px;}' .
            '#' . $containerId . '.teleprompter-mirror{transform:scaleX(-1);}' .
            '#' . $containerId . ' .teleprompter-track{will-change:transform;}' .
            '#' . $containerId . ' .teleprompter-content{padding:1.5rem 2rem;}' .
            '#' . $containerId . ' .teleprompter-block{line-height:1.6;font-size:' . $params['descriptionSize'] . ';color:' . $params['descriptionColor'] . ';text-align:left;background:' . $params['slideContentBg'] . ';padding:0.75rem 0;margin:0;}' .
            '#' . $containerId . ' .teleprompter-block h1{font-size:' . $params['titleSize'] . ';color:' . $params['titleColor'] . ';margin:0 0 .5em;}' .
            '#' . $containerId . ' .teleprompter-media img{max-width:100%;height:auto;display:block;margin:0 auto 1rem;}'
        );

        $headerlib->add_js(
            '$(function(){' .
            'var $container=$("#' . $containerId . '");' .
            'var $track=$("#' . $trackId . '");' .
            'var $first=$("#' . $contentId . '");' .
            'if(!$container.length||!$track.length||!$first.length){return;}' .
            'var speed=' . $teleprompterSpeed . ';' .
            'var pauseOnHover=' . $pauseOnHover . ';' .
            'var paused=false;var offset=0;var lastTs=null;var loopHeight=1;' .
            'var refreshLoopHeight=function(){loopHeight=Math.max($first.outerHeight(true),1);};' .
            'var animate=function(ts){if(lastTs===null){lastTs=ts;}var dt=(ts-lastTs)/1000;lastTs=ts;if(!paused){offset+=speed*dt;if(offset>=loopHeight){offset=offset%loopHeight;}$track.css("transform","translate3d(0,-"+offset+"px,0)");}window.requestAnimationFrame(animate);};' .
            'refreshLoopHeight();$(window).on("resize",refreshLoopHeight);window.requestAnimationFrame(animate);' .
            'if(pauseOnHover){$container.on("mouseenter",function(){paused=true;}).on("mouseleave",function(){paused=false;});}' .
            '$container.attr("tabindex","0").on("keydown",function(e){if(e.key===" "){paused=!paused;e.preventDefault();}if(e.key==="ArrowUp"){speed=Math.min(speed+10,500);e.preventDefault();}if(e.key==="ArrowDown"){speed=Math.max(speed-10,10);e.preventDefault();}});' .
            '});'
        );

        return '<div id="' . $containerId . '" class="teleprompter-container' . $mirrorClass . '">' .
            '<div id="' . $trackId . '" class="teleprompter-track">' .
            '<div id="' . $contentId . '" class="teleprompter-content">' . $teleprompterHtml . '</div>' .
            '<div class="teleprompter-content" aria-hidden="true">' . $teleprompterHtml . '</div>' .
            '</div></div>';
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
