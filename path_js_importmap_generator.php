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
                "@event-calendar/core" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@event-calendar/core/index.js",
                "@event-calendar/day-grid" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@event-calendar/day-grid/index.js",
                "@event-calendar/interaction" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@event-calendar/interaction/index.js",
                "@event-calendar/list" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@event-calendar/list/index.js",
                "@event-calendar/resource-time-grid" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@event-calendar/resource-time-grid/index.js",
                "@event-calendar/resource-timeline" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@event-calendar/resource-timeline/index.js",
                "@event-calendar/time-grid" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@event-calendar/time-grid/index.js",
                "@kurkle/color" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@kurkle/color/dist/color.esm.js",
                "@popperjs/core" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@popperjs/core/dist/esm/index.js",
                "animejs" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/anime/dist/anime.es.js",
                "@shoelace/color-picker" => $tikiUrl . JS_ASSETS_PATH . "/color-picker.js",
                "bootstrap" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/bootstrap/dist/js/bootstrap.esm.min.js",
                "chartjs" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/chart.js/dist/chart.js",
                "clipboard" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/clipboard/dist/clipboard.min.js",
                "dompurify" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/dompurify/dist/purify.es.js",
                "driver.js" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/driver.js/dist/driver.js.mjs",
                "jquery" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/jquery/dist/jquery.js",
                // We can't add jquery-validation because it's not available as ESM
                "mermaid" => $tikiUrl . MERMAID_DIST_PATH . "/mermaid.esm.min.mjs",
                "moment" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/moment/dist/moment.js",
                "ol" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/ol/dist/ol.js",
                "select2" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/select2/dist/select2.min.js",
                "smartmenus" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/smartmenus/dist/js/smartmenus.esm.js",
                "sortablejs" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/sortablejs/modular/sortable.esm.js",
                "summernote" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/summernote/dist/summernote-bs5.min.js",
                // currently we don't use the prod build to improve the experience for SFC
                "vue" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/vue/dist/vue.esm-browser.js",

                /* jquery_tiki */
                "@jquery-tiki/constants" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/constants.js",
                "@jquery-tiki/plugin-edit" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/plugin-edit/index.js",
                "@jquery-tiki/plugin-edit/buttons" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/plugin-edit/buttons.js",
                "@jquery-tiki/plugins/cypht" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/plugins/cypht.js",
                "@jquery-tiki/plugins/dialog" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/plugins/dialog.js",
                "@jquery-tiki/plugins/pagetabs" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/plugins/pagetabs.js",
                "@jquery-tiki/plugins/wysiwyg" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/plugins/wysiwyg.js",
                "@jquery-tiki/tiki-calendar" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tiki-calendar.js",
                "@jquery-tiki/tiki-svgedit_draw" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tiki-svgedit_draw.js",
                "@jquery-tiki/tiki-handle_svgedit" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tiki-handle_svgedit.js",
                "@jquery-tiki/tiki-admin_menu_options" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tiki-admin_menu_options.js",
                "@jquery-tiki/tiki-edit_structure" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tiki-edit_structure.js",
                "@jquery-tiki/ui-utils" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/ui-utils/index.js",
                "@jquery-tiki/wikiplugin-trackercalendar" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/wikiplugin-trackercalendar.js",
                "@jquery-tiki/fullcalendar_to_pdf" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/fullcalendar_to_pdf.js",
                "@jquery-tiki/tiki-maps-ol3" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tiki-maps-ol3.js",
                "@jquery-tiki/wysiwyg" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/wysiwyg/index.js",

                /* single-spa microfrontends and common files (root and styleguide) */
                "@vue-mf/duration-picker" => $tikiUrl . JS_ASSETS_PATH . "/duration-picker.js",
                "@vue-mf/emoji-picker" => $tikiUrl . JS_ASSETS_PATH . "/emoji-picker.js",
                "@vue-mf/kanban" => $tikiUrl . JS_ASSETS_PATH . "/kanban.js",
                "@vue-mf/root-config" => $tikiUrl . JS_ASSETS_PATH . "/root-config.js",
                "@vue-mf/styleguide" => $tikiUrl . JS_ASSETS_PATH . "/styleguide.js",
                "@vue-mf/tiki-offline" => $tikiUrl . JS_ASSETS_PATH . "/tiki-offline.js",
                "@vue-mf/toolbar-dialogs" => $tikiUrl . JS_ASSETS_PATH . "/toolbar-dialogs.js",

                /* vue widgets */
                "@vue-widgets/el-autocomplete" => $tikiUrl . JS_ASSETS_PATH . "/element-plus-ui/autocomplete.js",
                "@vue-widgets/el-date-picker" => $tikiUrl . JS_ASSETS_PATH . "/element-plus-ui/datepicker.js",
                "@vue-widgets/el-file-gal-uploader" => $tikiUrl . JS_ASSETS_PATH . "/element-plus-ui/fileGalUploader.js",
                "@vue-widgets/el-select" => $tikiUrl . JS_ASSETS_PATH . "/element-plus-ui/select.js",
                "@vue-widgets/el-transfer" => $tikiUrl . JS_ASSETS_PATH . "/element-plus-ui/transfer.js",

                /* tiki-iot */
                "@tiki-iot/tiki-iot-dashboard-all" => $tikiUrl . JS_ASSETS_PATH . "/tiki-iot/tiki-iot-dashboard-all.js",
                "@tiki-iot/tiki-iot-dashboard" => $tikiUrl . JS_ASSETS_PATH . "/tiki-iot/tiki-iot-dashboard.js",

                /* tiki-vue-sfc-loader */
                "@tiki-vue-sfc-loader" => $tikiUrl . JS_ASSETS_PATH . "/tiki-vue-sfc-loader.js",
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
