<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * Create codemirror modes in temp - put wiki language upfront.
 */
function createCodemirrorModes()
{
    global $prefs, $tikidomainslash;
    $js = '';
    $css = '';

    $targetFs = tiki_temp_dir(true) . '/' . $tikidomainslash;
    $targetWeb = tiki_temp_dir_web(true) . $tikidomainslash;
    $jsModesFs = $targetFs . 'codemirror_modes.js';
    $cssModesFs = $targetFs . 'codemirror_modes.css';
    $jsModesWeb = $targetWeb . 'codemirror_modes.js';
    $cssModesWeb = $targetWeb . 'codemirror_modes.css';

    if (! file_exists($jsModesFs) || ! file_exists($cssModesFs)) {
        //codemirror theme
        $js .= 'window.codeMirrorTheme = "' . $prefs['feature_syntax_highlighter_theme'] . '";
test = { mode: function () {}, indentation: function() {} }
';  // test is a dummy line to supress exceptions from mode tests in cm 3

        //load modes first
        //tiki first, where are our priorities!
        $js .= @file_get_contents("lib/codemirror_tiki/mode/tiki/tiki.js");
         $css .= @file_get_contents("themes/base_files/feature_css/codemirror_mode_tiki.css");

        foreach (glob(CODEMIRROR_DIST_PATH . '/mode/*', GLOB_ONLYDIR) as $dir) {
            foreach (glob($dir . '/*.js', GLOB_NOCHECK) as $jsFile) {
                if (
                    is_file($jsFile) &&
                    (
                        $prefs['tiki_minify_javascript'] !== 'y' ||
                        (
                            // FIXME temporary workaound for errors in codemirror 5.19.0
                            strpos($jsFile, 'powershell.js') === false && strpos($jsFile, 'swift.js') === false
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

        file_put_contents($jsModesFs, $js);
        chmod($jsModesFs, 0644);

        file_put_contents($cssModesFs, $css);
        chmod($cssModesFs, 0644);
    }

    // creation upfront is ok, but only include them if the feature is enabled. Otherwise we would get js errors bc codemirror itself would be missing
    if (isset($prefs['feature_syntax_highlighter']) && $prefs['feature_syntax_highlighter'] == 'y') {
        TikiLib::lib("header")
            ->add_jsfile($jsModesWeb)
            ->add_cssfile($cssModesWeb);
    }
}
