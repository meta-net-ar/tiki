<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * @param bool $useBaseUrl by default (false) the urls generated only have the URI part, when using baseUrl will also include proto and host (full URL)
 *
 * @return string
 * @throws JsonException
 */
function generateJsImportmapScripts(bool $useBaseUrl = false)
{
    global $tikiroot, $base_url;

    $tikiUrl = $useBaseUrl ? $base_url : $tikiroot;

    $importmap = (object) [
            // NOTE: Keep the list alphabetically sorted.
            //IMPORTANT:  All these have to be ESM modules, TEST them, don't assume that they are.
            "imports" => [
                "@tiki/editor-toolbar/fileRecording" => $tikiUrl . JS_ASSETS_PATH . "/@tiki/editor-toolbar/fileRecording.js",
                "@tiki/modules/search" => $tikiUrl . JS_ASSETS_PATH . "/@tiki/modules/search.js",
                "@tiki/plugin-base/buttonParamHandler" => $tikiUrl . JS_ASSETS_PATH . "/@tiki/plugin-base/buttonParamHandler.js",
                "@tiki/plugin-base/registerFieldDependency" => $tikiUrl . JS_ASSETS_PATH . "/@tiki/plugin-base/registerFieldDependency.js",
                "@tiki/plugins/bigbluebutton" => $tikiUrl . JS_ASSETS_PATH . "/@tiki/plugins/bigbluebutton.js",
                "@tiki/plugins/dialog" => $tikiUrl . JS_ASSETS_PATH . "/@tiki/plugins/dialog.js",
                "@tiki/plugins/fade" => $tikiUrl . JS_ASSETS_PATH . "/@tiki/plugins/fade.js",
                "@tiki/plugins/pagetabs" => $tikiUrl . JS_ASSETS_PATH . "/@tiki/plugins/pagetabs.js",
                "@tiki/tracker-field-base/dirtyCheck" => $tikiUrl . JS_ASSETS_PATH . "/@tiki/tracker-field-base/dirtyCheck.js",
                "@tiki/tracker-fields/emailFolder" => $tikiUrl . JS_ASSETS_PATH . "/@tiki/tracker-fields/emailFolder.js",
                "@tiki/tracker-fields/secret" => $tikiUrl . JS_ASSETS_PATH . "/@tiki/tracker-fields/secret.js",

                /* src/js/@tiki/ui-utils */
                "@tiki/ui-utils" => $tikiUrl . JS_ASSETS_PATH . "/@tiki/ui-utils.js",

                /* src/js/avatar-generator */
                "avatar-generator" => $tikiUrl . JS_ASSETS_PATH . "/avatar-generator.js",

                /* common_externals available in ESM format */
                "@dicebear/core" => $tikiUrl . DICEBEAR_COLLECTIONS_PATH . "/core.js",

                //The following @dicebear likely need updating if @dicebear/collection above is updated.
                //To check, update, and access http://tiki.local/tiki-pick_avatar.php, open the js console, ans see if there is an error at the top
                "@dicebear/adventurer" => $tikiUrl . DICEBEAR_COLLECTIONS_PATH . "/adventurer.js",
                "@dicebear/adventurerNeutral" => $tikiUrl . DICEBEAR_COLLECTIONS_PATH . "/adventurerNeutral.js",
                "@dicebear/avataaars" => $tikiUrl . DICEBEAR_COLLECTIONS_PATH . "/avataaars.js",
                "@dicebear/avataaarsNeutral" => $tikiUrl . DICEBEAR_COLLECTIONS_PATH . "/avataaarsNeutral.js",
                "@dicebear/bigEars" => $tikiUrl . DICEBEAR_COLLECTIONS_PATH . "/bigEars.js",
                "@dicebear/bigEarsNeutral" => $tikiUrl . DICEBEAR_COLLECTIONS_PATH . "/bigEarsNeutral.js",
                "@dicebear/bigSmile" => $tikiUrl . DICEBEAR_COLLECTIONS_PATH . "/bigSmile.js",
                "@dicebear/bottts" => $tikiUrl . DICEBEAR_COLLECTIONS_PATH . "/bottts.js",
                "@dicebear/botttsNeutral" => $tikiUrl . DICEBEAR_COLLECTIONS_PATH . "/botttsNeutral.js",
                "@dicebear/croodles" => $tikiUrl . DICEBEAR_COLLECTIONS_PATH . "/croodles.js",
                "@dicebear/croodlesNeutral" => $tikiUrl . DICEBEAR_COLLECTIONS_PATH . "/croodlesNeutral.js",
                "@dicebear/dylan" => $tikiUrl . DICEBEAR_COLLECTIONS_PATH . "/dylan.js",
                "@dicebear/funEmoji" => $tikiUrl . DICEBEAR_COLLECTIONS_PATH . "/funEmoji.js",
                "@dicebear/glass" => $tikiUrl . DICEBEAR_COLLECTIONS_PATH . "/glass.js",
                "@dicebear/icons" => $tikiUrl . DICEBEAR_COLLECTIONS_PATH . "/icons.js",
                "@dicebear/identicon" => $tikiUrl . DICEBEAR_COLLECTIONS_PATH . "/identicon.js",
                "@dicebear/initials" => $tikiUrl . DICEBEAR_COLLECTIONS_PATH . "/initials.js",
                "@dicebear/lorelei" => $tikiUrl . DICEBEAR_COLLECTIONS_PATH . "/lorelei.js",
                "@dicebear/loreleiNeutral" => $tikiUrl . DICEBEAR_COLLECTIONS_PATH . "/loreleiNeutral.js",
                "@dicebear/micah" => $tikiUrl . DICEBEAR_COLLECTIONS_PATH . "/micah.js",
                "@dicebear/miniavs" => $tikiUrl . DICEBEAR_COLLECTIONS_PATH . "/miniavs.js",
                "@dicebear/notionists" => $tikiUrl . DICEBEAR_COLLECTIONS_PATH . "/notionists.js",
                "@dicebear/notionistsNeutral" => $tikiUrl . DICEBEAR_COLLECTIONS_PATH . "/notionistsNeutral.js",
                "@dicebear/openPeeps" => $tikiUrl . DICEBEAR_COLLECTIONS_PATH . "/openPeeps.js",
                "@dicebear/personas" => $tikiUrl . DICEBEAR_COLLECTIONS_PATH . "/personas.js",
                "@dicebear/pixelArt" => $tikiUrl . DICEBEAR_COLLECTIONS_PATH . "/pixelArt.js",
                "@dicebear/pixelArtNeutral" => $tikiUrl . DICEBEAR_COLLECTIONS_PATH . "/pixelArtNeutral.js",
                "@dicebear/rings" => $tikiUrl . DICEBEAR_COLLECTIONS_PATH . "/rings.js",
                "@dicebear/shapes" => $tikiUrl . DICEBEAR_COLLECTIONS_PATH . "/shapes.js",
                "@dicebear/thumbs" => $tikiUrl . DICEBEAR_COLLECTIONS_PATH . "/thumbs.js",
                "@dicebear/toonHead" => $tikiUrl . DICEBEAR_COLLECTIONS_PATH . "/toonHead.js",

                "@kurkle/color" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@kurkle/color/dist/color.esm.js",
                "@lottiefiles/dotlottie-wc" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@lottiefiles/dotlottie-wc/dist/index.js",
                "@popperjs/core" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@popperjs/core/dist/esm/index.js",
                "@shoelace/color-picker" => $tikiUrl . JS_ASSETS_PATH . "/color-picker.js",
                "altcha" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/altcha/dist/altcha.js",
                "animejs" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/anime/dist/anime.es.js",
                "bootstrap" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/bootstrap/dist/js/bootstrap.esm.min.js",
                "chartjs" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/chart.js/dist/chart.js",
                "clipboard" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/clipboard/dist/clipboard.min.js",
                // converse.js 14+ ships ESM-only (no UMD/global build); ConverseJS.php imports
                // this bare specifier and assigns it to window.converse for the classic plugin files.
                "converse.js" => $tikiUrl . CONVERSEJS_DIST_PATH . "/converse.min.js",
                "dompurify" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/dompurify/dist/purify.es.mjs",
                "driver.js" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/driver.js/dist/driver.js.mjs",
                "fieldslinker" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/fieldslinker/dist/fieldsLinker.js",
                "html2canvas-pro" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/html2canvas-pro/dist/html2canvas-pro.esm.js",
                "jquery" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/jquery/dist/jquery.js",
                // We can't add jquery-validation because it's not available as ESM
                "mermaid" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/mermaid/dist/mermaid.esm.min.mjs",
                "moment" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/moment/dist/moment.js",
                "ol" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/ol/dist/ol.js",
                "smartmenus" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/smartmenus/dist/js/smartmenus.esm.js",
                "sortablejs" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/sortablejs/modular/sortable.esm.js",
                "summernote" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/summernote/dist/summernote-bs5.min.js",
                "svgedit" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/svgedit/dist/editor/Editor.js",
                "timeline" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/vis-timeline/dist/vis-timeline-graph2d.esm.js",
                "three" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/three/build/three.module.min.js",

                "underscore" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/underscore/underscore-esm-min.js",
                // currently we don't use the prod build to improve the experience for SFC
                "vue" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/vue/dist/vue.esm-browser.js",
                "vue3-sfc-loader" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/vue3-sfc-loader/dist/vue3-sfc-loader.esm.js",

                /* src/js/common_reexported */
                "common-reexported/jspdf" => $tikiUrl . JS_ASSETS_PATH . "/common-reexported/jspdf.js",

                /* src/js/jquery_tiki */
                "@jquery-tiki/asyncLoop" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tiki-async-loop.js",
                "@jquery-tiki/constants" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/constants.js",
                "@jquery-tiki/cypht" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/cypht.js",
                "@jquery-tiki/coverpage-form" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/coverpage-form.js",
                "@jquery-tiki/report-wizard" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/report-wizard.js",
                "@jquery-tiki/tiki-calendar" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tiki-calendar.js",
                "@jquery-tiki/tiki-cookie-handler" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tiki-cookie-handler.js",
                "@jquery-tiki/tiki-editor_settings" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tiki-editor_settings.js",
                "@jquery-tiki/tiki-svgedit_draw" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tiki-svgedit_draw.js",
                "@jquery-tiki/tiki-handle_svgedit" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tiki-handle_svgedit.js",
                "@jquery-tiki/tiki-admin_menu_options" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tiki-admin_menu_options.js",
                "@jquery-tiki/tiki-admin_2fa" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tiki-admin_2fa.js",
                "@jquery-tiki/tiki-edit_structure" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tiki-edit_structure.js",
                "@jquery-tiki/wikiplugin-mouseover" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/wikiplugin-mouseover.js",
                "@jquery-tiki/wikiplugin-trackercalendar" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/wikiplugin-trackercalendar.js",
                "@jquery-tiki/eventcalendar_to_pdf" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/eventcalendar_to_pdf.js",
                "@jquery-tiki/tiki-maps" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tiki-maps.js",
                "@jquery-tiki/tiki-password" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tiki-password.js",
                "@jquery-tiki/tiki-share" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tiki-share.js",
                "@jquery-tiki/tiki-field_limiter" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tiki-field_limiter.js",
                "@jquery-tiki/languageCheckTextarea" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/languageCheckTextarea.js",
                "@jquery-tiki/timeago" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/timeago.js",
                "@jquery-tiki/validate-alt-image" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/validate-alt-image.js",

                /* src/js/tiki-3d-model-viewer */
                "@tiki-3d-model-viewer/model3dviewer" => $tikiUrl . JS_ASSETS_PATH . "/tiki-3d-model-viewer.js",

                /* src/js/tiki-figlet */
                "@tiki-figlet" => $tikiUrl . JS_ASSETS_PATH . "/tiki-figlet.js",

                /* src/js/tiki-glightbox */
                "@tiki-glightbox" => $tikiUrl . JS_ASSETS_PATH . "/tiki-glightbox.js",

                /* src/js/tiki-html5-qrcode */
                "@html5-qrcode/html5-qrcode" => $tikiUrl . JS_ASSETS_PATH . "/tiki-html5-qrcode.js",

                /* src/js/tiki-iot */
                "@tiki-iot/tiki-iot-dashboard-all" => $tikiUrl . JS_ASSETS_PATH . "/tiki-iot/tiki-iot-dashboard-all.js",
                "@tiki-iot/tiki-iot-dashboard" => $tikiUrl . JS_ASSETS_PATH . "/tiki-iot/tiki-iot-dashboard.js",

                /* src/js/tiki-lottie */
                "@tiki-lottie" => $tikiUrl . JS_ASSETS_PATH . "/tiki-lottie.js",

                /* src/js/tiki-mermaid */
                "@mermaidPack" => $tikiUrl . JS_ASSETS_PATH . "/tiki-mermaid.js",

                /* src/js/tiki-plugin-zones */
                "@tiki-plugin-zones" => $tikiUrl . JS_ASSETS_PATH . "/tiki-plugin-zones.js",

                /* src/js/tiki-sentry-browser */
                "@tiki-modules/sentryBrowser" => $tikiUrl . JS_ASSETS_PATH . "/tiki-sentry-browser.js",

                /* src/js/tiki-toast-ui Toast-ui editor */
                "@tiki-toast-ui/editor-index" => $tikiUrl . JS_ASSETS_PATH . "/tiki-toast-ui.js",

                /* src/js/tiki-vue-sfc-loader */
                "@tiki-vue-sfc-loader" => $tikiUrl . JS_ASSETS_PATH . "/tiki-vue-sfc-loader.js",

                /* src/js/vue-mf single-spa microfrontends and common files (root and styleguide) */
                "@vue-mf/duration-picker" => $tikiUrl . JS_ASSETS_PATH . "/duration-picker.js",
                "@vue-mf/emoji-picker" => $tikiUrl . JS_ASSETS_PATH . "/emoji-picker.js",
                "@vue-mf/kanban" => $tikiUrl . JS_ASSETS_PATH . "/kanban.js",
                "@vue-mf/root-config" => $tikiUrl . JS_ASSETS_PATH . "/root-config.js",
                "@vue-mf/styleguide" => $tikiUrl . JS_ASSETS_PATH . "/styleguide.js",
                "@vue-mf/tiki-offline" => $tikiUrl . JS_ASSETS_PATH . "/tiki-offline.js",
                "@vue-mf/toolbar-dialogs" => $tikiUrl . JS_ASSETS_PATH . "/toolbar-dialogs.js",
                "@vue-mf/tracker-rules" => $tikiUrl . JS_ASSETS_PATH . "/tracker-rules.js",
                "@vue-mf/sss-admin" => $tikiUrl . JS_ASSETS_PATH . "/sss-admin.js",

                /* src/js/vue-widgets vue widgets */
                "@vue-widgets/el-autocomplete" => $tikiUrl . JS_ASSETS_PATH . "/element-plus-ui/autocomplete.js",
                "@vue-widgets/el-date-picker" => $tikiUrl . JS_ASSETS_PATH . "/element-plus-ui/datepicker.js",
                "@vue-widgets/el-file-gal-uploader" => $tikiUrl . JS_ASSETS_PATH . "/element-plus-ui/fileGalUploader.js",
                "@vue-widgets/el-file-input" => $tikiUrl . JS_ASSETS_PATH . "/element-plus-ui/fileInput.js",
                "@vue-widgets/el-input" => $tikiUrl . JS_ASSETS_PATH . "/element-plus-ui/input.js",
                "@vue-widgets/el-message" => $tikiUrl . JS_ASSETS_PATH . "/element-plus-ui/message.js",
                "@vue-widgets/el-select" => $tikiUrl . JS_ASSETS_PATH . "/element-plus-ui/select.js",
                "@vue-widgets/el-slider" => $tikiUrl . JS_ASSETS_PATH . "/element-plus-ui/slider.js",
                "@vue-widgets/el-transfer" => $tikiUrl . JS_ASSETS_PATH . "/element-plus-ui/transfer.js",
                "@vue-widgets/el-backtop" => $tikiUrl . JS_ASSETS_PATH . "/element-plus-ui/backtop.js",
                "@vue-widgets/encrypted-field" => $tikiUrl . JS_ASSETS_PATH . "/element-plus-ui/encryptedField.js",
                "@vue-widgets/enter-key-modal" => $tikiUrl . JS_ASSETS_PATH . "/element-plus-ui/enterKeyModal.js",

                /* src/js/wysiwyg */
                "@wysiwyg/summernote" => $tikiUrl . JS_ASSETS_PATH . "/wysiwyg/summernote.js",
                "@wysiwyg/plugin" => $tikiUrl . JS_ASSETS_PATH . "/wysiwyg/plugin.js",
            ]
        ];
    $importmapJson = json_encode($importmap, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    $esModuleShimsSrc = $tikiUrl . NODE_PUBLIC_DIST_PATH . "/es-module-shims/dist/es-module-shims.js";
    $html = <<<HTML
    <script async src="$esModuleShimsSrc"></script>
    <script type="importmap">
        $importmapJson
    </script>
    <script type="module">
        import "@vue-mf/root-config";
    </script>
    HTML;
    return $html;
}
