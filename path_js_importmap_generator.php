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
            "imports" => [
                /* common_externals available in ESM format */
                "@kurkle/color" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@kurkle/color/dist/color.esm.js",
                "@popperjs/core" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@popperjs/core/dist/esm/index.js",
                "animejs" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/anime/dist/anime.es.js",
                "@shoelace/color-picker" => $tikiUrl . JS_ASSETS_PATH . "/color-picker.js",
                "bootstrap" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/bootstrap/dist/js/bootstrap.esm.min.js",
                "chartjs" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/chart.js/dist/chart.js",
                "clipboard" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/clipboard/dist/clipboard.min.js",
                "dompurify" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/dompurify/dist/purify.es.js",
                "driver.js" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/driver.js/dist/driver.js.mjs",
                "fieldslinker" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/fieldslinker/dist/fieldsLinker.js",
                "jquery" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/jquery/dist/jquery.js",
                // We can't add jquery-validation because it's not available as ESM
                "moment" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/moment/dist/moment.js",
                "ol" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/ol/dist/ol.js",
                "smartmenus" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/smartmenus/dist/js/smartmenus.esm.js",
                "sortablejs" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/sortablejs/modular/sortable.esm.js",
                "summernote" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/summernote/dist/summernote-bs5.min.js",
                "timeline" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/vis-timeline/dist/vis-timeline-graph2d.esm.js",
                // currently we don't use the prod build to improve the experience for SFC
                "vue" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/vue/dist/vue.esm-browser.js",

                /* html5-qrcode */
                "@html5-qrcode/html5-qrcode" => $tikiUrl . JS_ASSETS_PATH . "/tiki-html5-qrcode.js",

                /* common_reexported */
                "common-reexported/jspdf" => $tikiUrl . JS_ASSETS_PATH . "/common-reexported/jspdf/index.js",

                /* jquery_tiki */
                "@jquery-tiki/asyncLoop" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tiki-async-loop.js",
                "@jquery-tiki/constants" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/constants.js",
                "@jquery-tiki/plugin-edit" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/plugin-edit/index.js",
                "@jquery-tiki/plugin-edit/buttons" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/plugin-edit/buttons.js",
                "@jquery-tiki/plugins/cypht" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/plugins/cypht.js",
                "@jquery-tiki/plugins/dialog" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/plugins/dialog.js",
                "@jquery-tiki/plugins/pagetabs" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/plugins/pagetabs.js",
                "@jquery-tiki/plugins/wysiwyg" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/plugins/wysiwyg.js",
                "@jquery-tiki/tiki-calendar" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tiki-calendar.js",
                "@jquery-tiki/tiki-cookie-handler" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tiki-cookie-handler.js",
                "@jquery-tiki/tiki-editor_settings" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tiki-editor_settings.js",
                "@jquery-tiki/tiki-svgedit_draw" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tiki-svgedit_draw.js",
                "@jquery-tiki/tiki-handle_svgedit" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tiki-handle_svgedit.js",
                "@jquery-tiki/tiki-admin_menu_options" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tiki-admin_menu_options.js",
                "@jquery-tiki/tiki-admin_2fa" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tiki-admin_2fa.js",
                "@jquery-tiki/tiki-edit_structure" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tiki-edit_structure.js",
                "@jquery-tiki/wikiplugin-trackercalendar" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/wikiplugin-trackercalendar.js",
                "@jquery-tiki/eventcalendar_to_pdf" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/eventcalendar_to_pdf.js",
                "@jquery-tiki/tiki-maps-ol3" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tiki-maps-ol3.js",
                "@jquery-tiki/tiki-password" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tiki-password.js",
                "@jquery-tiki/tiki-field_limiter" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tiki-field_limiter.js",
                "@jquery-tiki/timeago" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/timeago.js",
                "@jquery-tiki/tracker-fields/emailFolder" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tracker-fields/emailFolder.js",
                "@jquery-tiki/tracker-fields/files" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tracker-fields/files.js",
                "@jquery-tiki/tracker-fields/dirtyCheck" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tracker-fields/all.dirtyCheck.js",
                "@jquery-tiki/validate-alt-image" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/validate-alt-image.js",
                "@tiki-modules/sentryBrowser" => $tikiUrl . JS_ASSETS_PATH . "/tiki-sentry-browser.js",
                "@mermaidPack" => $tikiUrl . JS_ASSETS_PATH . "/tiki-mermaid.js",
                "@tiki-glightbox" => $tikiUrl . JS_ASSETS_PATH . "/tiki-glightbox.js",
                "@tiki-figlet" => $tikiUrl . JS_ASSETS_PATH . "/tiki-figlet.js",

                /* Toast-ui editor */
                "@tiki-toast-ui/editor-index" => $tikiUrl . JS_ASSETS_PATH . "/tiki-toast-ui.js",

                "@tiki/ui-utils" => $tikiUrl . JS_ASSETS_PATH . "/@tiki/ui-utils.js",

                /* single-spa microfrontends and common files (root and styleguide) */
                "@vue-mf/duration-picker" => $tikiUrl . JS_ASSETS_PATH . "/duration-picker.js",
                "@vue-mf/emoji-picker" => $tikiUrl . JS_ASSETS_PATH . "/emoji-picker.js",
                "@vue-mf/kanban" => $tikiUrl . JS_ASSETS_PATH . "/kanban.js",
                "@vue-mf/root-config" => $tikiUrl . JS_ASSETS_PATH . "/root-config.js",
                "@vue-mf/styleguide" => $tikiUrl . JS_ASSETS_PATH . "/styleguide.js",
                "@vue-mf/tiki-offline" => $tikiUrl . JS_ASSETS_PATH . "/tiki-offline.js",
                "@vue-mf/toolbar-dialogs" => $tikiUrl . JS_ASSETS_PATH . "/toolbar-dialogs.js",
                "@vue-mf/tracker-rules" => $tikiUrl . JS_ASSETS_PATH . "/tracker-rules.js",

                /* vue widgets */
                "@vue-widgets/el-autocomplete" => $tikiUrl . JS_ASSETS_PATH . "/element-plus-ui/autocomplete.js",
                "@vue-widgets/el-date-picker" => $tikiUrl . JS_ASSETS_PATH . "/element-plus-ui/datepicker.js",
                "@vue-widgets/el-file-gal-uploader" => $tikiUrl . JS_ASSETS_PATH . "/element-plus-ui/fileGalUploader.js",
                "@vue-widgets/el-file-input" => $tikiUrl . JS_ASSETS_PATH . "/element-plus-ui/fileInput.js",
                "@vue-widgets/el-input" => $tikiUrl . JS_ASSETS_PATH . "/element-plus-ui/input.js",
                "@vue-widgets/el-message" => $tikiUrl . JS_ASSETS_PATH . "/element-plus-ui/message.js",
                "@vue-widgets/el-select" => $tikiUrl . JS_ASSETS_PATH . "/element-plus-ui/select.js",
                "@vue-widgets/el-slider" => $tikiUrl . JS_ASSETS_PATH . "/element-plus-ui/slider.js",
                "@vue-widgets/el-transfer" => $tikiUrl . JS_ASSETS_PATH . "/element-plus-ui/transfer.js",
                "@vue-widgets/el-backtop" => $tikiUrl . JS_ASSETS_PATH . "/element-plus-ui/backTop.js",

                /* tiki-iot */
                "@tiki-iot/tiki-iot-dashboard-all" => $tikiUrl . JS_ASSETS_PATH . "/tiki-iot/tiki-iot-dashboard-all.js",
                "@tiki-iot/tiki-iot-dashboard" => $tikiUrl . JS_ASSETS_PATH . "/tiki-iot/tiki-iot-dashboard.js",

                /* tiki-vue-sfc-loader */
                "@tiki-vue-sfc-loader" => $tikiUrl . JS_ASSETS_PATH . "/tiki-vue-sfc-loader.js",

                "@wysiwyg/summernote" => $tikiUrl . JS_ASSETS_PATH . "/wysiwyg-summernote.js",

                "avatar-generator" => $tikiUrl . JS_ASSETS_PATH . "/avatar-generator.js",
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
