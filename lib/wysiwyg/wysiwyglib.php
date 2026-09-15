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
    /**
     * Editorial disambiguation map from Tiki language codes to editor locale codes.
     * Used by resolveLocaleCode() (Summernote) and languageMapISO() (Toast UI).
     *
     * TikiLib::get_language() returns bare codes (e.g. 'es', 'fr'), which can match
     * more than one locale file on disk. This map makes the explicit editorial choice
     * (e.g. 'de' → 'de-DE' rather than 'de-CH'). Languages absent from the map fall
     * back to disk-based discovery in resolveSummernoteLocale().
     *
     * Special cases:
     *   - Empty string (''): English — Summernote has no locale file; Toast uses its default.
     *   - 'ar': bare code kept because Toast UI ships ar.js; Summernote resolves ar-AR from disk.
     */
    private const TIKI_EDITOR_LOCALE_MAP = [
        'ar'      => 'ar',           // Toast UI: ar.js exists; Summernote: disk resolves ar-AR regardless
        //'bg'    => 'bg',           // Bulgarian — no editor file yet
        //'ca'    => 'ca',           // Catalan — no editor file yet
        'cn'      => 'zh-CN',        // Simplified Chinese: Tiki='cn', editors='zh-CN'
        'cs'      => 'cs-CZ',        // Czech
        //'cy'    => 'cy',           // Welsh — no editor file yet
        //'da'    => 'da',           // Danish — no editor file yet
        'de'      => 'de-DE',        // German
        'en'      => '',             // English — Toast default (no file); Summernote uses 'en-US' (built-in, no locale file loaded)
        'en-uk'   => 'en-US',        // British English → en-US for both editors
        'es'      => 'es-ES',        // Spanish
        //'el'    => 'el',           // Greek — no editor file yet
        //'fa'    => 'fa',           // Farsi — no editor file yet
        'fi'      => 'fi-FI',        // Finnish
        //'fj'    => 'fj',           // Fijian — no editor file yet
        'fr'      => 'fr-FR',        // French
        'fy-NL'   => 'nl-NL',        // West Frisian → Dutch (no dedicated file in either editor)
        'gl'      => 'gl-ES',        // Galician
        //'he'    => 'he',           // Hebrew — no editor file yet
        'hr'      => 'hr-HR',        // Croatian
        //'id'    => 'id',           // Indonesian — no editor file yet
        //'is'    => 'is',           // Icelandic — no editor file yet
        'it'      => 'it-IT',        // Italian
        //'iu'    => 'iu',           // Inuktitut — no editor file yet
        //'iu-ro' => 'iu-ro',        // Inuktitut (Roman) — no editor file yet
        //'iu-iq' => 'iu-iq',        // Iniunnaqtun — no editor file yet
        'ja'      => 'ja-JP',        // Japanese
        'ko'      => 'ko-KR',        // Korean
        //'hu'    => 'hu',           // Hungarian — no editor file yet
        //'lt'    => 'lt',           // Lithuanian — no editor file yet
        'nds'     => 'de-DE',        // Low German → German locale
        'nl'      => 'nl-NL',        // Dutch
        'no'      => 'nb-NO',        // Norwegian: Tiki='no', editors use Bokmål 'nb'
        'pl'      => 'pl-PL',        // Polish
        // 'pt' is intentionally absent: Toast UI has no pt.js (only pt-br.js, covered below),
        // and Summernote resolves pt-PT from disk without a map entry.
        'pt-br'   => 'pt-BR',        // Brazilian Portuguese
        //'ro'    => 'ro',           // Romanian — no editor file yet
        //'rm'    => 'rm',           // Romansh — no editor file yet
        'ru'      => 'ru-RU',        // Russian
        //'sb'    => 'sb',           // Pijin Solomon — no editor file yet
        //'si'    => 'si',           // Sinhala — no editor file yet
        //'sk'    => 'sk',           // Slovak — no editor file yet
        //'sl'    => 'sl',           // Slovene — no editor file yet
        //'sq'    => 'sq',           // Albanian — no editor file yet
        'sr-latn' => 'sr-RS-Latin',  // Latin Serbian: suffix naming mismatch across editors
        'sv'      => 'sv-SE',        // Swedish
        //'tv'    => 'tv',           // Tuvaluan — no editor file yet
        'tr'      => 'tr-TR',        // Turkish
        'tw'      => 'zh-TW',        // Traditional Chinese: Tiki='tw', editors='zh-TW'
        'uk'      => 'uk-UA',        // Ukrainian
        //'vi'    => 'vi',           // Vietnamese — no editor file yet
    ];

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
        $tikLang = TikiLib::lib('tiki')->get_language();
        $locale = $this->resolveLocaleCode($tikLang);
        $langFilePath = NODE_PUBLIC_DIST_PATH . '/summernote/dist/lang/summernote-' . $locale . '.min.js';

        return ['lang' => $locale, 'filePath' => $langFilePath];
    }

    /**
     * Resolve the Summernote locale code for a given Tiki language code.
     *
     * TikiLib::get_language() returns bare codes (e.g. 'es', 'fr'). TIKI_EDITOR_LOCALE_MAP
     * makes the editorial choice when a bare code would match multiple files on disk
     * (e.g. 'de' → 'de-DE' rather than 'de-CH'). Languages absent from the map fall back
     * to disk-based discovery in resolveSummernoteLocale().
     *
     * @see TikiLib::get_language()
     */
    protected function resolveLocaleCode(string $tikLang): string
    {
        $mapped = self::TIKI_EDITOR_LOCALE_MAP[$tikLang] ?? null;

        if ($mapped === null) {
            // Language not in map: auto-discover locale from disk.
            return $this->resolveSummernoteLocale($tikLang);
        } elseif ($mapped === '') {
            // English: Summernote renders in English by default; no locale file is loaded.
            return 'en-US';
        } elseif (strpos($mapped, '-') !== false) {
            // Full hyphenated locale (e.g. 'fr-FR', 'zh-CN'): use directly.
            return $mapped;
        } else {
            // Bare language hint (e.g. 'ar'): let disk resolution find the best variant.
            return $this->resolveSummernoteLocale($mapped);
        }
    }

    /**
     * Auto-discover and resolve the correct Summernote locale code from disk.
     * Matches exact case-insensitive code, then falls back to prefix matching
     * (with country-code tiebreaker), and finally defaults to 'en-US'.
     */
    protected function resolveSummernoteLocale(string $tikLang): string
    {
        $langDir = NODE_PUBLIC_DIST_PATH . '/summernote/dist/lang/';
        $files = glob($langDir . 'summernote-*.min.js');

        if (empty($files)) {
            return 'en-US';
        }

        // Extract locale codes from file names (e.g. 'sv-SE', 'de-DE', 'de-CH')
        $availableLocales = [];
        foreach ($files as $file) {
            if (preg_match('/summernote-(.+)\.min\.js$/', basename($file), $matches)) {
                $availableLocales[] = $matches[1];
            }
        }

        // 1. Exact match (case-insensitive) — e.g. Tiki 'pt-br' → Summernote 'pt-BR'
        foreach ($availableLocales as $locale) {
            if (strcasecmp($locale, $tikLang) === 0) {
                return $locale;
            }
        }

        // 2. Prefix match — e.g. Tiki 'sv' matches Summernote 'sv-SE'
        $langPrefix = strtolower(explode('-', $tikLang)[0]);
        $candidates = array_values(array_filter($availableLocales, function ($locale) use ($langPrefix) {
            return strtolower(explode('-', $locale)[0]) === $langPrefix;
        }));

        if (count($candidates) === 1) {
            return $candidates[0];
        }

        if (count($candidates) > 1) {
            // Tiebreak: prefer the locale whose country suffix (uppercased)
            // mirrors the language prefix — e.g. 'de' prefers 'de-DE' over 'de-CH'.
            $preferredCountry = strtoupper($langPrefix);
            foreach ($candidates as $locale) {
                $parts = explode('-', $locale);
                if (isset($parts[1]) && $parts[1] === $preferredCountry) {
                    return $locale;
                }
            }
            // No preferred match: return the first one alphabetically
            sort($candidates);
            return $candidates[0];
        }

        // 3. Fallback
        return 'en-US';
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

    /**
     * Map a Tiki language code to the locale code used by Toast UI editor.
     * Reads from the shared TIKI_EDITOR_LOCALE_MAP constant so both editors
     * are always in sync — update the constant, not this method.
     *
     * @param string $lang  Tiki language code
     * @return string       Toast UI locale code, or '' if Toast has no translation
     */
    private function languageMapISO($lang)
    {
        return self::TIKI_EDITOR_LOCALE_MAP[$lang] ?? '';
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
