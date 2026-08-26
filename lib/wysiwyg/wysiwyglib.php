<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/*
 * Shared functions for tiki implementation of the wysiwyg html and markdown editor
 */

class WYSIWYGLib
{
    public function setupInlineEditor($pageName)
    {
        global $prefs, $user;

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

        $params = [
            '_wysiwyg'     => 'y',
            'area_id'      => false,
            'comments'     => '',
            '_is_html'      => 'y',  // temporary element id
            'switcheditor' => 'n',
            'inline' => true
        ];

        $headerlib = TikiLib::lib('header');
        $smarty = TikiLib::lib('smarty');

        $tools = json_encode(\SmartyTiki\FunctionHandler\Toolbars::render($params, $smarty->getEmptyInternalTemplate()), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS);
        $tools = addslashes($tools); // Escape special characters for JavaScript

        ['lang' => $lang, 'filePath' => $langFilePath] = $this->getEditorLang();

        $headerlib->add_js_module(<<<JS
            import('@wysiwyg/summernote').then((module) => {
                module.loadLanguage('{$langFilePath}', () => {
                    module.inlineEdit(JSON.parse(`{$tools}`), '{$lang}', '{$pageName}');
                });
            });
        JS);
    }

    public function getEditorLang()
    {
        $lang = TikiLib::lib('tiki')->get_language();
        if ($lang === 'en' || $lang === 'en-uk') { // Summernote only has en-US
            $lang = 'en-US';
        } else {
            $parts = explode('-', $lang);
            $lang = $parts[0] . '-' . strtoupper($parts[1] ?? $parts[0]);
        }

        $langFilePath = NODE_PUBLIC_DIST_PATH . '/summernote/dist/lang/summernote-' . $lang . '.min.js';

        return ['lang' => $lang, 'filePath' => $langFilePath];
    }

    public function setUpEditor($dom_id, $params = [])
    {
        global $prefs, $user;
        $headerlib = TikiLib::lib('header');
        $smarty = TikiLib::lib('smarty');

        if ($params['_toolbars'] !== 'n') {
            $tools = json_encode(\SmartyTiki\FunctionHandler\Toolbars::render($params, $smarty->getEmptyInternalTemplate()), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS);
            $tools = addslashes($tools); // Escape special characters for JavaScript
        } else {
            $tools = json_encode([]);
        }

        ['lang' => $lang, 'filePath' => $langFilePath] = $this->getEditorLang();

        // Prepare language check options from prefs with effective language fallback chain
        $lcEnabled = $prefs['feature_language_check'] === 'y' ? 'true' : 'false';
        $lcDebounce = (int) $prefs['language_check_debounce_ms'];
        $lcAutoBool = $prefs['language_check_auto_detect'] === 'y';
        $lcAuto = $lcAutoBool ? 'true' : 'false';

        // Determine effective language for validation
        $contentLang = isset($params['lang']) && $params['lang'] ? $params['lang'] : '';
        if (! $contentLang && ! empty($params['objectId']) && ! empty($params['section']) && $params['section'] === 'wiki page') {
            $info = TikiLib::lib('tiki')->get_page_info($params['objectId']);
            if (! empty($info['lang'])) {
                $contentLang = $info['lang'];
            }
        }
        if ($contentLang) {
            $effectiveLanguage = $contentLang;
        } else {
            $effectiveLanguage = Language::getCurrentLanguage();
        }

        // Always check if language is supported by LanguageTool (regardless of auto-detect setting)
        $isLanguageSupported = false;
        try {
            $ltUrl = Services_LanguageCheck_Controller::getLanguageToolUrl($prefs);
            $client = TikiLib::lib('tiki')->get_http_client();
            $client->setUri(rtrim($ltUrl, '/') . '/v2/languages');
            $client->setMethod(Laminas\Http\Request::METHOD_GET);
            $client->setOptions(['timeout' => 5]);
            $response = $client->send();
            if ($response->isSuccess()) {
                $data = json_decode($response->getBody(), true);
                if (is_array($data)) {
                    $supportedCodes = [];
                    foreach ($data as $langInfo) {
                        $code = $langInfo['code'] ?? ($langInfo['longCode'] ?? null);
                        if ($code) {
                            $supportedCodes[] = strtolower($code);
                            // Also check short code (e.g., 'en' matches 'en-US')
                            $shortCode = explode('-', $code)[0];
                            if ($shortCode) {
                                $supportedCodes[] = strtolower($shortCode);
                            }
                        }
                    }
                    $effectiveLangLower = strtolower($effectiveLanguage);
                    $effectiveLangShort = strtolower(explode('-', $effectiveLanguage)[0]);
                    $isLanguageSupported = in_array($effectiveLangLower, $supportedCodes) || in_array($effectiveLangShort, $supportedCodes);
                }
            }
        } catch (Exception $e) {
            $isLanguageSupported = false;
        }

        $lcDefault = json_encode($effectiveLanguage);
        $lcIsSupported = $isLanguageSupported ? 'true' : 'false';

        $loadingIndicatorVar = 'loadingIndicator' . $dom_id;
        $enableSummernoteLC = ($prefs['feature_language_check'] === 'y') ? 'true' : 'false';
        $headerlib->add_js_module(<<<JS
            const $loadingIndicatorVar = $($.IMPORT_LOADER_MARKUP);
            $('#{$dom_id}').after($loadingIndicatorVar);
            $.editorSection = "{$params['section']}";

            import('@wysiwyg/summernote').then((module) => {
                module.loadLanguage('{$langFilePath}', () => {
                    $loadingIndicatorVar.remove();
                    const options = {lang: '{$lang}'};
                    if ($.editorSection === 'wiki page') {
                        options.height = 600;
                    }
                    if ({$enableSummernoteLC}) {
                        options.languageCheck = {
                            enabled: {$lcEnabled},
                            debounceMs: {$lcDebounce},
                            autoDetect: {$lcAuto},
                            defaultLanguage: {$lcDefault},
                            isLanguageSupported: {$lcIsSupported}
                        };
                    }
                    module.default('{$dom_id}', JSON.parse(`{$tools}`), options);
                });
            });
        JS);
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

            if (! str_contains($pluginMarkup, ' ')) {
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
            $mdCustomBlock = "{$startNewLine}\$\$tiki\n{$pluginMarkup}\n\$\${$endNewLine}";
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
            'initialEditType' => $prefs['markdown_wysiwyg_intitial_edit_type'],
            'usageStatistics' => $prefs['markdown_wysiwyg_usage_statistics'] === 'y',
            'initialValue' => $content,
        ];

        $languageCode = $this->languageMapISO($prefs['language']);
        if ($languageCode) {
            $options['language'] = $languageCode;
            $headerlib->add_jsfile(
                NODE_PUBLIC_DIST_PATH . "/@toast-ui/editor/dist/i18n/" . strtolower($languageCode) . '.js'
            );
        }

        if (! empty($params['_toolbars'])  && $params['_toolbars'] === 'y') {
            /** @var \Tiki\Smarty\SmartyTiki $smarty */
            $smarty = TikiLib::lib('smarty');
            $toolbarParams = [
                'syntax' => 'markdown',
                'area_id' => $dom_id,
                '_wysiwyg' => 'y',
                'is_html' => false,
            ];
            $tuitools = \SmartyTiki\FunctionHandler\Toolbars::render($toolbarParams, $smarty->getEmptyInternalTemplate());
        } else {
            $tuitools = [];
        }
        $options['toolbarItems'] = $tuitools;

        $jsonOptions = json_encode($options);
        // using %~ at the start and end of values that need to be literals, like functions
        $jsonOptions = preg_replace(['/"%~/', '/~%"/'], '', $jsonOptions);

        $headerlib
        ->add_js_module("import tikiToastEditor from '@tiki-toast-ui/editor-index';
            $(document).ready(function() {
                tikiToastEditor($jsonOptions);
            })
        ");

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

        return $langMap[$lang] ?? '';
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
