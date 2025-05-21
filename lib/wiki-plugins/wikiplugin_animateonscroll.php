<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_animateonscroll_info()
{
    return [
        'name' => tra('Animate on Scroll'),
        'documentation' => 'PluginAnimateOnScroll',
        'description' => tra('Perform scroll-based animations (like fade or zoom) when elements enter the viewport. Powered by anime.js.'),
        'prefs' => ['wikiplugin_animateonscroll'],
        'introduced' => 29,
        'tags' => ['basic', 'animation', 'scroll'],
        'params' => [
            'animation' => [
                'required' => false,
                'name' => tra('Animation'),
                'description' => tra('Type of animation. Supported values: fade-up, fade-down, fade-left, fade-right, fade-up-right, fade-up-left, fade-down-right, fade-down-left, flip-left, flip-right, flip-up, flip-down, zoom-in, zoom-out, zoom-in-up, zoom-in-down, zoom-in-left, zoom-in-right, zoom-out-up, zoom-out-down, zoom-out-left, zoom-out-right.'),
                'since' => '29.0',
                'default' => 'fade-up',
                'filter' => 'text',
            ],
            'duration' => [
                'required' => false,
                'name' => tra('Duration (ms)'),
                'description' => tra('Length of the animation, in milliseconds.'),
                'since' => '29.0',
                'default' => 800,
                'filter' => 'int',
            ],
            'delay' => [
                'required' => false,
                'name' => tra('Delay (ms)'),
                'description' => tra('Delay before animation starts, in milliseconds.'),
                'since' => '29.0',
                'default' => 0,
                'filter' => 'int',
            ],
            'easing' => [
                'required' => false,
                'name' => tra('Easing'),
                'description' => tra('Anime.js easing function (default: easeOutQuad).'),
                'since' => '29.0',
                'default' => 'easeOutQuad',
                'filter' => 'text',
            ],
            'once' => [
                'required' => false,
                'name' => tra('Animate only once'),
                'description' => tra('If y, animation runs only the first time element enters viewport.'),
                'since' => '29.0',
                'default' => 'y',
                'filter' => 'alpha',
                'accepted' => 'y or n',
            ],
            'threshold' => [
                'required' => false,
                'name' => tra('Threshold'),
                'description' => tra('Value 0–1. Proportion of the element that must appear before animating.'),
                'since' => '29.0',
                'default' => 0.2,
                'filter' => 'float',
            ],
            'offset' => [
                'required' => false,
                'name' => tra('Offset (px)'),
                'description' => tra('Distance in px before the element triggers. Implemented via rootMargin in IntersectionObserver. E.g., 100 means trigger 100px before the element is actually in the viewport.'),
                'since' => '29.0',
                'default' => 0,
                'filter' => 'int',
            ],
            'anchorPlacement' => [
                'required' => false,
                'name' => tra('Anchor placement'),
                'description' => tra('Similar to AOS anchorPlacement. Determines how to position the element relative to the viewport: "top-bottom", "center-center", "top-center", etc.'),
                'since' => '29.0',
                'default' => 'top-bottom',
                'filter' => 'text',
            ],
            'mirror' => [
                'required' => false,
                'name' => tra('Mirror'),
                'description' => tra('If set to y, animate out (reverse) when scrolling back up.'),
                'since' => '29.0',
                'default' => 'n',
                'filter' => 'alpha',
                'accepted' => 'y or n',
            ],
        ],
    ];
}

function wikiplugin_animateonscroll($data, $params)
{
    // WARNING: This conditional import is a workaround due to a known limitation in Tiki.
    // This is not the recommended way to structure JavaScript modules.
    static $animeModuleDeclared = false;
    $animeImportCode = '';
    if (! $animeModuleDeclared) {
        $animeImportCode = 'import anime from "animejs";';
        $animeModuleDeclared = true;
    }

    extract($params, EXTR_SKIP);

    // Set defaults if not provided
    $animation        = isset($animation) ? $animation : 'fade-up';
    $duration         = isset($duration) ? (int) $duration : 800;
    $delay            = isset($delay) ? (int) $delay : 0;
    $easing           = isset($easing) ? $easing : 'easeOutQuad';
    $once             = isset($once) ? $once : 'y';
    $threshold        = isset($threshold) ? floatval($threshold) : 0.2;
    $offset           = isset($offset) ? (int) $offset : 0;
    $anchorPlacement  = isset($anchorPlacement) ? $anchorPlacement : 'top-bottom';
    $mirror           = isset($mirror) ? $mirror : 'n';


    // Preset animations
    $presets = [
        // Basic fade directions
        'fade-up' => [
            'translateY' => [50, 0],
            'opacity' => [0, 1],
        ],
        'fade-down' => [
            'translateY' => [-50, 0],
            'opacity' => [0, 1],
        ],
        'fade-left' => [
            'translateX' => [-50, 0],
            'opacity' => [0, 1],
        ],
        'fade-right' => [
            'translateX' => [50, 0],
            'opacity' => [0, 1],
        ],

        // Fade in diagonal directions
        'fade-up-right' => [
            'translateX' => [50, 0],
            'translateY' => [50, 0],
            'opacity' => [0, 1],
        ],
        'fade-up-left' => [
            'translateX' => [-50, 0],
            'translateY' => [50, 0],
            'opacity' => [0, 1],
        ],
        'fade-down-right' => [
            'translateX' => [50, 0],
            'translateY' => [-50, 0],
            'opacity' => [0, 1],
        ],
        'fade-down-left' => [
            'translateX' => [-50, 0],
            'translateY' => [-50, 0],
            'opacity' => [0, 1],
        ],

        // Flip animations
        'flip-left' => [
            'rotateY' => [90, 0],
            'opacity' => [0, 1],
        ],
        'flip-right' => [
            'rotateY' => [-90, 0],
            'opacity' => [0, 1],
        ],
        'flip-up' => [
            'rotateX' => [90, 0],
            'opacity' => [0, 1],
        ],
        'flip-down' => [
            'rotateX' => [-90, 0],
            'opacity' => [0, 1],
        ],

        // Zoom basic
        'zoom-in' => [
            'scale' => [0.8, 1],
            'opacity' => [0, 1],
        ],
        'zoom-out' => [
            'scale' => [1.2, 1],
            'opacity' => [0, 1],
        ],

        // Zoom in with direction
        'zoom-in-up' => [
            'scale' => [0.8, 1],
            'translateY' => [50, 0],
            'opacity' => [0, 1],
        ],
        'zoom-in-down' => [
            'scale' => [0.8, 1],
            'translateY' => [-50, 0],
            'opacity' => [0, 1],
        ],
        'zoom-in-left' => [
            'scale' => [0.8, 1],
            'translateX' => [-50, 0],
            'opacity' => [0, 1],
        ],
        'zoom-in-right' => [
            'scale' => [0.8, 1],
            'translateX' => [50, 0],
            'opacity' => [0, 1],
        ],

        // Zoom out with direction
        'zoom-out-up' => [
            'scale' => [1.2, 1],
            'translateY' => [50, 0],
            'opacity' => [0, 1],
        ],
        'zoom-out-down' => [
            'scale' => [1.2, 1],
            'translateY' => [-50, 0],
            'opacity' => [0, 1],
        ],
        'zoom-out-left' => [
            'scale' => [1.2, 1],
            'translateX' => [-50, 0],
            'opacity' => [0, 1],
        ],
        'zoom-out-right' => [
            'scale' => [1.2, 1],
            'translateX' => [50, 0],
            'opacity' => [0, 1],
        ],
    ];


    $animationPreset = isset($presets[$animation]) ? $presets[$animation] : $presets['fade-up'];
    $animeProps = array_merge($animationPreset, [
        'duration' => $duration,
        'delay' => $delay,
        'easing' => $easing,
    ]);
    $animePropsJson = json_encode($animeProps);

    // 1) Convert anchorPlacement to a rootMargin (approximation).
    //    anchorPlacement often means, for example, "top-bottom" =>
    //    "trigger the animation when the top of the element hits the bottom of the viewport."
    //    We'll interpret it loosely via rootMargin. If offset is set, we also incorporate it.
    //
    //    Examples:
    //    - top-bottom => rootMargin: "0px 0px -offset px 0px"
    //    - center-center => rootMargin: "-50% 0px -50% 0px" (plus offset)
    $rootMargin = '';
    switch ($anchorPlacement) {
        case 'top-bottom':
            $rootMargin = "0px 0px -{$offset}px 0px";
            break;

        case 'top-center':
            $rootMargin = "0px 0px calc(-50% - {$offset}px) 0px";
            break;

        case 'center-center':
            $rootMargin = "calc(-50% - {$offset}px) 0px calc(-50% - {$offset}px) 0px";
            break;

        case 'center-bottom':
            $rootMargin = "calc(-50%) 0px -{$offset}px 0px";
            break;

        case 'bottom-bottom':
            $rootMargin = "0px 0px -{$offset}px 0px";
            break;

        case 'bottom-center':
            $rootMargin = "calc(-50%) 0px 0px 0px";
            break;

        default:
            // Fallback to "top-bottom"
            $rootMargin = "0px 0px -{$offset}px 0px";
            break;
    }


    // 2) Unique ID for the container
    $uniqueId = uniqid('animateonscroll_');

    // 3) IntersectionObserver script
    // If mirror='y', we attempt to do a reversed animation on exit.
    $observerJs = $animeImportCode . '
        (function(){
            var element = document.getElementById("' . $uniqueId . '");
            if(!element) return;
            var animeProps = ' . $animePropsJson . ';

            var options = {
                rootMargin: "' . $rootMargin . '",
                threshold: ' . $threshold . '
            };

            var hasAnimatedIn = false;
            var observer = new IntersectionObserver(function(entries, obs){
                entries.forEach(function(entry){
                    if(entry.isIntersecting) {
                        // Animate IN
                        anime(Object.assign({}, animeProps, {
                            targets: "#" + "' . $uniqueId . '",
                            direction: "normal"
                        }));
                        hasAnimatedIn = true;

                        if("' . $once . '" === "y") {
                            obs.unobserve(entry.target);
                        }
                    } else {
                        if("' . $mirror . '" === "y" && hasAnimatedIn) {
                            anime(Object.assign({}, animeProps, {
                                targets: "#" + "' . $uniqueId . '",
                                direction: "reverse"
                            }));
                        }
                    }
                });
            }, options);

            observer.observe(element);
        })();
    ';


    TikiLib::lib('header')->add_js_module($observerJs);

    $smarty = TikiLib::lib('smarty');
    $smarty->assign('uniqueId', $uniqueId);
    $smarty->assign('data', $data);
    return $smarty->fetch('wiki-plugins/wikiplugin_animateonscroll.tpl');
}
