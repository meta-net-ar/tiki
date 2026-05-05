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
                "@tiki/plugin-base/buttonParamHandler" => $tikiUrl . JS_ASSETS_PATH . "/@tiki/plugin-base/buttonParamHandler.js",
                "@tiki/plugin-base/registerFieldDependency" => $tikiUrl . JS_ASSETS_PATH . "/@tiki/plugin-base/registerFieldDependency.js",
                "@tiki/plugins/bigbluebutton" => $tikiUrl . JS_ASSETS_PATH . "/@tiki/plugins/bigbluebutton.js",
                "@tiki/plugins/dialog" => $tikiUrl . JS_ASSETS_PATH . "/@tiki/plugins/dialog.js",
                "@tiki/plugins/pagetabs" => $tikiUrl . JS_ASSETS_PATH . "/@tiki/plugins/pagetabs.js",
                "@tiki/tracker-field-base/dirtyCheck" => $tikiUrl . JS_ASSETS_PATH . "/@tiki/tracker-field-base/dirtyCheck.js",
                "@tiki/tracker-fields/emailFolder" => $tikiUrl . JS_ASSETS_PATH . "/@tiki/tracker-fields/emailFolder.js",

                /* src/js/@tiki/ui-utils */
                "@tiki/ui-utils" => $tikiUrl . JS_ASSETS_PATH . "/@tiki/ui-utils.js",

                /* src/js/avatar-generator */
                "avatar-generator" => $tikiUrl . JS_ASSETS_PATH . "/avatar-generator.js",

                /* common_externals available in ESM format */
                "@dicebear/collection" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@dicebear/collection/lib/index.js",
                "@dicebear/core" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@dicebear/core/lib/index.js",

                //The following @dicebear likely need updating if @dicebear/collection above is updated.
                //To check, update, and access http://tiki.local/tiki-pick_avatar.php, open the js console, ans see if there is an error at the top
                "@dicebear/adventurer" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@dicebear/adventurer/lib/index.js",
                "@dicebear/adventurer-neutral" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@dicebear/adventurer-neutral/lib/index.js",
                "@dicebear/avataaars" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@dicebear/avataaars/lib/index.js",
                "@dicebear/avataaars-neutral" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@dicebear/avataaars-neutral/lib/index.js",
                "@dicebear/big-ears" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@dicebear/big-ears/lib/index.js",
                "@dicebear/big-ears-neutral" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@dicebear/big-ears-neutral/lib/index.js",
                "@dicebear/big-smile" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@dicebear/big-smile/lib/index.js",
                "@dicebear/bottts" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@dicebear/bottts/lib/index.js",
                "@dicebear/bottts-neutral" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@dicebear/bottts-neutral/lib/index.js",
                "@dicebear/croodles" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@dicebear/croodles/lib/index.js",
                "@dicebear/croodles-neutral" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@dicebear/croodles-neutral/lib/index.js",
                "@dicebear/dylan" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@dicebear/fun-emoji/lib/index.js",
                "@dicebear/fun-emoji" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@dicebear/collection/lib/index.js",
                "@dicebear/glass" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@dicebear/glass/lib/index.js",
                "@dicebear/icons" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@dicebear/icons/lib/index.js",
                "@dicebear/identicon" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@dicebear/identicon/lib/index.js",
                "@dicebear/initials" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@dicebear/initials/lib/index.js",
                "@dicebear/lorelei" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@dicebear/lorelei/lib/index.js",
                "@dicebear/lorelei-neutral" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@dicebear/lorelei-neutral/lib/index.js",
                "@dicebear/micah" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@dicebear/micah/lib/index.js",
                "@dicebear/miniavs" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@dicebear/miniavs/lib/index.js",
                "@dicebear/notionists" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@dicebear/notionists/lib/index.js",
                "@dicebear/notionists-neutral" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@dicebear/notionists-neutral/lib/index.js",
                "@dicebear/open-peeps" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@dicebear/open-peeps/lib/index.js",
                "@dicebear/personas" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@dicebear/personas/lib/index.js",
                "@dicebear/pixel-art" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@dicebear/pixel-art/lib/index.js",
                "@dicebear/pixel-art-neutral" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@dicebear/pixel-art-neutral/lib/index.js",
                "@dicebear/rings" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@dicebear/rings/lib/index.js",
                "@dicebear/shapes" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@dicebear/shapes/lib/index.js",
                "@dicebear/thumbs" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@dicebear/thumbs/lib/index.js",
                "@dicebear/toon-head" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@dicebear/toon-head/lib/index.js",

                "@kurkle/color" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@kurkle/color/dist/color.esm.js",
                "@lottiefiles/dotlottie-wc" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@lottiefiles/dotlottie-wc/dist/index.js",
                "@popperjs/core" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@popperjs/core/dist/esm/index.js",
                "@shoelace/color-picker" => $tikiUrl . JS_ASSETS_PATH . "/color-picker.js",
                "altcha" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/altcha/dist/altcha.js",
                "animejs" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/anime/dist/anime.es.js",
                "bootstrap" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/bootstrap/dist/js/bootstrap.esm.min.js",
                "chartjs" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/chart.js/dist/chart.js",
                "clipboard" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/clipboard/dist/clipboard.min.js",
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
                "@jquery-tiki/tiki-maps-ol3" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tiki-maps-ol3.js",
                "@jquery-tiki/tiki-password" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tiki-password.js",
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
