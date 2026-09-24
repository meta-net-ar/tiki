<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/*
 * Shared functions for tiki implementation of ckeditor and toast ui for markdown
 */

use Tiki\Lib\core\Toolbar\ToolbarCombos;

class WYSIWYGLib
{
    public static $ckEditor = null;

    public function setupInlineEditor($pageName)
    {
        global $tikiroot, $prefs, $user;

        if ($prefs['javascript_enabled'] != 'y') {
            return;
        }
        // Validate user permissions
        $tikilib = TikiLib::lib('tiki');
        if (! $tikilib->user_has_perm_on_object($user, $pageName, 'wiki page', 'edit')) {
            // Check if the user has inline edit permissions
            if (! $tikilib->user_has_perm_on_object($user, $pageName, 'wiki page', 'edit_inline')) {
                // User has no permission
                return;
            }
        }

        // If the page uses flagged revisions, check if the page can be edited.
        //  Inline edit sessions can cross page boundaries, thus the page attempts to start in inline edit mode
        if ($prefs['flaggedrev_approval'] == 'y') {
            $flaggedrevisionlib = TikiLib::lib('flaggedrevision');
            if ($flaggedrevisionlib->page_requires_approval($pageName)) {
                if (! isset($_REQUEST['latest']) || $_REQUEST['latest'] != '1') {
                    // The page cannot be edited
                    return;
                }
            }
        }

        if (! empty(self::$ckEditor)) {
            // Inline editor is already initialized
            return;
        }
        self::$ckEditor = 'ckeditor4';

        $headerlib = TikiLib::lib('header');

        $headerlib->add_js_config('window.CKEDITOR_BASEPATH = "' . $tikiroot . 'vendor_bundled/vendor/ckeditor/ckeditor/";')
            ->add_jsfile('vendor_bundled/vendor/ckeditor/ckeditor/ckeditor.js', true)
            ->add_js('window.CKEDITOR.config._TikiRoot = "' . $tikiroot . '";', 1);

        // Inline editing config
        $skin = $prefs['wysiwyg_toolbar_skin'] != 'default' ? $prefs['wysiwyg_toolbar_skin'] : 'moono';

        // the toolbar TODO refactor as duplicated from below
        $smarty = TikiLib::lib('smarty');

        $info = $tikilib->get_page_info($pageName, false);  // Don't load page data.
        $params = [
            '_wysiwyg'     => 'y',
            'area_id'      => 'page-data',
            'comments'     => '',
            'is_html'      => $info['is_html'],  // temporary element id
            'switcheditor' => 'n',
        ];

        $cktools = smarty_function_toolbars($params, $smarty->getEmptyInternalTemplate());
        if ($cktools) {
            $cktools[0][count($cktools[0]) - 1][] = 'inlinesave';
            $cktools[0][count($cktools[0]) - 1][] = 'inlinecancel';
            $cktools = json_encode($cktools);
            $cktools = substr($cktools, 1, strlen($cktools) - 2); // remove surrouding [ & ]
            $cktools = str_replace(']],[[', '],"/",[', $cktools); // add new row chars - done here so as not to break existing f/ck
        }
        $ckeformattags = ToolbarCombos::getFormatTags($info['is_html'] ? 'html' : 'wiki');


        $headerlib->add_jsfile('lib/ckeditor_tiki/tiki-ckeditor.js')
            ->add_js(
                '// --- config settings for the inlinesave plugin ---
window.CKEDITOR.config.extraPlugins = "";
window.CKEDITOR.config.extraPlugins += (window.CKEDITOR.config.extraPlugins ? ",inlinesave" : "inlinesave" );
window.CKEDITOR.plugins.addExternal( "inlinesave", "' . $tikiroot . 'lib/ckeditor_tiki/plugins/inlinesave/");
window.CKEDITOR.config.extraPlugins += (window.CKEDITOR.config.extraPlugins ? ",inlinecancel" : "inlinecancel" );
window.CKEDITOR.plugins.addExternal( "inlinecancel", "' . $tikiroot . 'lib/ckeditor_tiki/plugins/inlinecancel/");
window.CKEDITOR.config.ajaxSaveRefreshTime = 30 ;            // RefreshTime
window.CKEDITOR.config.contentsLangDirection = ' . (Language::isRTL() ? '"rtl"' : '"ui"') . ';
// --- plugins
window.CKEDITOR.config.autoSavePage = "' . addcslashes($pageName, '"') . '";        // unique reference for each page
window.CKEDITOR.config.allowedContent = true;
// --- other configs

window.CKEDITOR.config.skin = "' . $skin . '";
window.CKEDITOR.disableAutoInline = true;
window.CKEDITOR.config.toolbar = ' . $cktools . ';
//window.CKEDITOR.config.format_tags = "' . $ckeformattags . '";

'
            );
        $headerlib->add_jsfile('lib/ckeditor_tiki/tikilink_dialog.js');
        $headerlib->add_js(
            '//window.CKEDITOR.config.extraPlugins += (window.CKEDITOR.config.extraPlugins ? ",tikiplugin" : "tikiplugin" );
//            window.CKEDITOR.plugins.addExternal( "tikiplugin", "' . $tikiroot . 'lib/ckeditor_tiki/plugins/tikiplugin/");',
            5
        );
    }

    // According to Jonny Bradley, "the full_page thing was something to do with the unfinished inline editing that is fairly broken now". 2017-06-12
    public function setUpEditor($is_html, $dom_id, $params = [], $auto_save_referrer = '', $full_page = true)
    {
        global $tikiroot, $prefs;
        $headerlib = TikiLib::lib('header');


        $params['_toolbars'] = isset($params['_toolbars']) ? $params['_toolbars'] : 'y';
        if (empty($params['_wysiwyg'])) {
            // needed for toolbars setup
            $params['_wysiwyg'] = 'y';
        }

        $headerlib->add_js('window.CKEDITOR.config.extraPlugins = "' . $prefs['wysiwyg_extra_plugins'] . '";');
        $headerlib->add_js_config('window.CKEDITOR_BASEPATH = "' . $tikiroot . 'vendor_bundled/vendor/ckeditor/ckeditor/";')
            //// for js debugging - copy _source from ckeditor distribution to libs/ckeditor to use
            //// note, this breaks ajax page load via wikitopline edit icon
            ->add_jsfile('vendor_bundled/vendor/ckeditor/ckeditor/ckeditor.js', true)
            ->add_js('window.CKEDITOR.config._TikiRoot = "' . $tikiroot . '";', 1);

        if ($full_page) {
            $headerlib->add_jsfile('lib/ckeditor_tiki/tikilink_dialog.js');
            $headerlib->add_js(
                'window.CKEDITOR.config.extraPlugins += (window.CKEDITOR.config.extraPlugins ? ",tikiplugin,autocomplete,textmatch" : "tikiplugin,autocomplete,textmatch" );
                window.CKEDITOR.plugins.addExternal( "tikiplugin", "' . $tikiroot . 'lib/ckeditor_tiki/plugins/tikiplugin/");',
                5
            );

            if ($prefs['feature_smileys'] === 'y') {
                $headerlib->add_js('window.CKEDITOR.config.extraPlugins += (window.CKEDITOR.config.extraPlugins ? ",emoji" : "emoji" );', 5);
            }
        }
        if (! $is_html && $full_page) {
            $headerlib->add_js(
                'window.CKEDITOR.config.extraPlugins += (window.CKEDITOR.config.extraPlugins ? ",tikiwiki" : "tikiwiki" );
                window.CKEDITOR.plugins.addExternal( "tikiwiki", "' . $tikiroot . 'lib/ckeditor_tiki/plugins/tikiwiki/");',
                5
            );  // before dialog tools init (10)
        }
        if (
            $auto_save_referrer && $prefs['feature_ajax'] === 'y'
            && $prefs['ajax_autosave'] === 'y'
            && $params['autosave'] == 'y'
        ) {
            $headerlib->add_js(
                '// --- config settings for the autosave plugin ---
window.CKEDITOR.config.extraPlugins += (window.CKEDITOR.config.extraPlugins ? ",autosave" : "autosave" );
window.CKEDITOR.plugins.addExternal( "autosave", "' . $tikiroot . 'lib/ckeditor_tiki/plugins/autosave/");
window.CKEDITOR.config.ajaxAutoSaveRefreshTime = 30 ;            // RefreshTime
window.CKEDITOR.config.contentsLangDirection = ' . (Language::isRTL() ? '"rtl"' : '"ui"') . ';
window.CKEDITOR.config.ajaxAutoSaveSensitivity = 2 ;            // Sensitivity to key strokes
register_id("' . $dom_id . '","' . addcslashes($auto_save_referrer, '"') . '");    // Register auto_save so it gets removed on submit
ajaxLoadingShow("' . $dom_id . '");
',
                5
            );  // before dialog tools init (10)
        }

        // finally the toolbar
        $smarty = TikiLib::lib('smarty');

        $params['area_id'] = empty($params['area_id']) ? $dom_id : $params['area_id'];

        $cktools = ($params['_toolbars'] === 'n') ? '[]' : smarty_function_toolbars($params, $smarty->getEmptyInternalTemplate());
        $cktools = json_encode($cktools);
        $cktools = substr($cktools, 1, strlen($cktools) - 2); // remove surrouding [ & ]
        $cktools = str_replace(']],[[', '],"/",[', $cktools); // add new row chars - done here so as not to break existing f/ck

        $ckeformattags = ToolbarCombos::getFormatTags($is_html ? 'html' : 'wiki');

        // js to initiate the editor
        $ckoptions = '{
    toolbar: ' . $cktools . ',
    customConfig: "",
    autoSaveSelf: "' . addcslashes($auto_save_referrer, '"') . '",        // unique reference for each page set up in ensureReferrer()
    font_names: "' . trim($prefs['wysiwyg_fonts']) . '",
    format_tags: "' . $ckeformattags . '",
    stylesSet: "tikistyles:' . $tikiroot . 'lib/ckeditor_tiki/tikistyles.js",
    templates_files: ["' . $tikiroot . 'lib/ckeditor_tiki/tikitemplates.js"],
    skin: "' . ($prefs['wysiwyg_toolbar_skin'] != 'default' ? $prefs['wysiwyg_toolbar_skin'] : 'moono') . '",
    defaultLanguage: "' . $this->languageMap($prefs['language']) . '",
     contentsLangDirection: "' . (Language::isRTL() ? 'rtl' : 'ltr') . '",
    language: "' . ($prefs['feature_detect_language'] === 'y' ? '' : $this->languageMap($prefs['language'])) . '"
    ' . (empty($params['rows']) ? ',height: "' . (empty($params['height']) ? '400' : $params['height']) . '"' : '') . '
    , resize_dir: "both"
    , allowedContent: true
    , versionCheck: false
}';

//  , extraAllowedContent: {        // TODO one day, currently disabling the "Advanced Content Filter" as tiki plugins are too complex
//      "div span": {
//          classes: "tiki_plugin",
//          attributes: "data-plugin data-syntax data-args data-body"
//      }
//  }

        return $ckoptions;
    }

    /**
     * @param string $dom_id
     * @param array  $params
     * @param string $auto_save_referrer
     *
     * @return array
     */
    public function setUpMarkdownEditor(string $dom_id, string $content, array $params = [], string $auto_save_referrer = ''): array
    {
        global $prefs;
        $hashed = [];
        // replace all Wiki Argument Variables by a hash to prevent to be transalted as plugins
        $content = preg_replace_callback('/\{\{(.+?)\}\}/', function ($m) use (&$hashed) {
            return TikiLib::lib('edit')->pushToHashed($hashed, $m[0]);
        }, $content);

        $matches = WikiParser_PluginMatcher::match($content);
        $position = 0;
        $newContent = '';
        foreach ($matches as $match) {
            $newContent .= substr($content, $position, $match->getStart() - $position);

            $pluginMarkup = substr($content, $match->getStart(), $match->getEnd() - $match->getStart());

            // plugin matcher matches random bits of code contained in {} which breaks toast TODO properly
            if (! preg_match('/^\{\S+/', $pluginMarkup)) {
                continue;
            }

            if (strpos($pluginMarkup, ' ') === false) {
                // custom blocks without spaces seem to trigger an error in toast rendering code, so add a "harlmess" space if we don't find one
                $pluginMarkup = str_replace('}', ' }', $pluginMarkup);
            }
            if (substr($content, $match->getStart() - 1, 1) !== "\n") {
                $startNewLine = "\n";
            } else {
                $startNewLine = '';
            }
            if (substr($content, $match->getEnd(), 1) !== "\r" && substr($content, $match->getEnd(), 1) !== "\n") {
                $endNewLine = "\n";
            } else {
                $endNewLine = '';
            }
            $mdCustomBlock = "$startNewLine\$\$tiki\n$pluginMarkup\n\$\$$endNewLine";
            $newContent .= $mdCustomBlock;

            $position = $match->getEnd();
        }

        $newContent .= substr($content, $position);
        $newContent = $this->processSpecialHeadings($newContent);

        $content = $newContent;

        /** @var HeaderLib $headerlib */
        $headerlib = TikiLib::lib('header');

        if (count($hashed) > 0) {
            $content = str_replace($hashed['keys'], $hashed['values'], $content);
        }

        $options = [
            'domId' => "$dom_id",
            'height' => $prefs['markdown_wysiwyg_height'],
            'previewStyle' => $prefs['markdown_wysiwyg_preview_style'],
            'initialEditType' => $prefs['markdown_wysiwyg_initial_edit_type'],
            'usageStatistics' => $prefs['markdown_wysiwyg_usage_statistics'] === 'y',
            'initialValue' => $content,
        ];

        $languageCode = $this->languageMapISO($prefs['language']);
        if ($languageCode) {
            $options['language'] = $languageCode;
            $headerlib->add_jsfile_external(
                'https://uicdn.toast.com/editor/latest/i18n/' . strtolower($languageCode) . '.js'
            );
        }

        if (! empty($params['_toolbars'])  && $params['_toolbars'] === 'y') {
            /** @var Smarty_Tiki $smarty */
            $smarty = TikiLib::lib('smarty');
            $toolbarParams = [
                'syntax' => 'markdown',
                'area_id' => $dom_id,
                '_wysiwyg' => 'y',
                'is_html' => false,
            ];
            $tuitools = smarty_function_toolbars($toolbarParams, $smarty->getEmptyInternalTemplate());
        } else {
            $tuitools = [];
        }
        $options['toolbarItems'] = $tuitools;

        $jsonOptions = json_encode($options);
        // using %~ at the start and end of values that need to be literals, like functions
        $jsonOptions = preg_replace(['/"%~/', '/~%"/'], '', $jsonOptions);

        $headerlib
            //->add_jsfile('vendor_bundled/vendor/npm-asset/toast-ui--editor/dist/toastui-editor.js', true)
            //->add_cssfile('vendor_bundled/vendor/npm-asset/toast-ui--editor/dist/toastui-editor.css')
            //->add_cssfile('https://uicdn.toast.com/editor/latest/toastui-editor.min.css')
            ->add_jq_onready("tikiToastEditor($jsonOptions);");

        return [];
    }

    /** Map between tiki lang codes and Toast (uses ISO codes)
     *
     * @param string $lang  Tiki language code
     *
     * @return string       mapped language code
     *                      defaults empty if not found so not supported
     */
    private function languageMapISO($lang)
    {

        $langMap = [
            'ar' => 'ar',           // Arabic = United Arab Emirates
            //'bg' => 'bg',         // Bulgarian
            //'ca' => 'ca',         // Catalan
            'cn' => 'zh-CN',        // China - Simplified Chinese
            'cs' => 'cs-CZ',        // Czech
            //'cy' => 'cy',         // Welsh
            //'da' => 'da',         // Danish
            'de' => 'de-DE',        // Germany - German
            //'en-uk' => 'en-GB',   // United Kingdom - English
            'en' => '',        // United States - English
            'es' => 'es-ES',        // Spain - Spanish
            //'el' => 'el',         // Greek
            //'fa' => 'fa',         // Farsi
            'fi' => 'fi-FI',         // Finnish
            //'fj' => 'fj',         // Fijian
            'fr' => 'fr-FR',        // France - French
            'fy-NL' => 'nl',        // Netherlands - Dutch
            'gl' => 'gl-ES',        // Galician
            //'he' => 'he',         // Israel - Hebrew
            'hr' => 'hr-HR',        // Croatian
            //'id' => 'id',         // Indonesian
            //'is' => 'is',         // Icelandic
            'it' => 'it-IT',        // Italy - Italian
            //'iu' => 'iu',         // Inuktitut
            //'iu-ro' => 'iu-ro',   // Inuktitut (Roman)
            //'iu-iq' => 'iu-iq',   // Iniunnaqtun
            'ja' => 'ja-JP',        // Japan - Japanese
            'ko' => 'ko-KR',        // Korean
            //'hu' => 'hu',         // Hungarian
            //'lt' => 'lt',         // Lithuanian
            'nds' => 'de-DE',       // Low German
            'nl' => 'nl-NL',        // Netherlands - Dutch
            'no' => 'nb-NO',        // Norway - Norwegian
            'pl' => 'pl-PL',        // Poland - Polish
            'pt' => 'pt',           // Portuguese
            'pt-br' => 'pt-BR',     // Brazil - Portuguese
            //'ro' => 'ro',         // Romanian
            //'rm' => 'rm',         // Romansh
            'ru' => 'ru-RU',        // Russia - Russian
            //'sb' => 'sb',           // Pijin Solomon
            //'si' => 'si',         // Sinhala
            //'sk' => 'sk',         // Slovak
            //'sl' => 'sl',         // Slovene
            //'sq' => 'sq',         // Albanian
            //'sr-latn' => 'sr-latn',   // Serbian Latin
            'sv' => 'sv-SE',        // Sweden - Swedish
            //'tv' => 'tv',           // Tuvaluansr-latn
            'tr' => 'tr-TR',        // Turkey - Turkish
            'tw' => 'zh-TW',        // Taiwan - Traditional Chinese
            'uk' => 'uk-UA',        // Ukrainian
            //'vi' => 'vi',         // Vietnamese
        ];

        return isset($langMap[$lang]) ? $langMap[$lang] : '';
    }

    /** Map between tiki lang codes and ckeditor's (mostly the same)
     *
     * @param string $lang  Tiki language code
     * @return string       mapped language code - defaults to the same if not found
     */
    private function languageMap($lang)
    {

        $langMap = [
            //'ar' => 'ar',         // Arabic = United Arab Emirates - English ok?
            //'bg' => 'bg',         // Bulgarian
            //'ca' => 'ca',         // Catalan
            'cn' => 'zh-cn',        // China - Simplified Chinese
            //'cs' => 'cs',         // Czech
            //'cy' => 'cy',         // Welsh
            //'da' => 'da',         // Danish
            'de' => 'de',           // Germany - German
            'en-uk' => 'en-gb',     // United Kingdom - English
            //'en' => 'en',         // United States - English
            //'es' => 'es',         // Spain - Spanish
            //'el' => 'el',         // Greek
            //'fa' => 'fa',         // Farsi
            //'fi' => 'fi',         // Finnish
            'fj' => 'en',           // Fijian   (not supported)
            //'fr' => 'fr',         // France - French
            'fy-NL' => 'nl',        // Netherlands - Dutch
            'gl' => 'es',               // Galician
            //'he' => 'he',         // Israel - Hebrew
            //'hr' => 'hr',         // Croatian
            //'id' => 'id',         // Indonesian
            //'is' => 'is',         // Icelandic
            //'it' => 'it',         // Italy - Italian
            'iu' => 'en',           // Inuktitut    (not supported)
            'iu-ro' => 'en',        // Inuktitut (Roman)    (not supported)
            'iu-iq' => 'en',        // Iniunnaqtun  (not supported)
            //'ja' => 'ja',         // Japan - Japanese
            //'ko' => 'ko',         // Korean
            //'hu' => 'hu',         // Hungarian
            //'lt' => 'lt',         // Lithuanian
            'nds' => 'de',          // Low German
            //'nl' => 'nl',         // Netherlands - Dutch
            //'no' => 'no',         // Norway - Norwegian
            //'pl' => 'pl',         // Poland - Polish
            //'pt' => 'pt',         // Portuguese
            //'pt-br' => 'pt-br',   // Brazil - Portuguese
            //'ro' => 'ro',         // Romanian
            'rm' => 'en',           // Romansh  (not supported)
            //'ru' => 'ru',         // Russia - Russian
            'sb' => 'en',           // Pijin Solomon    (not supported)
            //'si' => 'si',         // Sinhala
            //'sk' => 'sk',         // Slovak
            //'sl' => 'sl',         // Slovene
            //'sq' => 'sq',         // Albanian
            //'sr-latn' => 'sr-latn',   // Serbian Latin
            //'sv' => 'sv',         // Sweden - Swedish
            'tv' => 'en',           // Tuvaluansr-latn
            //'tr' => 'tr',         // Turkey - Turkish
            'tw' => 'zh',           // Taiwan - Traditional Chinese
            //'uk' => 'uk',         // Ukrainian
            //'vi' => 'vi',         // Vietnamese
        ];

        return isset($langMap[$lang]) ? $langMap[$lang] : $lang;
    }

    private function processSpecialHeadings($content)
    {
        $lines = preg_split('#\r?\n#', $content, 0);
        $newLines = '';
        $totalLines = count($lines);
        $r = '/#{1,6}[\$[\+\-]]?\s/';
        for ($i = 0; $i < $totalLines; $i++) {
            if ($lines[$i] && preg_match($r, $lines[$i])) {
                $nextKey = $i + 1;
                if ($nextKey < $totalLines && $lines[$nextKey] && ! preg_match($r, $lines[$nextKey])) {
                    $newLines .= "\$\$tiki\r\n" . $lines[$i];
                    $i++;
                    while ($i < $totalLines && $lines[$i] && ! preg_match($r, $lines[$i])) {
                        $newLines .= "\r\n" . $lines[$i];
                        if (isset($lines[$i + 1]) && ! preg_match($r, $lines[$i + 1])) {
                            $i++;
                        } else {
                            break;
                        }
                    }
                    $newLines .= "\r\n$$";
                } else {
                    $newLines .= $lines[$i];
                }
            } else {
                $newLines .= $lines[$i];
            }
            if ($i < ($totalLines - 1)) {
                $newLines .= "\r\n";
            }
        }

        return $newLines;
    }
}

global $wysiwyglib;
$wysiwyglib = new WYSIWYGLib();
