import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
import { resolve } from "path";
import { visualizer } from "rollup-plugin-visualizer";
import { viteStaticCopy } from "vite-plugin-static-copy";
import { globSync } from "tinyglobby";
import path from "node:path";
import AutoImport from "unplugin-auto-import/vite";
import Components from "unplugin-vue-components/vite";
import { ElementPlusResolver } from 'unplugin-vue-components/resolvers';
import postcssRootToHost from "./postcssRootToHost";
/*

Overarching principles:

-  Don't break npm run watch from root.  It doesn't mean that HMR style no-reload works everywhere, but no code should ever have to be rebuild manually when changing a source file in an IDE.  An most reloading the page is sufficient.

-  Don't tie ourselves to tooling or a stack too deeply.  A lot of this is in flux.  npm workspaces are new.  Vite is maturing very quickly, but watching multiple codebases has no internal support.  So while we strive to have all dependencies synchronised, it must remain possible to run completely independent js codebases with single-spa (https://single-spa.js.org/docs/getting-started-overview) to orchestrate individual independent frontends.

- It's not workable to have entirely independent modules all with their own vite.config.  Aside from the fact some developers would run out of RAM locally, I did a prototype and while possible, it's EXTREMELY painful to share common configs, and real global HMR would never be a reality - benoitg - 2023-09-19

TODO Explain how to instantiate apps (see wikiplugin_kanban.tpl and importmap path_js_importmap_generator.php)

TODO: Explain how to add a new module.   (see path_js_importmap_generator.php)
Stack choices:

Stack choices:

CHOSEN:

- single-spa, DONE
- single-spa-vue, DONE
- single-spa-css  DONE, temporary.  We may have to write our own abstraction, it was designed for webpack.  It's a stopgap after using vite-plugin-single-spa failed

NOT CHOSEN
- vite-plugin-single-spa (https://github.com/WJSoftware/vite-plugin-single-spa) Pretty new (4 months), excellent overall,and we are kind of already committed to single-spa.  It allows transparently managing CSS for js, in effect replacing single-spa-css.  Unfortunately it doesn't handle multiple entry points properly, it's not compiling it's ex extension properly if more than one module use it. After EXTENSIVE testing, it seems unlikely we can ever use it as is.  It may get better in the future, but it also causes a lot of problems because of all it sets in vite config.  benoitg - 2023-09-19

- https://github.com/single-spa/self-hosted-shared-dependencies , we have that exact need, but it's better to do it manually, since we also use raw js files from php.

CONSIDERED

Module federation and https://github.com/originjs/vite-plugin-federation , it's essentially an alternative to importmaps, maybe it doesn't buy us much.  Claims to work with vite build --watch, but not with vite dev, so not great for the development experience considering the effort we put in having a single vite.config.mjs for most things.  But depending on where single-spa goes in the future, we may have to consider it again.  Especially since it can be used as a performance optimization:  https://single-spa.js.org/docs/recommended-setup/#module-federation

POSTPONED:

- Use https://vitejs.dev/config/shared-options.html#resolve-dedupe once it's fixed for ESM

- Figure out how to manage CSS with multiple entry points in a single vite project.  Awaiting movement on https://github.com/vitejs/vite/issues/12072#issuecomment-1793736497 Update:  A lot of this has now been implemented in https://github.com/vitejs/vite/pull/14945

Maybe obsolete: https://dev.to/hontas/using-vite-with-linked-dependencies-37n7  Use the Vite config option optimizeDeps.exclude when working with linked local dependencies.


DONE:

- Decide turborepo https://github.com/gajus/turbowatch#readme vs concurrently https://github.com/open-cli-tools/concurrently:  DONE:  Concurrently for now
- Integrate with setup.sh
- Migrate to workspaces IN PROGRESS
- Migrate twbs from composer, it's referenced by CSS (themes) and JS (BootstrapModal.vue) AND PHP (multiple places), so it's a good complete test.
- Migrate at least one of the tiki traditional javascript to an ESM module as an example.
- Get scss files compiling with dart-css, and remove them from git
IN PROGRESS:

TODO:

- Get index.php and htaccess fiels generated

- Test on windows

- Finish generating rollupInput below dynamically

- Replace viteStaticCopy

- Manage versions https://www.npmjs.com/package/check-dependency-version-consistency, this is not optional, having varying versions increase bundle sizes AND can cause serious problems.

- GET HMR and vite dev working.  vite dev is more important that HMR, as many developpers have slow machines and recompiline everything with vite build --watch is likely to take more than 10 seconds.   That will require (among other thing) generating import maps in to be able to use vite dev
 * There are Here is a drupal example https://www.drupal.org/project/vite.  Might be simpler to just rewrite base for vite dev server:  https://single-spa.js.org/docs/ecosystem-vite/, but that doesn't touch html and the like.
 * Other solution:  proxy:  https://vitejs.dev/config/server-options.html#server-proxy
 * Other solution:  see how vite-plugin-single-spa did it https://github.com/WJSoftware/vite-plugin-single-spa/commit/ed31833a7a9b7368c3227e6becbd02ac9585aab2

- Vite assumes everything is build by a single vite pass. a single manifest.json is build.  We could read the latter with https://packagist.org/packages/gin0115/vite-manifest-parser and try to generate an importmap dynamically in path_js_importmap_generator.phps
 * Use generated manifest from PHP https://vitejs.dev/guide/backend-integration.html ?

- Generate unique file names at build time and make it available to PHP so we don't need any cache busting mechanism and can eventually us long server cache times.

*/

/* GOTCHAS!

There are still issues with multiple entry point modules in vite.

While it's quickly improving, vite and rollup still occasionally make unfortunate assumptions that all modules are included.

Currently (2023-09-27), this is problematic for common CSS.  If input module1 and input module2 import (js import) css for library 1, only module 2 has the css in it's final build file.  This is especially confusing since if module 1 was developed before module 2, it works fine until module 2 is build.

*/

export default defineConfig(({ command, mode }) => {
    let rollupInput = {};

    addGlobEntries(rollupInput, [
        { prefix: "jquery-tiki/", src: "src/js/jquery-tiki" },
        { prefix: "common-reexported/", src: "src/js/common-reexported" },
        { prefix: "MOVE_THIS_CONTENT_ELSEWHERE/", src: "src/js/MOVE_THIS_CONTENT_ELSEWHERE" },
        { prefix: "@tiki/plugin-base/", src: "src/js/@tiki/plugin-base" },
        { prefix: "@tiki/plugins/", src: "src/js/@tiki/plugins" },
        { prefix: "@tiki/tracker-field-base/", src: "src/js/@tiki/tracker-field-base" },
        { prefix: "@tiki/tracker-fields/", src: "src/js/@tiki/tracker-fields" },
    ]);

    Object.assign(rollupInput, {
        //Watch out, __dirname is the path of the config file, no matter how vite is called...
        "avatar-generator": resolve(__dirname, "avatar-generator/index.js"),
        "color-picker": resolve("node_modules/@shoelace-style/shoelace/dist/components/color-picker/color-picker.js"),
        "duration-picker": resolve(__dirname, "vue-mf/duration-picker/src/duration-picker.js"),
        "emoji-picker": resolve(__dirname, "vue-mf/emoji-picker/src/emoji-picker.js"),
        "element-plus-ui/autocomplete": resolve(__dirname, "vue-widgets/element-plus-ui/src/elements/autocomplete.js"),
        "element-plus-ui/datepicker": resolve(__dirname, "vue-widgets/element-plus-ui/src/elements/datepicker.js"),
        "element-plus-ui/fileGalUploader": resolve(__dirname, "vue-widgets/element-plus-ui/src/elements/fileGalUploader.js"),
        "element-plus-ui/fileInput": resolve(__dirname, "vue-widgets/element-plus-ui/src/elements/fileInput.js"),
        "element-plus-ui/input": resolve(__dirname, "vue-widgets/element-plus-ui/src/elements/input.js"),
        "element-plus-ui/message": resolve(__dirname, "vue-widgets/element-plus-ui/src/elements/message.js"),
        "element-plus-ui/select": resolve(__dirname, "vue-widgets/element-plus-ui/src/elements/select.js"),
        "element-plus-ui/slider": resolve(__dirname, "vue-widgets/element-plus-ui/src/elements/slider.js"),
        "element-plus-ui/transfer": resolve(__dirname, "vue-widgets/element-plus-ui/src/elements/transfer.js"),
        "element-plus-ui/backtop": resolve(__dirname, "vue-widgets/element-plus-ui/src/elements/backtop.js"),
        "element-plus-ui/datepicker-root-css": resolve(__dirname, "vue-widgets/element-plus-ui/src/components/DatePicker/datePicker.scss"),
        "element-plus-ui/autocomplete-root-css": resolve(__dirname, "vue-widgets/element-plus-ui/src/components/Autocomplete/autocomplete.scss"),
        "element-plus-ui/input-root-css": resolve(__dirname, "vue-widgets/element-plus-ui/src/components/Input/input.scss"),
        "element-plus-ui/select-root-css": resolve(__dirname, "vue-widgets/element-plus-ui/src/components/Select/select.scss"),
        "tiki-html5-qrcode": resolve(__dirname, "tiki-html5-qrcode/index.js"),
        kanban: resolve(__dirname, "vue-mf/kanban/src/kanban.js"),
        "root-config": resolve(__dirname, "vue-mf/root-config/src/root-config.js"),
        "tiki-3d-model-viewer": resolve(__dirname, "tiki-model3dviewer/model3dviewer.js"),
        "tiki-glightbox": resolve(__dirname, "tiki-glightbox/glightbox-index.js"),
        "tiki-sentry-browser": resolve(__dirname, "tiki-sentry-browser/sentry-browser.js"),
        "tiki-figlet": resolve(__dirname, "tiki-figlet/figlet.js"),
        styleguide: resolve(__dirname, "vue-mf/styleguide/src/styleguide.js"),
        "tiki-offline": resolve(__dirname, "vue-mf/tiki-offline/src/tiki-offline.js"),
        "toolbar-dialogs": resolve(__dirname, "vue-mf/toolbar-dialogs/src/toolbar-dialogs.js"),
        "tracker-rules": resolve(__dirname, "vue-mf/tracker-rules/src/tracker-rules.js"),
        "tiki-iot-dashboard": resolve(__dirname, "tiki-iot/dashboard.js"),
        "tiki-iot-dashboard-all": resolve(__dirname, "tiki-iot/dashboard-all.js"),
        "tiki-mermaid": resolve(__dirname, "tiki-mermaid/mermaid.js"),
        "tiki-lottie": resolve(__dirname, "tiki-lottie/lottie.js"),
        "tiki-vue-sfc-loader": resolve(__dirname, "tiki-vue-sfc-loader/src/tiki-vue-sfc-loader.js"),
        "tiki-toast-ui": resolve(__dirname, "tiki-toast-ui/toast-index.js"),
        "@tiki/ui-utils": resolve(__dirname, "@tiki/ui-utils/index.js"),
        "wysiwyg/summernote": resolve(__dirname, "wysiwyg/summernote/index.js"),
        "wysiwyg/plugin": resolve(__dirname, "wysiwyg/plugin.js"),
    });
    return {
        base: "/public/generated/js", //This must NOT have a trailing slash
        publicDir: false, //tiki already uses public for other purposes.  If we want to use this feature we can create a src/public folder for it.
        resolve: {
            alias: {
                "moment-timezone": resolve(__dirname, "../../node_modules/moment-timezone/builds/moment-timezone-with-data-10-year-range.min.js"),
                "html2canvas": "html2canvas-pro"
            },
        },
        build: {
            outDir: resolve(__dirname, "../../public/generated/js"),
            emptyOutDir: true,
            // Allow large tools: SVGEdit, Avatar Generator, SFC Loader, Emoji Picker, 3D Viewer.
            chunkSizeWarningLimit: 2500,
            minify: mode === "production",
            sourcemap: mode === "production",
            cssCodeSplit: true,
            // emit manifest so PHP can find the hashed files
            manifest: true,
            target: "es2022", //https://caniuse.com/?search=es2022 Who cares about IE these days...
            optimizeDeps: {
                disabled: false,
                include: ["@event-calendar/core"],
                //If you ever need to debug a dependency and see your changes do this (ref: https://dev.to/hontas/using-vite-with-linked-dependencies-37n7):
                //exclude: ["svelte"],
            },
            rollupOptions: {
                // NOTE: Keep the list alphabetically sorted.
                external: [
                    /* src/js/common-reexported section
                    */
                    /^common-reexported\/.+/,

                    /* src/js/common-externals section
                    Loaded through path_js_importmap_generator.php or directly in inline modules
                    As of 2026-03-11, some modules are missing from this list.  They must be found, added here, and functionality checked - benoitg
                    */
                    "@dicebear/collection",
                    "@dicebear/core",
                    /^@jquery-tiki\/.+/,
                    "@lottiefiles/dotlottie-wc",
                    "@popperjs/core",
                    /^@vue-mf\/.+/,
                    /^@vue-widgets\/.+/,
                    "@wysiwyg/summernote",
                    "@zxing/library",
                    "bootstrap",
                    "clipboard",
                    "converse.js",
                    "dompurify",
                    "driver.js",
                    "html2canvas-pro",
                    "jquery",
                    "jquery-ui",
                    "jquery-validation",
                    "mermaid",
                    "moment",
                    /^moment\/.+/, //Moment submodules
                    "pivottablejs",
                    "reveal.js",
                    "sortablejs",
                    "subtotal",
                    "summernote",
                    "svgedit",
                    "three",
                    "underscore",
                    "vue",
                    "vue3-sfc-loader",

                    /* src/js/common-externals-legacy-cjs section
                    Loaded manually through headerlib add_js_file()
                    */
                    "swiper",
                    "tablesorter",
                ],
                input: rollupInput,
                maxParallelFileOps: 100, // TRy to workaround run watch getting stalled with no error.  See https://github.com/vitejs/vite/issues/19410 and https://github.com/rollup/rollup/issues/5848
                output: {
                    //dir: "./public/generated/js",
                    //file: "../../../storage/public/vue-mf/kanban/vue-mf-kanban.min.js",
                    //preserveModules: true,
                    //preserveModulesRoot: 'src/js/',
                    manualChunks: undefined,
                    format: "es",
                    //And this is super hard to integrate since this bug introduced in vite 4 https://github.com/vitejs/vite/issues/12072
                    //Maybe we can try the solution at the end of https://github.com/vitejs/vite/issues/4863
                    //It means we can't use hashing, and we need to name the entry point nameofmodule.js so we can have a nameofmodule.css file
                    //Can't use the hash untill we have deeper integration of manifest in php anyway
                    //assetFileNames: "[name]-assets/[name][extname]",
                    assetFileNames: (assetInfo) => {
                        //console.log(assetInfo);
                        return assetInfo.name;
                    },
                    entryFileNames: (chunkInfo) => {
                        // Check if the chunk is from the tiki-iot workspace
                        if (chunkInfo.name.startsWith("tiki-iot")) {
                            return "tiki-iot/[name].js";
                        }
                        return "[name].js";
                    },
                },
                preserveEntrySignatures: "allow-extension",
            },
        },
        css: {
            postcss: {
                plugins: [postcssRootToHost()],
            },
        },
        plugins: [
            vue({
                template: {
                    transformAssetUrls: {
                        base: "/public/generated/js/",
                    },
                },
            }),

            //These are re-bundled files that need to be read at runtime

            viteStaticCopy({
                //TODO: This object should really be imported from a file in common-externals
                //TODO: In development, this should be served directly from node_modules once we have vite dev server working
                //TODO IMPORTANT:  This does not check if the path exits (nor output what was done to the console).  This will INEVITABLY cause silent errors when package accidentally get duplicated or moved among node_modules in workspaces, or if there is any typo.  So we need something better than viteStaticCopy (https://www.npmjs.com/package/vite-plugin-static-copy)
                //Alternatives
                //https://www.npmjs.com/package/vite-plugin-watch-and-run (just a part of a solution)
                //https://github.com/knjshimi/vite-plugin-assets-watcher (quick library from someone hitting the same problem we do, but not very feature-rich).  May be worth forking.
                //rollup-plugin-copy (on which vite-plugin-static-copy was based) has had a pull request for years:                  https://github.com/vladshcherbin/rollup-plugin-copy/pull/30.  Fortunately, the fork is from after flatten=false was supported.
                //It's this fork:  https://www.npmjs.com/package/@guanghechen/rollup-plugin-copy
                //https://www.npmjs.com/package/rollup-plugin-copy-watch A different fork, exactly what we need, but unmaintained
                //https://stackoverflow.com/questions/63373804/rollup-watch-include-directory/63548394#63548394, brute force solution, but doesn't work for new files.
                //Interesting discussion: https://github.com/vitejs/vite/discussions/8364

                targets: [
                    /*
                    Essentially, this builds a common CDN repository
                    Things to remember when adding to this list:
                    - The reason we copy these is that tiki must run without internet access, so we can't rely on CDNs.  But do try to keep the structure these packages have on CDNs.  Typically, that means copying the dist folder under dist.  To check quickly, look up the package on https://unpkg.com/ , you can then browse what is distributed for each version.
                    - We want to save space, so if there is multiple formats distributed, only pick one (typically ESM)
                    - For many modules that just means:
                        {
                            src: "node_modules/module-name/dist/*",",
                            dest: "vendor_dist/module-name/dist",
                        },
                    But make sure to look into the dist folder, so we don't add a bunch of useless stuff, but don't miss required support files (such as language files)
                    */

                    /* Importmap polyfills used in path_js_importmap_generator module system */
                    {
                        src: "node_modules/es-module-shims/dist/es-module-shims.js",
                        dest: "vendor_dist/es-module-shims/dist",
                    },

                    // TODO: This is an indirect runtime dependency of chart.js.  So why isn't it made a direct dependency in common-externals if we have to copy files from it?  -  benoitg - 2026-03-09
                    {
                        src: "node_modules/@kurkle/color/dist/color.esm.js",
                        dest: "vendor_dist/@kurkle/color/dist",
                    },

                    /* BEGIN common_externals section
                    These should all ALSO be in rollupOptions / external */
                    {
                        src: "node_modules/@dicebear/*", //Both @@dicebear/core and @dicebear/collection.  They do not have a normal dist structure
                        dest: "vendor_dist/@dicebear",
                    },
                    {
                        src: "node_modules/@lottiefiles/dotlottie-wc/dist/*.js",
                        dest: "vendor_dist/@lottiefiles/dotlottie-wc/dist",
                    },
                    {
                        src: "node_modules/@shoelace-style/shoelace/dist/themes/*.css",
                        dest: "vendor_dist/@shoelace-style/shoelace/dist/themes",
                    },
                    {
                        src: "node_modules/@zxing/library/umd/index.min.js",
                        dest: "vendor_dist/@zxing/library/umd/index.min.js",
                    },
                    {
                        src: "node_modules/animejs/lib/anime.es.js",
                        dest: "vendor_dist/anime/dist",
                    },
                    {
                        src: "node_modules/chart.js/dist/chart.js*",
                        dest: "vendor_dist/chart.js/dist",
                    },
                    {
                        src: "node_modules/chart.js/dist/chunks/helpers.dataset.js",
                        dest: "vendor_dist/chart.js/dist/chunks",
                    },
                    {
                        src: "node_modules/clipboard/dist/*",
                        dest: "vendor_dist/clipboard/dist",
                    },
                    {
                        src: "node_modules/codemirror/lib/*",
                        dest: "vendor_dist/codemirror/lib",
                    },
                    {
                        src: "node_modules/codemirror/addon/search/searchcursor.js",
                        dest: "vendor_dist/codemirror/addon/search",
                    },
                    {
                        src: "node_modules/codemirror/addon/mode/*",
                        dest: "vendor_dist/codemirror/addon/mode",
                    },
                    {
                        src: "node_modules/codemirror/theme/*",
                        dest: "vendor_dist/codemirror/theme",
                    },
                    {
                        src: "node_modules/codemirror/mode/*",
                        dest: "vendor_dist/codemirror/mode",
                    },
                    {
                        src: "node_modules/converse.js/dist/*.min.*",
                        dest: "vendor_dist/converse.js/dist",
                    },
                    {
                        src: "node_modules/converse.js/dist/webfonts/*",
                        dest: "vendor_dist/converse.js/dist/webfonts",
                    },
                    {
                        src: "node_modules/converse.js/dist/sounds/*",
                        dest: "vendor_dist/converse.js/dist/sounds",
                    },
                    {
                        src: "node_modules/converse.js/dist/locales/*",
                        dest: "vendor_dist/converse.js/dist/locales",
                    },
                    {
                        src: "node_modules/converse.js/dist/emoji.json",
                        dest: "vendor_dist/converse.js/dist",
                    },
                    {
                        src: "node_modules/recordrtc/RecordRTC.js",
                        dest: "vendor_dist/recordrtc",
                    },
                    {
                        src: "node_modules/interactjs/dist/*",
                        dest: "vendor_dist/interactjs/dist",
                    },
                    {
                        src: ["node_modules/d3-milestones/build/d3-milestones.css", "node_modules/d3-milestones/build/d3-milestones.min.js"],
                        dest: "vendor_dist/d3-milestones/build",
                    },
                    {
                        src: "node_modules/dompurify/dist/purify.(es)*",
                        dest: "vendor_dist/dompurify/dist",
                    },
                    {
                        src: ["node_modules/drawflow/dist/drawflow.min.css"],
                        dest: "vendor_dist/drawflow/dist",
                    },
                    {
                        src: "node_modules/driver.js/dist/driver.js.mjs",
                        dest: "vendor_dist/driver.js/dist",
                    },
                    {
                        src: "node_modules/driver.js/dist/driver.css",
                        dest: "vendor_dist/driver.js/dist",
                    },
                    {
                        src: "node_modules/fitvids/dist/fitvids.js",
                        dest: "vendor_dist/fitvids/dist",
                    },
                    {
                        src: ["node_modules/fieldslinker/fieldsLinker.css", "node_modules/fieldslinker/fieldsLinker.js"],
                        dest: "vendor_dist/fieldslinker",
                    },
                    {
                        src: ["node_modules/glightbox/dist/css/glightbox.min.css"],
                        dest: "vendor_dist/glightbox/dist",
                    },
                    {
                        src: ["node_modules/plyr/dist/plyr.css"],
                        dest: "vendor_dist/glightbox/dist",
                    },
                    {
                        src: ["node_modules/plyr/dist/plyr.min.js"],
                        dest: "vendor_dist/glightbox/dist",
                    },
                    {
                        src: "node_modules/html2canvas-pro/dist/html2canvas-pro.esm.js",
                        dest: "vendor_dist/html2canvas-pro/dist",
                    },
                    {
                        src: "node_modules/interactjs/dist/*",
                        dest: "vendor_dist/interactjs/dist",
                    },
                    {
                        src: ["node_modules/gridstack/dist/gridstack.min.css", "node_modules/gridstack/dist/gridstack-extra.min.css"],
                        dest: "vendor_dist/gridstack/dist",
                    },
                    {
                        src: "node_modules/jqdoublescroll/jquery.doubleScroll.js",
                        dest: "vendor_dist/jqdoublescroll",
                    },
                    {
                        src: ["node_modules/jquery-treetable/css/jquery.treetable.css", "node_modules/jquery-treetable/jquery.treetable.js"],
                        dest: "vendor_dist/jquery-treetable",
                    },
                    {
                        src: "node_modules/jquery-zoom/(icon.png|jquery.zoom.js)",
                        dest: "vendor_dist/jquery-zoom",
                    },
                    {
                        src: "node_modules/jquery/dist/*",
                        dest: "vendor_dist/jquery/dist",
                    },
                    {
                        src: "node_modules/jquery-form/dist/*",
                        dest: "vendor_dist/jquery-form/dist",
                    },
                    {
                        src: "node_modules/jquery-migrate/dist/*",
                        dest: "vendor_dist/jquery-migrate/dist",
                    },
                    {
                        src: ["node_modules/jquery-tagcanvas/jquery.tagcanvas.min.js"],
                        dest: "vendor_dist/jquery-tagcanvas",
                    },
                    {
                        src: "node_modules/jquery-ui/dist/*",
                        dest: "vendor_dist/jquery-ui/dist",
                    },
                    {
                        src: "node_modules/jquery-validation/dist/*",
                        dest: "vendor_dist/jquery-validation/dist",
                    },
                    {
                        src: "node_modules/mermaid/dist/mermaid.esm.min.mjs",
                        dest: "vendor_dist/mermaid/dist",
                    },
                                        {
                        src: "node_modules/mermaid/dist/chunks/mermaid.esm.min/*",
                        dest: "vendor_dist/mermaid/dist/chunks/mermaid.esm.min",
                    },
                    {
                        src: "node_modules/moment/dist/*",
                        dest: "vendor_dist/moment/dist",
                    },
                    { //This is a nightmare.  It was added https://gitlab.com/tikiwiki/tiki/-/merge_requests/5950, out of alphabetical order.  But then https://gitlab.com/tikiwiki/tiki/-/merge_requests/7598 added it in setup.php on every page if fgal_use_record_rtc_screen is on!  Which would be bad enough, but it's not the same version as the rest of tiki uses above from the importmap.  benoitg - 2026-03-09
                        src: "node_modules/moment/min/*",
                        dest: "vendor_dist/moment/min",
                    },
                    {
                        src: "node_modules/ol/dist/ol.js",
                        dest: "vendor_dist/ol/dist",
                    },
                    {
                        src: "node_modules/ol/ol.css",
                        dest: "vendor_dist/ol",
                    },
                    {
                        src: ["node_modules/ol-layerswitcher/dist/ol-layerswitcher.js", "node_modules/ol-layerswitcher/dist/ol-layerswitcher.css"],
                        dest: "vendor_dist/ol-layerswitcher/dist",
                    },
                    {
                        src: "node_modules/plotly.js/dist/topojson*",
                        dest: "vendor_dist/plotly.js/dist/topojson",
                    },
                    {
                        src: "node_modules/plotly.js/dist/*.min.js",
                        dest: "vendor_dist/plotly.js/dist",
                    },
                    {
                        src: "node_modules/plotly.js/dist/plotly-locale*",
                        dest: "vendor_dist/plotly.js/dist",
                    },
                    {
                        src: "node_modules/plotly.js/dist/plot-schema.json",
                        dest: "vendor_dist/plotly.js/dist",
                    },
                    {
                        src: "node_modules/plotly.js/dist/plotly-geo-assets.js",
                        dest: "vendor_dist/plotly.js/dist",
                    },
                    {
                        src: "node_modules/reveal.js/dist/*",
                        dest: "vendor_dist/reveal.js/dist",
                    },
                    {
                        src: "node_modules/signature_pad/dist/signature_pad.umd.min.js",
                        dest: "vendor_dist/signature_pad/dist",
                    },
                    {
                        src: "node_modules/smartmenus/dist/*",
                        dest: "vendor_dist/smartmenus/dist",
                    },
                    {
                        src: "node_modules/sortablejs/modular/*",
                        dest: "vendor_dist/sortablejs/modular",
                    },
                    {
                        src: "node_modules/subtotal/dist/subtotal.min.js",
                        dest: "vendor_dist/subtotal/dist",
                    },
                                        {
                        src: "node_modules/svgedit/dist/editor/*",  //Could be pared down, but we do need most of the subdirectories
                        dest: "vendor_dist/svgedit/dist/editor",
                    },
                    {
                        src: [
                            "node_modules/swagger-ui-dist/swagger-ui-bundle.js",
                            "node_modules/swagger-ui-dist/swagger-ui.css",
                            "node_modules/swagger-ui-dist/favicon-16x16.png",
                            "node_modules/swagger-ui-dist/favicon-32x32.png",
                        ],
                        dest: "vendor_dist/swagger-ui-dist",
                    },
                    {
                        src: "node_modules/three/build/three.*.min.js",
                        dest: "vendor_dist/three/build",
                    },
                    {
                        src: "node_modules/vue/dist/vue.esm-browser.js",
                        dest: "vendor_dist/vue/dist",
                    },
                    {
                        src: "node_modules/vue3-sfc-loader/dist/vue3-sfc-loader.esm.js",
                        dest: "vendor_dist/vue3-sfc-loader/dist",
                    },
                    /* END common-externals section */

                    /* BEGIN src/js/common-externals-legacy-cjs section
                    These should all ALSO be in rollupOptions / external */
                    {
                        src: "node_modules/swiper/*.min.js",
                        dest: "vendor_dist/swiper",
                    },
                    {
                        src: "node_modules/swiper/*.min.css",
                        dest: "vendor_dist/swiper",
                    },
                    {
                        src: "node_modules/tablesorter/dist/js/jquery.tablesorter.combined.js",
                        dest: "vendor_dist/tablesorter/dist/js",
                    },

                    {
                        src: "node_modules/tablesorter/dist/js/parsers/parser-input-select.min.js",
                        dest: "vendor_dist/tablesorter/dist/js/parsers",
                    },
                    {
                        src: [
                            "node_modules/tablesorter/dist/js/widgets/widget-columnSelector.min.js",
                            "node_modules/tablesorter/dist/js/widgets/widget-filter-formatter-jui.min.js",
                            "node_modules/tablesorter/dist/js/widgets/widget-grouping.min.js",
                            "node_modules/tablesorter/dist/js/widgets/widget-math.min.js",
                            "node_modules/tablesorter/dist/js/widgets/widget-pager.min.js",
                            "node_modules/tablesorter/dist/js/widgets/widget-output.min.js",
                            "node_modules/tablesorter/dist/js/widgets/widget-sort2Hash.min.js",
                        ],
                        dest: "vendor_dist/tablesorter/dist/js/widgets",
                    },
                    {
                        src: "node_modules/underscore/underscore-esm-min.js",
                        dest: "vendor_dist/underscore",
                    },
                    /* END src/js/common-externals-legacy-cjs section */

                    /* src/js/jquery_tiki - These should be in common-externals* if they are not compiled in!  - benoitg - 2026-03-11 */
                    {
                        src: "node_modules/@event-calendar/core/dist/index.css",
                        dest: "vendor_dist/@event-calendar/core/dist",
                    },
                    {
                        src: "node_modules/boomerangjs/boomerang.js",
                        dest: "vendor_dist/boomerangjs/",
                    },
                    {
                        src: "node_modules/boomerangjs/plugins/rt.js",
                        dest: "vendor_dist/boomerangjs/plugins",
                    },
                    {
                        src: "node_modules/bootstrap/dist/css/bootstrap.min.*",
                        dest: "vendor_dist/bootstrap/dist/css",
                    },
                    {
                        src: "node_modules/bootstrap/dist/js/bootstrap.esm.min.js",
                        dest: "vendor_dist/bootstrap/dist/js",
                    },
                    {
                        src: "node_modules/@popperjs/core/dist/esm/*",
                        dest: "vendor_dist/@popperjs/core/dist/esm",
                    },
                    {
                        src: "node_modules/bootstrap-icons/font/*",
                        dest: "vendor_dist/bootstrap-icons/font",
                    },
                    {
                        src: ["node_modules/timeago/jquery.timeago.js", "node_modules/timeago/locales"],
                        dest: "vendor_dist/timeago/dist",
                    },

                    /**
                     * src/js/tiki-toast-ui
                     * Toast UI Editor: We place the CSS file here because inserting it directly into the toast-index.js file
                     * causes part of the editor to be styled while another part remains unstyled. Therefore, we preferred to use the CSS file directly
                     */
                    {
                        src: "node_modules/@toast-ui/editor/dist/toastui-editor.css",
                        dest: "vendor_dist/@toast-ui/editor/dist",
                    },
                    {
                        src: "node_modules/@toast-ui/editor/dist/i18n/*",
                        dest: "vendor_dist/@toast-ui/editor/dist/i18n/",
                    },
                    {
                        src: [
                            "node_modules/vis-timeline/dist/vis-timeline-graph2d.esm.js",
                            "node_modules/vis-timeline/dist/vis-timeline-graph2d.min.css",
                        ],
                        dest: "vendor_dist/vis-timeline/dist",
                    },

                    /* src/js/vue_widgets */
                    {
                        src: "node_modules/element-plus/dist/locale/*.min.mjs",
                        dest: "vendor_dist/element-plus/dist/locale",
                    },
                    {
                        src: [
                            "node_modules/element-plus/theme-chalk/base.css",
                            "node_modules/element-plus/theme-chalk/el-message.css",
                            "node_modules/element-plus/theme-chalk/el-message-box.css",
                            "node_modules/element-plus/theme-chalk/el-popper.css",
                            "node_modules/element-plus/theme-chalk/el-backtop.css",
                        ],
                        dest: "vendor_dist/element-plus/css",
                    },

                    /* src/js/wysiwyg  - These should be in common-externals* if they are not compiled in!  - benoitg - 2026-03-11 */
                    {
                        src: ["node_modules/summernote/dist/summernote-bs5.min.js", "node_modules/summernote/dist/summernote-bs5.min.css"],
                        dest: "vendor_dist/summernote/dist",
                    },
                    {
                        src: "node_modules/summernote/dist/font/*",
                        dest: "vendor_dist/summernote/dist/font",
                    },
                    {
                        src: "node_modules/summernote/dist/lang/*.min.js",
                        dest: "vendor_dist/summernote/dist/lang",
                    },

                    /* Assets for src/scss (theme system) */
                    {
                        src: "node_modules/@fortawesome/fontawesome-free/css/all.css",
                        dest: "vendor_dist/@fortawesome/fontawesome-free/css",
                    },
                    {
                        src: "node_modules/@fortawesome/fontawesome-free/webfonts/*",
                        dest: "vendor_dist/@fortawesome/fontawesome-free/webfonts",
                    },

                ],
            }),
            AutoImport({
                //dts: false,
                resolvers: [ElementPlusResolver()],
            }),
            Components({
                //dts: false,
                resolvers: [ElementPlusResolver()],
            }),
            /* Uncomment this in development to see which dependencies contribute to bundle size */
            //visualizer({ filename: "temp/dev/stats.html", open: true, gzipSize: false }),
        ],
        server: {
            watch: {
                //This is also used by vitest to exclude files from watch
                ignored: ["**/node_modules/**", "**/.git/**", "**/.gitlab-ci-local/**", "**/vendor_bundled/**","**/temp/**"],
            },
        },
        test: {
            include: ["src/js/**/tests/**/*.test.js"],
            globals: true,
            environment: "happy-dom",
            coverage: {
                include: [
                    "src/js/vue-widgets/**/*.{vue,js}",
                    "src/js/wysiwyg/**/*.js",
                    "src/js/avatar-generator/**/*.js",
                    "src/js/@tiki/ui-utils/handle*.js",
                ],
                exclude: ["**/*.ce.js", "**/*.test.js", "**/elements/**"],
                provider: "istanbul",
            },
            server: {
                deps: {
                    inline: [/element-plus/],
                },
            },
        },
    };
});

/**
 * Adds glob-based entries to rollupInput from multiple source folders.
 * @param {object} rollupInput - The rollupInput object to update.
 * @param {Array} entries - An array of objects with the following properties:
 *   - prefix: prefix used for the key names (e.g., "jquery-tiki/")
 *   - src: path to the source folder to scan (e.g., "src/js/jquery-tiki")
 */
function addGlobEntries(rollupInput, entries) {
    entries.forEach(({ prefix, src }) => {
        Object.assign(
            rollupInput,
            Object.fromEntries(
                //do NOT change this to /**/*.js, this is to implement an easy migration path for single file modules like the old lib/jquery-tiki
                globSync(`${src}/*.js`, {
                    ignore: ["**/node_modules/**", "**/*.test.js"]
                }).map(file => {
                    const relativePath = path.relative(src, file.slice(0, file.length - path.extname(file).length));
                    const fullPath = resolve(__dirname, path.relative(__dirname, file));
                    return [`${prefix}${relativePath}`, fullPath];
                })
            )
        );
    });
}
