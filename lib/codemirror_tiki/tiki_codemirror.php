<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function addCodemirrorModesJs(): void
{
    global $prefs;

    global $tikidomainslash;
    $target = 'temp/public/' . $tikidomainslash;
    $jsModes = $target . 'codemirror_modes.js';

    //codemirror theme
    $js = 'window.codeMirrorTheme = "' . $prefs['feature_syntax_highlighter_theme'] . '";
test = { mode: function () {}, indentation: function() {} }
';  // test is a dummy line to supress exceptions from mode tests in cm 3

    if (! file_exists($jsModes)) {
        foreach (glob(CODEMIRROR_DIST_PATH . '/mode/*', GLOB_ONLYDIR) as $dir) {
            foreach (glob($dir . '/*.js', GLOB_NOCHECK) as $jsFile) {
                if (
                    is_file($jsFile) &&
                    (
                        $prefs['tiki_minify_javascript'] !== 'y' ||
                        (
                            // FIXME temporary workaround for errors in CodeMirror 5.19.0
                            ! str_contains($jsFile, 'powershell.js') &&
                            ! str_contains($jsFile, 'swift.js')
                        )
                    )
                ) {
                    $js .= "//" . $jsFile . "\n";
                    $js .= "try {\n" . @file_get_contents($jsFile) . "\n} catch (e) { };\n";
                }
            }
        }
        file_put_contents($jsModes, $js);
        chmod($jsModes, 0644);
    }
    TikiLib::lib("header")->add_jsfile($jsModes);
}

function addCodemirrorModesCss(): void
{
    global $tikidomainslash;
    $target = 'temp/public/' . $tikidomainslash;
    $css = '';
    $cssModes = $target . 'codemirror_modes.css';
    if (! file_exists($cssModes)) {
        foreach (glob(CODEMIRROR_DIST_PATH . '/mode/*', GLOB_ONLYDIR) as $dir) {
            foreach (glob($dir . '/*.css', GLOB_NOCHECK) as $cssFile) {
                if (is_file($cssFile)) {
                    $css .= "/*" . $cssFile . "*/\n";
                    $css .= @file_get_contents($cssFile);
                }
            }
        }

        foreach (glob(CODEMIRROR_DIST_PATH . '/theme/*.css') as $cssFile) {
            if (is_file($cssFile)) {
                $css .= @file_get_contents($cssFile);
            }
        }
        file_put_contents($cssModes, $css);
        chmod($cssModes, 0644);
    }

    TikiLib::lib("header")->add_cssfile($cssModes);
}

/**
 * Create codemirror modes in temp - put wiki language upfront.
 */
function addCodemirror(): void
{
    //add codemirror stuff
    TikiLib::lib("header")
        ->add_jsfile(CODEMIRROR_DIST_PATH . '/lib/codemirror.js')
        ->add_jsfile(CODEMIRROR_DIST_PATH . '/addon/search/searchcursor.js')
        ->add_jsfile(CODEMIRROR_DIST_PATH . '/addon/mode/overlay.js')
        //add tiki stuff
        ->add_jsfile('lib/codemirror_tiki/codemirror_tiki.js')
        //add interactjs
        ->add_jsfile(NODE_PUBLIC_DIST_PATH . '/interactjs/dist/interact.min.js');
        addCodemirrorModesJs();
}
