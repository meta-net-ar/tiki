<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Lib\CookieConsent\CookieConsentLib;
use Tiki\Lib\Theme\ThemeLib;
use Tiki\Lib\TikiDate;
use Tiki\TaskQueue\QueuedTaskBanner;
use Tiki\Sections;

if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    die('This script may only be included.');
}

Sections::onSectionChange(function ($section) {
    global $prefs, $user, $base_url;

    $smarty = TikiLib::lib('smarty');
    $tikilib = TikiLib::lib('tiki');
    $headerlib = TikiLib::lib('header');

// need to rebuild because they were created in tiki-setup and just removed due to clear cache
// LEGACY: assign short variable for use in templates
    $smarty->assign('js', 1);
//for use in setting tags for css menus as fallback for action dropdowns
    $smarty->assign('libeg', '');
    $smarty->assign('liend', '');
    $plus_one_year = ($tikilib->now + 365 * 24 * 3600) * 1000;      // ms

// load translations lang object from /lang/xx/language.js if there
    if (file_exists('lang/' . $prefs['language'] . '/language.js')) {
        // after the usual lib includes (up to 10) but before custom.js (50)
        $headerlib
            ->add_jsfile_late('lang/' . $prefs['language'] . '/language.js')
            ->add_js("$.lang = '" . $prefs['language'] . "';");
    }


    /** Use custom.js in lang dir if there **/
    $language = Language::getCurrentLanguage();
    if (is_file("lang/$language/custom.js")) {
        TikiLib::lib('header')->add_jsfile_late("lang/$language/custom.js");    // before styles custom.js
    }

//LEGACY CODE, you should use _custom instead
    if (! empty($tikidomain) && is_file("lang/$language/$tikidomain/custom.js")) {      // Note: lang tikidomain dirs not created automatically
        TikiLib::lib('header')->add_jsfile_late("lang/$language/$tikidomain/custom.js");
    }

// Is there a lang/$language/custom.js in the current base theme (no options)?
    $custom_js = ThemeLib::getThemePath(null, '', "lang/$language/custom.js");
    if ($custom_js) {
        $headerlib->add_jsfile_late($custom_js);
    }

// Is there a lang/$language/custom.js in the current theme options?
    $custom_js = ThemeLib::getThemePath(null, null, "lang/$language/custom.js");
    if ($custom_js) {
        $headerlib->add_jsfile_late($custom_js);
    }


//Is there a lang/$language/custom.js in_custom/shared
    $custom_js = \Tiki\Paths\Customization::getSharedPublicPath(\Tiki\Paths\Customization::getCustomLangFragment());
    if ($custom_js) {
        $headerlib->add_jsfile_late($custom_js);
    }

//Is there a lang/$language/custom.js from _custom/sites
    $custom_js = \Tiki\Paths\Customization::getCurrentSitePublicPath(\Tiki\Paths\Customization::getCustomLangFragment());
    if ($custom_js) {
        $headerlib->add_jsfile_late($custom_js);
    }

    /* Use js/custom.js in themes and options dir if there */

// Before Tiki 27, we used to also look in /themes/js for a custom.js file

    $custom_js_files = [];
    $custom_js_modules = [];

// Is there a js/custom.js in the current base theme (no options)?
    $custom_js_files[] = ThemeLib::getThemePath(null, '', 'js/custom.js');
    $custom_js_modules[] = ThemeLib::getThemePath(null, '', 'js/custom.module.js');

// Is there a js/custom.js in the current theme options?
    $custom_js_files[] = ThemeLib::getThemePath(null, null, 'js/custom.js');
    $custom_js_modules[] = ThemeLib::getThemePath(null, null, 'js/custom.module.js');

//Is there a _custom/shared/js/custom.js
    $custom_js_files[] = \Tiki\Paths\Customization::getSharedPublicPath(TIKI_CUSTOMIZATIONS_JAVASCRIPT_PATH_FRAGMENT);

//Is there a _custom/sites/my.domain/js/custom.js
    $custom_js_files[] = \Tiki\Paths\Customization::getCurrentSitePublicPath(TIKI_CUSTOMIZATIONS_JAVASCRIPT_PATH_FRAGMENT);

    foreach ($custom_js_files as $custom_js_file) {
        if (! empty($custom_js_file)) {
            $headerlib->add_jsfile($custom_js_file);
        }
    }

    foreach ($custom_js_modules as $custom_js_module) {
        if (! empty($custom_js_module)) {
            $headerlib->add_js_module(
                "
import \"./$custom_js_module\";
"
            );
        }
    }

// setup timezone array
    $tz = TikiDate::getTimezoneAbbreviations();
    $headerlib->add_js(
        '
try {
    var timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    setCookie("local_tz", timezone, "", "session", window.tikiCookieConstants.BUILTIN_COOKIE_CATEGORY_FUNCTIONAL);
} catch (e) {}

// this is used by tiki-confirm.js checkTimeout, so needs to be always set
const scriptStartDate = new Date(); //Be careful, this will not be a global if this script ever becomes a module - benoitg - 2023-11-21

if (! timezone) {
    function inArray(item, array) {
        for (var i in array) {
            if (array[i] === item) {
                return i;
            }
        }
        return false;
    }
    var allTimeZoneCodes = ' . json_encode(array_map("strtoupper", $tz)) . ';
    var now_string = now.toString();
    var offsethours = - now.getTimezoneOffset() / 60;
    setCookie("local_tzoffset", offsethours, "", "session", window.tikiCookieConstants.BUILTIN_COOKIE_CATEGORY_FUNCTIONAL);
    var m = now_string.match(/[ \(]([A-Z]{3,6})[ \)]?[ \d]*$/);    // try three or more char tz first at the end or just before the year
    if (!m) {
        m = now_string.match(/[ \(]([A-Z]{1,6})[ \)]?[ \d]*$/);    // might be a "military" one if not
    }
    if (m) {
        m = m[1];
    } else {    // IE (sometimes) gives UTC +offset instead of the abbreviation
        // sadly this workaround will fail for non-whole hour offsets
        var hours = - now.getTimezoneOffset() / 60;
        m = "GMT" + (hours > 0 ? " + " : "") + hours;
    }
    // Etc/GMT+ is equivalent to GMT-
    if (m.substring(0,4) == "GMT + ") {
        m = "Etc / GMT - " + m.substring(4);
        setCookie("local_tz", m, "", "session", window.tikiCookieConstants.BUILTIN_COOKIE_CATEGORY_FUNCTIONAL);
    }
    if (m.substring(0,4) == "GMT - ") {
        m = "Etc / GMT + " + m.substring(4);
        setCookie("local_tz", m, "", "session", window.tikiCookieConstants.BUILTIN_COOKIE_CATEGORY_FUNCTIONAL);
    }
    if (inArray(m, allTimeZoneCodes)) {
        setCookie("local_tz", m, "", "session", window.tikiCookieConstants.BUILTIN_COOKIE_CATEGORY_FUNCTIONAL);
    }
}
',
        2
    );

// Extract cookie consent constants from the CookieConsentLib
// Create a ReflectionClass instance for the CookieConsentLib class
    $reflection = new ReflectionClass(CookieConsentLib::class);

// Get all the constants defined in the CookieConsentLib class
    $constants = $reflection->getConstants();

// Generate JavaScript content with the constants
    $jsContent = "window.tikiCookieConstants = Object.freeze({\n";
    foreach ($constants as $name => $value) {
        $jsContent .= "    $name: " . json_encode($value) . ",\n";
    }
    $jsContent .= "});\n";
// Add the JavaScript content to the header
    $headerlib->add_js_config($jsContent);

    $jqueryTiki['ui'] = $prefs['feature_jquery_ui'] === 'y';
    $jqueryTiki['ui_theme'] = $prefs['feature_jquery_ui_theme'];
    $jqueryTiki['tooltips'] = true;
    $jqueryTiki['smartmenus'] = $prefs['jquery_smartmenus_enable'] === 'y';
    $jqueryTiki['smartmenus_collapsible_behavior'] = $prefs['jquery_smartmenus_collapsible_behavior'];
    $jqueryTiki['smartmenus_open_close_click'] = $prefs['jquery_smartmenus_open_close_click'] === 'y';
    $jqueryTiki['tablesorter'] = $prefs['feature_jquery_tablesorter'] === 'y';
    $jqueryTiki['colorbox'] = $prefs['feature_shadowbox'] === 'y';
    $jqueryTiki['sheet'] = $prefs['feature_sheet'] === 'y';
    $jqueryTiki['carousel'] = $prefs['feature_jquery_carousel'] === 'y';
    $jqueryTiki['validate'] = $prefs['feature_jquery_validation'] === 'y';
    $jqueryTiki['zoom'] = $prefs['feature_jquery_zoom'] === 'y';
    $jqueryTiki['url_fragment_guesser'] = ($prefs['url_fragment_guesser'] ?? 'n') === 'y';

// Default effect
    $jqueryTiki['effect'] = $prefs['jquery_effect'];
// "horizontal" | "vertical" etc
    $jqueryTiki['effect_direction'] = $prefs['jquery_effect_direction'];
// "slow" | "normal" | "fast" | milliseconds (int) ]
    $jqueryTiki['effect_speed'] = $prefs['jquery_effect_speed'] === 'normal' ? '400' : $prefs['jquery_effect_speed'];
    $jqueryTiki['effect_tabs'] = $prefs['jquery_effect_tabs'];      // Different effect for tabs
    $jqueryTiki['effect_tabs_direction'] = $prefs['jquery_effect_tabs_direction'];
    $jqueryTiki['effect_tabs_speed'] = $prefs['jquery_effect_tabs_speed'] === 'normal' ? '400' : $prefs['jquery_effect_tabs_speed'];
    $jqueryTiki['home_file_gallery'] = $prefs['home_file_gallery'];

    $jqueryTiki['autosave'] = $prefs['ajax_autosave'] === 'y';
    $jqueryTiki['sefurl'] = $prefs['feature_sefurl'] === 'y';
    $jqueryTiki['ajax'] = $prefs['feature_ajax'] === 'y';
    $jqueryTiki['syntaxHighlighter'] = $prefs['feature_syntax_highlighter'] === 'y';
    $jqueryTiki['mapTileSets'] = $tikilib->get_preference('geo_tilesets', ['openstreetmap'], true);
    $jqueryTiki['infoboxTypes'] = Services_Object_Controller::supported();
    $jqueryTiki['googleStreetView'] = $prefs['geo_google_streetview'] === 'y';
    $jqueryTiki['googleStreetViewOverlay'] = $prefs['geo_google_streetview_overlay'] === 'y';
    $jqueryTiki['googleMapsAPIKey'] = $prefs['gmap_key'];
    $jqueryTiki['structurePageRepeat'] = $prefs['page_n_times_in_a_structure'] === 'y';
    $jqueryTiki['no_cookie'] = false;
    $jqueryTiki['language'] = Language::getCurrentLanguage();
    $jqueryTiki['useInlineComment'] = $prefs['feature_inline_comments'] === 'y';
    $jqueryTiki['useInlineAnnotations'] = $prefs['comments_inline_annotator'] === 'y';
    $jqueryTiki['helpurl'] = $prefs['feature_help'] === 'y' ? $prefs['helpurl'] : '';
    $jqueryTiki['shortDateFormat'] = $prefs['short_date_format_js'];
    $jqueryTiki['shortTimeFormat'] = $prefs['short_time_format_js'];
    $jqueryTiki['changeMonth'] = $prefs['change_month'] === 'y';
    $jqueryTiki['changeYear'] = $prefs['change_year'] === 'y';
    $jqueryTiki['username'] = $user;
    $jqueryTiki['userRealName'] = TikiLib::lib('user')->clean_user($user);
    $jqueryTiki['userAvatar'] = $base_url . TikiLib::lib('userprefs')->get_public_avatar_path($user);
    $jqueryTiki['autoToc_inline'] = $prefs['wiki_inline_auto_toc'] === 'y';
    $jqueryTiki['autoToc_pos'] = $prefs['wiki_toc_pos'];
    $jqueryTiki['autoToc_offset'] = $prefs['wiki_toc_offset'];
    $jqueryTiki['autoToc_tabs'] = $prefs['wiki_toc_tabs'];
    $jqueryTiki['bingMapsAPIKey'] = $prefs['geo_bingmaps_key'];
    $jqueryTiki['nextzenAPIKey'] = $prefs['geo_nextzen_key'];
    $jqueryTiki['numericFieldScroll'] = $prefs['unified_numeric_field_scroll'];
    $jqueryTiki['themeIconset'] = $prefs['theme_iconset'];
    $jqueryTiki['display_start_year'] = $prefs['display_start_year'];
    $jqueryTiki['display_end_year'] = $prefs['display_end_year'];
    $jqueryTiki['display_field_order'] = $prefs['display_field_order'];
    $jqueryTiki['tiki_same_day_time_only'] = $prefs['tiki_same_day_time_only'];
    $jqueryTiki['jquery_timeago'] = $prefs['jquery_timeago'] === 'y';
    $jqueryTiki['short_date_format'] = $prefs['short_date_format'];
    $jqueryTiki['short_time_format'] = $prefs['short_time_format'];
    $jqueryTiki['cookie_consent_enabled'] = $prefs['cookie_consent_feature'] === 'y' ? true : false;
    $jqueryTiki['cookie_consent_dom_id'] = $prefs['cookie_consent_dom_id'];
    $jqueryTiki['cookie_consent_mode'] = $prefs['cookie_consent_mode'];
    $jqueryTiki['cookie_consent_expires'] = $prefs['cookie_consent_expires'];
    $jqueryTiki['cookie_consent_disable'] = $prefs['cookie_consent_disable'] === "y";
    $jqueryTiki['cookie_consent_name'] = CookieConsentLib::COOKIE_CONSENT_NAME;
    $jqueryTiki['cookie_consent_categories'] = json_encode(array_keys(CookieConsentLib::getRequestedCookieCategories()));
    $jqueryTiki['cookie_consent_value'] = json_encode(CookieConsentLib::getConsentPreferences(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $jqueryTiki['BUILTIN_COOKIE_CATEGORY_ESSENTIAL'] = json_encode(CookieConsentLib::BUILTIN_COOKIE_CATEGORY_ESSENTIAL);
    $jqueryTiki['wiki_url_scheme'] = $prefs['wiki_url_scheme'];
    $jqueryTiki['feature_queued_tasks'] = ($prefs['feature_queued_tasks'] ?? 'n') === 'y';
    $jqueryTiki['queued_tasks_js_processing_disabled'] = ($prefs['queued_tasks_js_processing_disabled'] ?? 'n') === 'y';
    $jqueryTiki['calendar_pdf_export_layout'] = $prefs['calendar_pdf_export_layout'];
// Check if user has active tasks (Pending or InProgress) to enable smart polling
    $hasActiveJobs = false;
    if (($prefs['feature_queued_tasks'] ?? 'n') === 'y') {
        $activeJobs = QueuedTaskBanner::get(function ($job) {
            return isset($job['status']) && ($job['status'] === 'Pending' || $job['status'] === 'InProgress');
        });
        $hasActiveJobs = ! empty($activeJobs);
    }
    $jqueryTiki['queued_tasks_has_pending'] = $hasActiveJobs;

//set at 4 hours if empty
    $jqueryTiki['securityTimeout'] = ! empty($prefs['site_security_timeout']) ? $prefs['site_security_timeout']
        : TikiLib::lib('access')->getDefaultTimeout();

    if (empty($object)) {
        $object = current_object();
    }
    if (empty($object)) {
        $object = ['type' => '', 'object' => ''];
    }
    $jqueryTiki['current_object'] = $object;

    $username_pattern = " / ^ ['\-_a-zA-Z0-9\.]*$/";
    if (is_string($prefs['username_pattern'])) {
        if (trim($prefs['username_pattern']) && trim($prefs['username_pattern']) !== '') {
            $jqueryTiki['usernamePattern'] = trim($prefs['username_pattern']);
        } else {
            $jqueryTiki['usernamePattern'] = $username_pattern;
        }
    } else {
        $jqueryTiki['usernamePattern'] = $username_pattern;
    }
    $jqueryTiki['print_pdf_from_url'] = "{$prefs['print_pdf_from_url']}";


    if ($prefs['feature_calendar'] === 'y') {
        $calendarlib = TikiLib::lib('calendar');
        $jqueryTiki['firstDayofWeek'] = $calendarlib->firstDayofWeek();
    }

    $js = '
// JS Object to hold prefs for jq
var jqueryTiki = ' . json_encode($jqueryTiki, JSON_UNESCAPED_SLASHES) . "\n";

    if ($prefs['feature_syntax_highlighter'] !== 'y') {
        // add a dummy syntaxHighlighter object as it seems to be used all over the place without checking for the feature
        $js .= <<< 'EOT'
var syntaxHighlighter = {
    ready: function(textarea, settings) {
        return null;
    },
    sync: function(textarea) {
        return null;
    },
    add: function(editor, $input, none, skipResize) {
        return null;
    },
    remove: function($input) {
        return null;
    },
    get: function($input) {
        return null;
    },
    fullscreen: function(textarea) {
        return null;
    },
    find: function(textareaEditor, val) {
        return null;
    },
    searchCursor: [],
    replace: function(textareaEditor, val, replaceVal) {
        return null;
    },
    insertAt: function(textareaEditor, replaceString, perLine, blockLevel) {
        return null;
    }
};
EOT;
    }

    $headerlib->add_js_config($js);
    $js = '';

    if (in_array('y', [$prefs['feature_draggable_modals'], ! empty($prefs['feature_resizable_modals']) ? $prefs['feature_resizable_modals'] : 'n'])) {
        $headerlib->add_jsfile(NODE_PUBLIC_DIST_PATH . '/interactjs/dist/interact.min.js');
    }

    if ($prefs['feature_draggable_modals'] === 'y') {
        $js .= <<< 'EOT'
$(document).on("shown.bs.modal", function(event) {
    const dialog = $(event.target).find(".modal-dialog");
    if (!dialog.attr('id')) { // To avoid overwriting IDs
        dialog.attr("id", 'modal-dialog-' + Math.random().toString(36).substring(7));
    }
    const draggableId = dialog.attr('id');
    interact(`#${draggableId}`).draggable({
        inertia: true,
        autoScroll: true,
        allowFrom: '.modal-header',
        listeners: {
            move: (e) => {
                const target = e.target;
                // Store position in data-attributes
                let x = (parseFloat(target.getAttribute('data-x')) || 0) + e.dx;
                let y = (parseFloat(target.getAttribute('data-y')) || 0) + e.dy;
                target.style.transform = `translate(${x}px, ${y}px)`;
                target.setAttribute('data-x', x);
                target.setAttribute('data-y', y);
            }
        }
    });
});
EOT;
        $headerlib->add_css('.modal-header {cursor: grab}');
    }

    if (! empty($prefs['feature_resizable_modals']) && $prefs['feature_resizable_modals'] === 'y') {
        $js .= <<< 'EOT'
$(document).on("tiki.modal.redraw", function(event) {
    let $modalContent = $(event.target);
    if (!$modalContent.is(".modal-content")) {
        $modalContent = $modalContent.find(".modal-content")
    }
    
    // Guard: Prevent duplicate listeners on the same modal
    const el = $modalContent[0];
    if (el.dataset.resizeInit === '1') return;
    el.dataset.resizeInit = '1';

    const modalBody = $modalContent.find(".modal-body")[0];
    if (!modalBody) return;

    let initialHeight;
    const initialOverflow = getComputedStyle(modalBody).overflow;

    const modalId = 'modal' + Math.random().toString(36).substring(7);
    $modalContent.attr("id", modalId);
    const dialogEl = $modalContent[0];
    dialogEl.dataset.x = dialogEl.dataset.x || 0;
    dialogEl.dataset.y = dialogEl.dataset.y || 0;
    interact(`#${modalId}`).resizable({
        edges: { left: true, right: true, bottom: true, top: true },
        inertia: true,
        listeners: {
            // The start listener captures the modal's initial height if not yet done, at the moment resizing begins.
            start(event) {
                if(! initialHeight) initialHeight = event.rect.height;
            },
            move: (e) => {
                const target = e.target;
                // width/height from interact
                const width = Math.max(e.rect.width, 0);
                const height = Math.max(e.rect.height, 0);

                // translate when resizing from left/top
                const x = (parseFloat(target.dataset.x) || 0) + (e.deltaRect.left || 0);
                const y = (parseFloat(target.dataset.y) || 0) + (e.deltaRect.top || 0);

                target.style.width = `${width}px`;
                if (e.edges.top || e.edges.bottom) {
                    target.style.height = `${height}px`;
                }
                target.style.transform = `translate(${x}px, ${y}px)`;
                target.dataset.x = x;
                target.dataset.y = y;
                if (width < modalBody.scrollWidth || height < initialHeight) {
                    modalBody.style.overflow = "auto";
                } else {
                    modalBody.style.overflow = initialOverflow;
                }
            }
        }
    });
});
EOT;
    }

    if ($prefs['feature_realtime'] === 'y') {
        $websocket_full_base_url = $prefs['realtime_full_base_url'];
        if (empty($websocket_full_base_url)) {
            $ws_url = json_encode(preg_replace('#http://#', 'ws://', preg_replace('#https://#', 'wss://', $base_url)) . 'ws/');
        } else {
            $ws_url = json_encode($websocket_full_base_url);
        }
        $session_token = session_id();

        $js .= <<<EOT
var tikiOpenWS = function (endpoint) {
    const queryString = endpoint.split('?')[1];
    const searchParams = new URLSearchParams(queryString);
    if (searchParams.toString()){ //some query params already exist
        if(searchParams.has("token")){
            return new WebSocket($ws_url + endpoint);
        }
        else{ //other query params exist but not the token
            return new WebSocket($ws_url + endpoint + "&token=$session_token");
        }
    }
    return new WebSocket($ws_url + endpoint + "?token=$session_token");
}\n
EOT;
        // TODO: use a preference for automatic start of WS session on each page - seems resource intensive...
        $js .= <<< 'EOT'
    initTikiGlobalWS();
EOT;
    }

    $headerlib->add_js($js);

    if ($prefs['feature_ajax'] != 'y') {
        $prefs['ajax_autosave'] = 'n';
    }

    if (isset($prefs['feature_elementplus']) && $prefs['feature_elementplus'] == 'y') {
        require_once('lib/setup/ui/elementplus.php');
    }
});
