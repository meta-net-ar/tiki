import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
import { resolve } from "path";
import { visualizer } from "rollup-plugin-visualizer";
import { viteStaticCopy } from "vite-plugin-static-copy";
import copy from "@guanghechen/rollup-plugin-copy";
import {glob} from "glob";
import path from "node:path";
import { fileURLToPath } from "node:url";
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
    /* Proof of concept.  Inspired by the documentation for input on https://rollupjs.org/configuration-options/#input but needs to be generalized further so it's not only used for jquery-tiki.  But it does work to generate stuff in subdirectories!

    We now need a function that computes everything from the glob - benoitg - 2023-11-10 */
    Object.assign(
        rollupInput,
        Object.fromEntries(
            glob.sync("src/js/jquery-tiki/*.js").map((file) => {
                //console.log(path.relative(__dirname, file));
                return [
                    // This remove `src/js/jquery-tiki` as well as the file extension from each
                    // file, so e.g. src/js/jquery-tiki/nested/foo.js becomes src/js/jquery-tiki/nested/foo
                    "jquery-tiki/" + path.relative("src/js/jquery-tiki", file.slice(0, file.length - path.extname(file).length)),
                    // This expands the relative paths to absolute paths, so e.g.
                    resolve(__dirname, path.relative(__dirname, file)),
                ];
            }),
        ),
    );
    Object.assign(rollupInput, {
        //Watch out, __dirname is the path of the config file, no matter how vite is called...
        "duration-picker": resolve(__dirname, "vue-mf/duration-picker/src/duration-picker.js"),
        "emoji-picker": resolve(__dirname, "vue-mf/emoji-picker/src/emoji-picker.js"),
        kanban: resolve(__dirname, "vue-mf/kanban/src/kanban.js"),
        "root-config": resolve(__dirname, "vue-mf/root-config/src/root-config.js"),
        styleguide: resolve(__dirname, "vue-mf/styleguide/src/styleguide.js"),
        "toolbar-dialogs": resolve(__dirname, "vue-mf/toolbar-dialogs/src/toolbar-dialogs.js"),
    });
    //console.log(rollupInput);
    return {
        base: "/public/generated/js", //This must NOT have a trailing slash
        publicDir: false, //tiki already uses public for other purposes.  If we want to use this feature we can create a src/public folder for it.
        resolve: {
            /*alias: {
            "@vue-mf/styleguide": resolve(__dirname, "vue-mf/styleguide/src/main.js"),
        },*/
        },
        build: {
            outDir: resolve(__dirname, "../../public/generated/js"),
            emptyOutDir: true,
            minify: mode === "production",
            sourcemap: mode === "production",
            cssCodeSplit: true,
            // emit manifest so PHP can find the hashed files
            manifest: true,
            target: "es2022", //https://caniuse.com/?search=es2022 Who cares about IE these days...
            optimizeDeps: {
                disabled: false,
                //If you ever need to debug a dependency and see your changes do this (ref: https://dev.to/hontas/using-vite-with-linked-dependencies-37n7):
                //exclude: ["svelte"],
            },
            rollupOptions: {
                // NOTE: Keep the list alphabetically sorted.
                external: [
                    /^@vue-mf\/.+/,
                    "@popperjs/core",
                    "bootstrap",
                    "clipboard",
                    "converse.js",
                    "dompurify",
                    "driver.js",
                    "jquery",
                    "jquery-ui",
                    "jquery-validation",
                    "moment",
                    "sortablejs",
                    "vue",
                ],
                //external: [/^@vue-mf\/.+/],
                input: rollupInput,
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
                    entryFileNames: "[name].js",
                },
                preserveEntrySignatures: "allow-extension",
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
                    /* Things to remember when adding to this list:
                    - The reason we copy these is that tiki must run without internet access, so we can't rely on CDNs.  But do try to keep the structure these packages have on CDNs.  Typically, that means copying the dist folder under dist.  To check quickly, look up the package on https://unpkg.com/ , you can then browse what is distributed for each version.
                    - We want to save space, so if there is multiple formats distributed, only pick one (typically ESM)
                    - For many modules that just means:
                        {
                            src: "node_modules/module-name/dist/*",",
                            dest: "vendor_dist/module-name/dist",
                        },
                    But make sure to look into the dist folder, so we don't add a bunch of useless stiff, but don't miss required support files (such as language files)
                    */

                    /* jquery_tiki */
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
                    /* module system */
                    {
                        src: "node_modules/es-module-shims/dist/es-module-shims.js",
                        dest: "vendor_dist/es-module-shims/dist",
                    },
                    /* tiki_themes */
                    {
                        src: "node_modules/@fortawesome/fontawesome-free/css/all.css",
                        dest: "vendor_dist/@fortawesome/fontawesome",
                    },
                    {
                        src: "node_modules/@fortawesome/fontawesome-free/webfonts/*",
                        dest: "vendor_dist/@fortawesome/webfonts",
                    },
                    /* common_externals */
                    {
                        src: "node_modules/animejs/lib/anime.es.js",
                        dest: "vendor_dist/anime/dist",
                    },
                    {
                        src: "node_modules/chart.js/dist/chart.js*",
                        dest: "vendor_dist/chart.js/dist",
                    },
                    {
                        src: "node_modules/chart.js/dist/chunks/helpers.segment.js*",
                        dest: "vendor_dist/chart.js/dist/chunks",
                    },
                    {
                        // This is an indirect runtime dependency of chart.js
                        src: "node_modules/@kurkle/color/dist/color.esm.js",
                        dest: "vendor_dist/@kurkle/color/dist",
                    },
                    {
                        src: "node_modules/clipboard/dist/*",
                        dest: "vendor_dist/clipboard/dist",
                    },
                    {
                        src: "node_modules/dompurify/dist/purify.(es|min)*",
                        dest: "vendor_dist/dompurify/dist",
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
                        src: "node_modules/moment/dist/*",
                        dest: "vendor_dist/moment/dist",
                    },
                    {
                        src: "node_modules/jquery/dist/*",
                        dest: "vendor_dist/jquery/dist",
                    },
                    {
                        src: "node_modules/jquery-ui/dist/*",
                        dest: "vendor_dist/jquery-ui/dist",
                    },
                    {
                        src: "node_modules/jquery-migrate/dist/*",
                        dest: "vendor_dist/jquery-migrate/dist",
                    },
                    {
                        src: "node_modules/jquery/dist/*",
                        dest: "vendor_dist/jquery/dist",
                    },
                    {
                        src: "node_modules/sortablejs/modular/*",
                        dest: "vendor_dist/sortablejs/modular",
                    },
                    {
                        src: "node_modules/vue/dist/vue.esm-browser.prod.js",
                        dest: "vendor_dist/vue/dist",
                    },
                    {
                        src: "node_modules/jquery-validation/dist/*",
                        dest: "vendor_dist/jquery-validation/dist",
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
                        src: "node_modules/converse.js/dist/emojis.js",
                        dest: "vendor_dist/converse.js/dist",
                    },
                ],
            }),
            copy({
                targets: [
                    {
                        //Theme assets
                        src: "_custom/**/themes/**/*.{woff,woff2,ttf,otf,svg,png,gif,jpg}",
                        dest: "public/generated/_custom",
                        flatten: false,
                        verbose: true,
                    },
                        //lang/ and js/ javascripts
                    {
                        src: "_custom/**/{lang,js}/**/*.{js,mjs}",
                        dest: "public/generated/_custom",
                        flatten: false,
                        verbose: true,
                    },
                ],
                //baseDir: "../../",
                silent: false,
                onWatch: true,
            }),
            /* Uncomment this in development to see which dependencies contribute to bundle size */
            //visualizer({ filename: "temp/dev/stats.html", open: true, gzipSize: false }),
        ],
    };
});
