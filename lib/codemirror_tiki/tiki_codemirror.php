<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * Create codemirror modes in temp - put wiki language upfront.
 */
function addCodemirror()
{
    global $prefs, $tikidomainslash;
    $js = '';
    $css = '';

    $target = 'temp/public/' . $tikidomainslash;
    $jsModes = $target . 'codemirror_modes.js';
    $cssModes = $target . 'codemirror_modes.css';

    if (! file_exists($jsModes) || ! file_exists($cssModes)) {
        //codemirror theme
        $js .= 'window.codeMirrorTheme = "' . $prefs['feature_syntax_highlighter_theme'] . '";
test = { mode: function () {}, indentation: function() {} }
';  // test is a dummy line to supress exceptions from mode tests in cm 3


        foreach (glob(CODEMIRROR_DIST_PATH . '/mode/*', GLOB_ONLYDIR) as $dir) {
            foreach (glob($dir . '/*.js', GLOB_NOCHECK) as $jsFile) {
                if (
                    is_file($jsFile) &&
                    (
                        $prefs['tiki_minify_javascript'] !== 'y' ||
                        (
                            // FIXME temporary workaound for errors in codemirror 5.19.0
                            ! str_contains($jsFile, 'powershell.js') && ! str_contains($jsFile, 'swift.js')
                        )
                    )
                ) {
                    $js .= "//" . $jsFile . "\n";
                    $js .= "try {\n" . @file_get_contents($jsFile) . "\n} catch (e) { };\n";
                }
            }
            foreach (glob($dir . '/*.css', GLOB_NOCHECK) as $cssFile) {
                if (is_file($cssFile)) {
                    $css .= "/*" . $cssFile . "*/\n";
                    $css .= @file_get_contents($cssFile);
                }
            }
        }

        //load themes
        foreach (glob(CODEMIRROR_DIST_PATH . '/theme/*.css') as $cssFile) {
            $css .= @file_get_contents($cssFile);
        }

        file_put_contents($jsModes, $js);
        chmod($jsModes, 0644);

        file_put_contents($cssModes, $css);
        chmod($cssModes, 0644);
    }

    //add codemirror stuff
    TikiLib::lib("header")->add_cssfile(CODEMIRROR_DIST_PATH . '/lib/codemirror.css')
        ->add_jsfile_dependency(CODEMIRROR_DIST_PATH . '/lib/codemirror.js')
        ->add_jsfile(CODEMIRROR_DIST_PATH . '/addon/search/searchcursor.js')
        ->add_jsfile(CODEMIRROR_DIST_PATH . '/addon/mode/overlay.js')
        //add tiki stuff
        ->add_cssfile('themes/base_files/feature_css/codemirror_tiki.css')
        ->add_jsfile('lib/codemirror_tiki/codemirror_tiki.js')
        //add interactjs
        ->add_jsfile(NODE_PUBLIC_DIST_PATH . '/interactjs/dist/interact.min.js')
        ->add_jsfile($jsModes)
        ->add_cssfile($cssModes);
}
