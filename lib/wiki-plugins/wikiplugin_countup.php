<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function getFontOptions()
{
    global $prefs;

    $names = explode(';', $prefs['wysiwyg_fonts']);
    $fonts = [];
    $fonts[] = ['text' => '', 'value' => ''];

    foreach ($names as $name) {
        $fonts[] = ['text' => $name, 'value' => $name];
    }
    return $fonts;
}

function wikiplugin_countup_info()
{
    $fontWeightOptions = [];
    $fontWeightOptions = [
        ['text' => '', 'value' => ''],
        ['text' => tra('normal'), 'value' => 'normal'],
        ['text' => tra('bold'), 'value' => 'bold'] ,
        ['text' => tra('lighter'), 'value' => 'lighter']
    ];
    for ($i = 100; $i <= 900; $i += 100) {
        $fontWeightOptions[] = ['text' => $i, 'value' => $i];
    }

    $fontStyleOptions = [
        ['text' => '', 'value' => ''],
        ['text' => tra('normal'), 'value' => 'normal'],
        ['text' => tra('italic'), 'value' => 'italic'] ,
        ['text' => tra('oblique'), 'value' => 'oblique']
    ];

    return [
        'name' => tra('Countup'),
        'documentation' => 'PluginCountup',
        'description' => tra('Make a counter on a wiki page'),
        'tags' => ['basic'],
        'prefs' => ['wikiplugin_countup'],
        'introduced' => 25,
        'params' => [
            'title' => [
                'required' => false,
                'name' => tra('Title'),
                'description' => tra('Counter title'),
                'since' => '25.0',
                'filter' => 'string',
                'default' => ''
            ],
            'titleFontFamily' => [
                'required' => false,
                'name' => tra('Title font family'),
                'description' => tra('Counter title font family'),
                'since' => '25.0',
                'filter' => 'string',
                'options' => getFontOptions(),
                'advanced' => true
            ],
            'titleFontWeight' => [
                'required' => false,
                'name' => tra('Title font weight'),
                'description' => tra('Counter title font weight.'),
                'since' => '25.0',
                'filter' => 'text',
                'options' => $fontWeightOptions,
                'default' => 'normal',
                'advanced' => true,
            ],
            'titleFontStyle' => [
                'required' => false,
                'name' => tra('Title font style'),
                'description' => tra('Counter title font style.'),
                'since' => '25.0',
                'filter' => 'text',
                'options' => $fontStyleOptions,
                'default' => 'normal',
                'advanced' => true,
            ],
            'titleFontSize' => [
                'required' => false,
                'name' => tra('Title font size'),
                'description' => tr('Counter title font size in pixels. For instance, use <code>16</code> for 16 pixels.'),
                'since' => '25.0',
                'filter' => 'float',
                'default' => 32,
                'advanced' => true
            ],
            'titleFontColor' => [
                'required' => false,
                'name' => tra('Title font color'),
                'description' => tr('Counter title font color. For instance, use <code>blue</code> for blue color.' .
                    'You can also use the <code>hexadecimal rgb</code> code or the <code>rgba</code> syntax to specify the color.'),
                'since' => '25.0',
                'filter' => 'alpha',
                'default' => 'black',
                'advanced' => true
            ],
            'icon' => [
                'required' => false,
                'name' => tra('Icon'),
                'description' => tra('Numeric ID of an icon in a file gallery. This will be set as the counter icon'),
                'since' => '25.0',
                'filter' => 'int',
                'default' => 0
            ],
            'iconWidth' => [
                'required' => false,
                'name' => tra('Icon width'),
                'description' => tr('Icon width size. The default value is <code>64</code>'),
                'since' => '25.0',
                'filter' => 'int',
                'default' => 64,
                'advanced' => true
            ],
            'iconHeight' => [
                'required' => false,
                'name' => tra('Icon height'),
                'description' => tr('Icon height size. The default value is <code>64</code>'),
                'since' => '25.0',
                'filter' => 'int',
                'default' => 64,
                'advanced' => true
            ],
            'description' => [
                'required' => false,
                'name' => tra('Description'),
                'description' => tra('Short description of the counter'),
                'since' => '25.0',
                'filter' => 'string',
                'default' => ''
            ],
            'descriptionFontFamily' => [
                'required' => false,
                'name' => tra('Description font family'),
                'description' => tra('Counter description font family.'),
                'since' => '25.0',
                'filter' => 'string',
                'options' => getFontOptions(),
                'advanced' => true
            ],
            'descriptionFontWeight' => [
                'required' => false,
                'name' => tra('Description font weight'),
                'description' => tra('Counter description font weight.'),
                'since' => '25.0',
                'filter' => 'text',
                'options' => $fontWeightOptions,
                'default' => 'normal',
                'advanced' => true,
            ],
            'descriptionFontStyle' => [
                'required' => false,
                'name' => tra('Description font style'),
                'description' => tra('Counter description font style.'),
                'since' => '25.0',
                'filter' => 'text',
                'options' => $fontStyleOptions,
                'default' => 'normal',
                'advanced' => true,
            ],
            'descriptionFontSize' => [
                'required' => false,
                'name' => tra('Description font size'),
                'description' => tr('Counter decription font size in pixels. For instance, use <code>16</code> for 16 pixels.'),
                'since' => '25.0',
                'filter' => 'float',
                'default' => '12',
                'advanced' => true
            ],
            'descriptionFontColor' => [
                'required' => false,
                'name' => tra('Description font color'),
                'description' => tr('Counter description font color. For instnace, use <code>blue</code> for blue color. ' .
                    'You can also use the <code>hexadecimal rgb</code> code or the <code>rgba</code> syntax to specify the color.'),
                'since' => '25.0',
                'filter' => 'alpha',
                'default' => 'black',
                'advanced' => true
            ],
            'startingNumber' => [
                'required' => false,
                'name' => tra('Starting number'),
                'description' => tra('The number from which the counter will start to count.'),
                'since' => '25.0',
                'filter' => 'int',
                'default' => 0
            ],
            'endingNumber' => [
                'required' => true,
                'name' => tra('Ending number'),
                'description' => tra('The number to which the counter will stop to count.'),
                'since' => '25.0',
                'filter' => 'int'
            ],
            'numberFontFamily' => [
                'required' => false,
                'name' => tra('Number font family'),
                'description' => tra('This is the font family of the displayed number in the counter at a certain time.'),
                'since' => '25.0',
                'filter' => 'string',
                'options' => getFontOptions(),
                'advanced' => true
            ],
            'numberFontWeight' => [
                'required' => false,
                'name' => tra('Number font weight'),
                'description' => tra('Displayed number font weight.'),
                'since' => '25.0',
                'filter' => 'text',
                'options' => $fontWeightOptions,
                'default' => 'normal',
                'advanced' => true,
            ],
            'numberFontStyle' => [
                'required' => false,
                'name' => tra('Number font style'),
                'description' => tra('Displayed number font style.'),
                'since' => '25.0',
                'filter' => 'text',
                'options' => $fontStyleOptions,
                'default' => 'normal',
                'advanced' => true,
            ],
            'numberFontSize' => [
                'required' => false,
                'name' => tra('Number font size'),
                'description' => tr('This is the font size of the displayed number at a certain time in pixels. For instance, use <code>16</code> for 16 pixels.'),
                'since' => '25.0',
                'filter' => 'float',
                'default' => 32,
                'advanced' => true
            ],
            'numberFontColor' => [
                'required' => false,
                'name' => tra('Number font color'),
                'description' => tr('This is the font color of the displayed number of counter at a certain time. For instance, use <code>blue</code> for blue color.' .
                    'You can also use the <code>hexadecimal rgb</code> code or the <code>rgba</code> syntax to specify the color.'),
                'since' => '25.0',
                'filter' => 'alpha',
                'default' => 'black',
                'advanced' => true
            ],
            'speed' => [
                'required' => false,
                'name' => tra('Speed'),
                'description' => tra('The counting(animation) speed of the counter in seconds.'),
                'since' => '25.0',
                'filter' => 'float',
                'default' => 0.2
            ],
            'delay' => [
                'required' => false,
                'name' => tra('Delay'),
                'description' => tra('The delay time in seconds after which the counter will start to count when it becomes visible in the viewport.'),
                'since' => '25.0',
                'filter' => 'float',
                'default' => 1.0
            ],
            'prefix' => [
                'required' => false,
                'name' => tra('Prefix'),
                'description' => tra('Letter, number or symbol to use as prefix of the number counter (the number that is displayed in the counter at certain time).'),
                'since' => '25.0',
                'filter' => 'text',
                'default' => ''
            ],
            'suffix' => [
                'required' => false,
                'name' => tra('Suffix'),
                'description' => tra('Letter, number or symbol to use as suffix of the number counter (the number that is displayed in the counter at certain time).'),
                'since' => '25.0',
                'filter' => 'text',
                'default' => ''
            ],
            'width' => [
                'required' => false,
                'name' => tra('Width'),
                'description' => tr('Counter width in pixels. For instance, use <code>200</code> for 200 pixels'),
                'since' => '25.0',
                'filter' => 'int',
                'default' => 400
            ],
            'height' => [
                'required' => false,
                'name' => tra('Height'),
                'description' => tr('Counter height in pixels. For instance, use <code>200</code> for 200 pixels'),
                'since' => '25.0',
                'filter' => 'int',
                'default' => 300
            ],
            'backgroundImage' => [
                'required' => false,
                'name' => tra('Background image'),
                'description' => tra('Numeric ID of an icon in a file gallery. This will be set as the counter background image'),
                'since' => '25.0',
                'filter' => 'int',
                'default' => 0,
                'advanced' => true
            ],
            'backgroundColor' => [
                'required' => false,
                'name' => tra('Background color'),
                'description' => tra('Counter background color' .
                    'You can use the color name, the <code>hexadecimal rgb</code> code or the <code>rgba</code> syntax to specify the color.'),
                'since' => '25.0',
                'filter' => 'alhpa',
                'default' => 'white',
                'advanced' => true
            ],
            'shadow' => [
                'required' => false,
                'name' => tra('Shadow'),
                'description' => tr('This property adds shadows to the counter via a comma separated list of shadows. ' .
                    'A shadow is defined with horizontal and vertical offsets from the element (in our case, the counter),' .
                    'with blur and spread radius and with a color. For instance, use <code>inset 2000px 0px 0px 0px #24a2c09c</code>' .
                    'for 2000px horizontal offset, 0px vertical offset, 0px blur radius, 0px spread radius and with a light blue color. ' .
                    'The inset keyword reverses the shadow inside. For the color value, you can use the color name, the hexadecimal rgb code ' .
                    'or the rgba syntax. For more details See <a href="https://developer.mozilla.org/en/docs/Web/CSS/box-shadow">here</a>. ' .
                    'Please be sure to respect the syntax given in the documentation to see the effect.'),
                'since' => '25.0',
                'filter' => 'string',
                'default' => '',
                'advanced' => true
            ]
        ]
    ];
}

function wikiplugin_countup($data, $params)
{
    TikiLib::lib('header')->add_js_module(
        'import anime from "animejs";'
    );

    $smarty = TikiLib::lib('smarty');
    $pluginInfos = $params;
    extract($params, EXTR_SKIP);

    $counterId = uniqid(); // Counter identifier to distinguish counters as we can add multiple counters on a wiki page
    $mainContainerStyle = (isset($height) ? "height: $height" . "px; " : "height: 300px; ")
        . (isset($width) ? "width: $width" . "px; " : "width: 400px; ")
        . (isset($backgroundColor) ? "background-color: $backgroundColor; " : "")
        . (isset($backgroundImage) ? "background-image: url('tiki-download_file.php?fileId=$backgroundImage'); background-repeat: no-repeat; background-position: center; background-size: cover; " : "")
        . (isset($shadow) ? "box-shadow: $shadow; " : ""
    );
    $titleStyle = (isset($titleFontFamily) ? "font-family : $titleFontFamily; " : "")
        . (isset($titleFontWeight) ? "font-weight : $titleFontWeight; " : "")
        . (isset($titleFontStyle) ? "font-style : $titleFontStyle; " : "")
        . (isset($titleFontSize) ? "font-size: $titleFontSize" . "px; " : "")
        . (isset($titleFontColor) ? "color: $titleFontColor;" : ""
    );
    $descriptionStyle = (isset($descriptionFontFamily) ? "font-family : $descriptionFontFamily; " : "")
        . (isset($descriptionFontWeight) ? "font-weight : $descriptionFontWeight; " : "")
        . (isset($descriptionFontStyle) ? "font-style : $descriptionFontStyle; " : "")
        . (isset($descriptionFontSize) ? "font-size: $descriptionFontSize" . "px; " : "")
        . (isset($descriptionFontColor) ? "color: $descriptionFontColor;" : ""
    );
    $numberStyle = (isset($numberFontFamily) ? "font-family : $numberFontFamily; " : "")
        . (isset($numberFontWeight) ? "font-weight : $numberFontWeight; " : "")
        . (isset($numberFontStyle) ? "font-style : $titleFontStyle; " : "")
        . (isset($numberFontSize) ? "font-size: $numberFontSize" . "px; " : "")
        . (isset($numberFontColor) ? "color: $numberFontColor;" : ""
    );

    if (! isset($speed)) {
        $speed = 0.2;
    }
    if (! isset($delay)) {
        $delay = 1;
    }
    $cleanedTitle = preg_replace('/[^A-Za-z0-9_]/', '', $title); // Remove white spaces and some specials chars to prevent bug when calling the update function and  getting the counter DOM elemnt by id.
    $js = '
        //Number counter update function
        function updateCounter' . $cleanedTitle . $counterId . '(){
            const counter = document.querySelector("#' . $cleanedTitle . 'Count_' . $counterId . '");
            const delay = ' . $delay * 1000 . '; // Converting delay in milliseconds
            const duration = ' . $speed * $endingNumber * 1000 . '; // As anime.js doesn\'t dispose a speed property, we use duration to adjust the speed of counting(animation). The duration is in milliseconds.

            // Call to animejs to animate the counter
            anime({
                targets: counter,
                textContent: ' . $endingNumber . ',
                round: 1,
                easing: "linear",
                delay: delay,
                duration: duration
            });
        }
    
        //Calling number counter function when the counter becomes visible in the Viewport
        const ' . $cleanedTitle . 'CounterObserver_' . $counterId . ' = new IntersectionObserver(function(entries) {
            entries.forEach((entry) => {
                if(entry.isIntersecting === true) {
                    if(entry.intersectionRatio === 1 || entry.intersectionRatio > 0.5) {
                        updateCounter' . $cleanedTitle . $counterId . '();
                    }
                }
            });
        }, {
            threshold: [0, 0.5, 1]
        });
    
        ' . $cleanedTitle . 'CounterObserver_' . $counterId . '.observe(document.querySelector("#' . $cleanedTitle . 'Count_' . $counterId . '"));
    ';

    $smarty->assign('pluginInfos', $pluginInfos);
    $smarty->assign('counterId', $counterId);
    $smarty->assign('cleanedTitle', $cleanedTitle);

    $smarty->assign('mainContainerStyle', $mainContainerStyle);
    $smarty->assign('titleStyle', $titleStyle);
    $smarty->assign('descriptionStyle', $descriptionStyle);
    $smarty->assign('numberStyle', $numberStyle);

    TikiLib::lib('header')->add_js_module($js);

    return $smarty->fetch('wiki-plugins/wikiplugin_countup.tpl');
}
