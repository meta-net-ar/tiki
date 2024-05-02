<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function generateJsImportmapScripts()
{
    global $tikiroot;
    $importmap = (object) [
            // NOTE: Keep the list alphabetically sorted.
            "imports" => [
                /*common_externals*/
                //While bootstrap is available distributted as ESM, we can't use it because  https://getbootstrap.com/docs/5.0/getting-started/javascript/#using-bootstrap-as-a-module
                "@kurkle/color" => $tikiroot . NODE_PUBLIC_DIST_PATH . "/@kurkle/color/dist/color.esm.js",
                "@popperjs/core" => $tikiroot . NODE_PUBLIC_DIST_PATH . "/@popperjs/core/dist/esm/index.js",
                "animejs" => $tikiroot . NODE_PUBLIC_DIST_PATH . "/anime/dist/anime.es.js",
                "bootstrap" => $tikiroot . NODE_PUBLIC_DIST_PATH . "/bootstrap/dist/js/bootstrap.esm.min.js",
                "chartjs" => $tikiroot . NODE_PUBLIC_DIST_PATH . "/chart.js/dist/chart.js",
                "clipboard" => $tikiroot . NODE_PUBLIC_DIST_PATH . "/clipboard/dist/clipboard.min.js",
                "dompurify" => $tikiroot . NODE_PUBLIC_DIST_PATH . "/dompurify/dist/purify.es.js",
                "driver.js" => $tikiroot . NODE_PUBLIC_DIST_PATH . "/driver.js/dist/driver.js.mjs",
                "jquery" => $tikiroot . NODE_PUBLIC_DIST_PATH . "/jquery/dist/jquery.js",
                "moment" => $tikiroot . NODE_PUBLIC_DIST_PATH . "/moment/dist/moment.js",
                "sortablejs" => $tikiroot . NODE_PUBLIC_DIST_PATH . "/sortablejs/modular/sortable.esm.js",
                "vue" => $tikiroot . NODE_PUBLIC_DIST_PATH . "/vue/dist/vue.esm-browser.prod.js",
                // We can't add jquery-validation because it's not available as ESM
                /* jquery_tiki */
                "@jquery-tiki/tiki-calendar" => $tikiroot . JS_ASSETS_PATH . "/jquery-tiki/tiki-calendar.js",
                "@jquery-tiki/tiki-admin_menu_options" => $tikiroot . JS_ASSETS_PATH . "/jquery-tiki/tiki-admin_menu_options.js",
                "@jquery-tiki/tiki-edit_structure" => $tikiroot . JS_ASSETS_PATH . "/jquery-tiki/tiki-edit_structure.js",
                "@jquery-tiki/wikiplugin-trackercalendar" => $tikiroot . JS_ASSETS_PATH . "/wikiplugin-trackercalendar.js",
                "@jquery-tiki/fullcalendar_to_pdf" => $tikiroot . JS_ASSETS_PATH . "/jquery-tiki/fullcalendar_to_pdf.js",
                /* single-spa microfrontends and common files (root and styleguide) */
                "@vue-mf/root-config" => $tikiroot . JS_ASSETS_PATH . "/root-config.js",
                "@vue-mf/styleguide" => $tikiroot . JS_ASSETS_PATH . "/styleguide.js",
                "@vue-mf/kanban" => $tikiroot . JS_ASSETS_PATH . "/kanban.js",
                "@vue-mf/duration-picker" => $tikiroot . JS_ASSETS_PATH . "/duration-picker.js",
                "@vue-mf/toolbar-dialogs" => $tikiroot . JS_ASSETS_PATH . "/toolbar-dialogs.js",
                "@vue-mf/emoji-picker" => $tikiroot . JS_ASSETS_PATH . "/emoji-picker.js"
            ]
            ];
    $importmapJson = json_encode($importmap, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    $esModuleShimsSrc = $tikiroot . NODE_PUBLIC_DIST_PATH . "/es-module-shims/dist/es-module-shims.js";
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
