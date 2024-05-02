<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_tour_info()
{
    return [
        'name' => tra('Tour'),
        'documentation' => 'PluginTour',
        'description' => tra('Quick and easy way to build your product tours with Driver.js'),
        'prefs' => [ 'wikiplugin_tour' ],
        'iconname' => 'information',
        'introduced' => 15,
        'body' => tra('Content of the step'),
        'format' => 'html',
        'params' => [
            'element' => [
                'name' => tra('Element'),
                'required' => false,
                'description' => tra('Element to show the popup on; if empty, use the plugin location itself'),
                'since' => '15.0',
                'filter' => 'text',
                'default' => '',
            ],
            'title' => [
                'name' => tra('Title'),
                'required' => true,
                'description' => tra('Title of the step'),
                'since' => '15.0',
                'filter' => 'text',
                'default' => '',
            ],
            'start' => [
                'name' => tra('Start'),
                'required' => false,
                'description' => tra('Start the tour on page load? If "No", then a start button can be made with "Restart Button", below. (Set only in the first step.)'),
                'since' => '15.0',
                'filter' => 'alpha',
                'default' => 'y',
                'options' => [
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'show_once' => [
                'name' => tra('Only Show Once'),
                'required' => false,
                'description' => tra('Show automatically only once. tour_id should also be set if there are multiple tours. (Set only in the first step.)'),
                'since' => '15.0',
                'filter' => 'alpha',
                'default' => 'n',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'tour_id' => [
                'name' => tra('Tour ID'),
                'required' => false,
                'description' => tra('Set a tour ID to be able to only show the tour once. (Set only in the first step.)'),
                'since' => '15.0',
                'filter' => 'text',
                'default' => 'default',
            ],
            'show_restart_button' => [
                'name' => tra('Restart Button'),
                'required' => false,
                'description' => tra('Display a button to restart the tour. Enter the text to appear on the button. (Set only in the first step.)'),
                'since' => '15.0',
                'filter' => 'text',
                'default' => '',
            ],
            'overlay_color' => [
                'name' => tra('Overlay Color'),
                'required' => false,
                'description' => tra('Set an overlay color to be shown behind the popover and its element, highlighting the current step. (Set only in the first step.)'),
                'since' => '27.0',
                'filter' => 'text',
                'default' => 'transparent',
            ],
            'overlay_opacity' => [
                'name' => tra('Overlay Opacity'),
                'required' => false,
                'description' => tra('Set the opacity of the overlay. (Set only in the first step.)'),
                'since' => '27.0',
                'filter' => 'int',
                'default' => '0.5',
            ],
            'allow_close' => [
                'name' => tra('allow_close'),
                'required' => false,
                'description' => tra('Allow closing the popover by clicking on the backdrop. (Set only in the first step.)'),
                'since' => '16.0',
                'filter' => 'alpha',
                'default' => 'y',
                'options' => [
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'side' => [
                'name' => tra('Side'),
                'required' => false,
                'description' => tra('The side of the popup. (Set only in the first step.)'),
                'since' => '15.0',
                'filter' => 'alpha',
                'default' => 'right',
                'options' => [
                    ['text' => tra('Right'), 'value' => 'right'],
                    ['text' => tra('Top'), 'value' => 'top'],
                    ['text' => tra('Bottom'), 'value' => 'bottom'],
                    ['text' => tra('Left'), 'value' => 'left'],
                ],
            ],
            'align' => [
                'name' => tra('Align'),
                'required' => false,
                'description' => tra('The alignment of the popup. (Set only in the first step.)'),
                'since' => '15.0',
                'filter' => 'alpha',
                'default' => 'end',
                'options' => [
                    ['text' => tra('End'), 'value' => 'end'],
                    ['text' => tra('Start'), 'value' => 'start'],
                    ['text' => tra('Center'), 'value' => 'center'],
                ],
            ],
            'number_of_steps' => [
                'name' => tra('Number of Steps'),
                'required' => false,
                'description' => tra('Number of steps in the tour. (Set only in the first step.)'),
                'since' => '27.0',
                'filter' => 'int',
                'default' => '',
            ],
            'animate' => [
                'name' => tra('Animate'),
                'required' => false,
                'description' => tra('Animate the popup. (Set only in the first step.)'),
                'since' => '27.0',
                'filter' => 'alpha',
                'default' => 'y',
                'options' => [
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'next_button_text' => [
                'name' => tra('Next Button Text'),
                'required' => false,
                'description' => tra('Text to show on the Next button. (Set only in the first step.)'),
                'since' => '27.0',
                'filter' => 'text',
                'default' => 'Next',
            ],
            'prev_button_text' => [
                'name' => tra('Prev Button Text'),
                'required' => false,
                'description' => tra('Text to show on the Prev button. (Set only in the first step.)'),
                'since' => '27.0',
                'filter' => 'text',
                'default' => 'Previous',
            ],
            'show_progress' => [
                'name' => tra('Show Progress'),
                'required' => false,
                'description' => tra('Show the progress text in popover. (Set only in the first step.)'),
                'since' => '27.0',
                'filter' => 'alpha',
                'default' => 'n',
                'options' => [
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'progress_text' => [
                'name' => tra('Progress Text'),
                'required' => false,
                'description' => tra('Template for the progress text. You can use the following placeholders in the template:
                    //  - {{current}}: The current step number.
                    //  - {{total}}: Total number of steps.
                    Example: Step {{current}} of {{total}}.
                    (Set only in the first step.)'),
                'since' => '27.0',
                'filter' => 'text',
                'default' => '{{current}} of {{total}}',
            ],
        ],
    ];
}

function wikiplugin_tour($data, $params)
{
    if (! session_id()) {
        session_start();
    }

    $_SESSION['id'] = ($_SESSION['id'] ?? 0) + 1;

    $defaults = [];
    $plugininfo = wikiplugin_tour_info();
    foreach ($plugininfo['params'] as $key => $param) {
        $defaults["$key"] = $param['default'];
    }
    $params = array_merge($defaults, $params);

    $cookie_id = 'tour' . md5($params['tour_id']);
    $cookie_expiry = time() + 31536000;
    if (getCookie($cookie_id, 'tours') == 'y') {
        $dontStart = true;
    } else {
        $dontStart = false;

        if ($params['show_once'] === 'y') {
            setCookieSection($cookie_id, 'y', 'tours', $cookie_expiry);
        }
    }

    if (! isset($wp_tour['start'])) {
        $wp_tour['start'] = $params['start'];
    }

    // tour constants (change across multiple tours)
    $TOUR_STEPS = "tour_steps_{$params['tour_id']}";
    $TOUR_NUMBER_OF_STEPS = "number_of_steps_{$params['tour_id']}";

    $step = array_filter($params);
    $content = TikiLib::lib('parser')->parse_data($data);
    $step['content'] = $content;
    $_SESSION[$TOUR_STEPS] = array_merge($_SESSION[$TOUR_STEPS] ?? [], [$step]);

    // first step
    if ($params['number_of_steps']) {
        $_SESSION[$TOUR_NUMBER_OF_STEPS] = $params['number_of_steps'];
        $unique = 'wptour_' . $_SESSION['id'];
        $_SESSION['startButtonId'] = $unique . '_restart';
    }

    $html = '';

    if ($_SESSION[$TOUR_NUMBER_OF_STEPS] === count($_SESSION[$TOUR_STEPS])) {
        $jsSteps = "";
        foreach ($_SESSION[$TOUR_STEPS] as $step) {
            $jsSteps .= "{element: '$step[element]', popover: {title: \"$step[title]\", description: \"$step[content]\", align: '$step[align]', side: '$step[side]'}},";
        }

        $first_step = $_SESSION[$TOUR_STEPS][0];

        // Avoid undefined array key warning
        $getParam = function ($name) use ($first_step) {
            return isset($first_step[$name]) ? $first_step[$name] : '';
        };

        $overlayColor = $getParam('overlay_color');
        $animate = $getParam('animate') === 'y' ? 'true' : 'false';
        $allowClose = $getParam('allow_close') === 'y' ? 'true' : 'false';
        $smoothScroll = $getParam('smooth_scroll') === 'y' ? 'true' : 'false';
        $nextBtnText = $getParam('next_button_text');
        $prevBtnText = $getParam('prev_button_text');
        $doneBtnText = $getParam('done_button_text');
        $overlayOpacity = $getParam('overlay_opacity');
        $showProgress = $getParam('show_progress') === 'y' ? 'true' : 'false';
        $progressText = $getParam('progress_text');

        $startButtonId = $_SESSION['startButtonId'];

        // Show the restart button
        if (! empty($first_step['show_restart_button'])) {
            $smarty = TikiLib::lib('smarty');
            $html .= smarty_function_button([
                    '_text' => tra($first_step['show_restart_button']),
                    '_id' => $startButtonId,
                    'href' => '#',
                ], $smarty->getEmptyInternalTemplate());
        }

        // Make sure variables names are unique, otherwise it will break if there are multiple tours on the same page
        $driver_var = 'driver' . $_SESSION['id'];
        $startButton_var = 'startButton' . $_SESSION['id'];
        $js = "
        const $driver_var = () => {
            window.driver({
                overlayColor: '$overlayColor',
                overlayOpacity: $overlayOpacity,
                animate: Boolean($animate),
                allowClose: Boolean($allowClose),
                smoothScroll: Boolean($smoothScroll),
                nextBtnText: '$nextBtnText',
                prevBtnText: '$prevBtnText',
                doneBtnText: '$doneBtnText',
                showProgress: Boolean($showProgress),
                progressText: '$progressText',
                steps: [$jsSteps]
            }).drive();
        };

        const $startButton_var = document.getElementById('$startButtonId');
        if ($startButton_var) {
            $startButton_var.addEventListener('click', () => {
                $driver_var();
            });
        }
        ";

        if ($first_step['start'] === 'y' && ! $dontStart) {
            $js .= "
            $driver_var();
            ";
        }

        $headerlib = TikiLib::lib('header');
        $headerlib->add_js_module("
        import { driver } from 'driver.js';
        window.driver = driver;
        ")
        ->add_cssfile(NODE_PUBLIC_DIST_PATH . '/driver.js/dist/driver.css');
        $headerlib->add_jq_onready($js, 12);

        // Start a new tour
        $_SESSION[$TOUR_STEPS] = [];
        $_SESSION[$TOUR_NUMBER_OF_STEPS] = null;
    }

    return $html;
}
