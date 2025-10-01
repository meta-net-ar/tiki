# src/js directory

This is where all the javascript source code will eventually live, and where the [Vite](https://vitejs.dev/) build system lives.  It builds from here and into the [public/generated/js/](../../public/generated/README.md) repository at the root.

It's also involved in [src/scss](../scss/README.md) to build the legacy themes directory.

Much of the current documentation (aside from the README files) is currently in comments in the [vite.config.mjs](./vite.config.mjs) file.

This is run from the root as part of:

* npm run build
* npm run watch

## How is this organized

Top-level folder in src/js group code mostly by technical similarity.

* common-externals are "raw" javascript libraries that may be used more than once (regardless of how they are loaded, some are loaded in more than one way in tiki).
* vue-mf is a collection of (mostly) independent vue3 modules loaded as microfrontend.  Each module has is it's own package.json.
* jquery-tiki contains legacy tiki js scripts based on jquery (loaded by, or loaded into jquery), that were modernized to compile their dependencies into themselves.  It has a single package.json, and generates multiple .mjs ESM modules.
* ...

## Migrating dependencies

Javascript dependencies are still being moved from composer.  For examples of how to migrate (examples are up to date as of 2023-12-04 - benoitg):

1. Dependencies compiled-in.  
    * Ideal from a developer experience, simplest by far, but implies the javascript **using** them is already migrated in src/js.  
    * Applies to specific dependencies used from specific systems (possibly more than one), that will benefit from tree-shaking.
1. Common, large dependencies used a lot:  Make them available in common-externals, AND define them as externals in vite.config.js so they are not compiled in.  
    * Two variants:  as ESM module if available, or as normal scripts.
    * Js code in src/js and legacy js code can share them.
    * Example: See how vue.js is used
1. Include dependencies as native ESM modules, adding them to the importmap.
    * Requires the dependency to be distributed as a ESM module
    * Easy to maintain
    * Example: See how bootstrap's javascript is used.
1. Dependencies included as "normal" js files, like we used to do in composer:  This is the most direct migration path (technically), but (usually) the hardest to maintain.
    * Example:  See how jquery-ui is used.

## Migration steps

### 1- Add the dependency into the appropriate package.json

* Is it a common dependency type? In this case the appropriate package.json should be located in [common-externals](./common-externals/).
* Is it a dependency that is compiled into a specific module under src/js (i.e tiki-jquery)? In this case, the appropriate package.json should be located in a folder like [jquery-tiki](./jquery-tiki/), [vue-mf](./vue-mf/), 
   ...

> **Note:** Ensure the content stays alphabetically sorted in your chosen package.json.

Once the dependency is listed among installable packages, execute:

```sh
npm install
```

### 2- Tell the build system and/or the source about it

#### For common-externals

In [vite.config.mjs](vite.config.mjs):

1. Mention all files and/or folders that need to be copied by vite in viteStaticCopy/targets.  There is detailed documentation there

1. Add the module to rollupOptions/external:

   > **Note:** The files to copy are only files that you need and use from the package.  Typically a subset of the package's dist folder.  For example, you don't copy a file like README.md.

* Make the package available or importable as an ES Module if it's mainly distributed as one.
  * Edit the file [path_js_importmap_generator.php](/path_js_importmap_generator.php).
    * We are interested in the `imports` entry of the object `$importmap`.
    * Register the package where it alphabetically fits:

        ```php
        ...
        "imports" => [
            ...
            "my-package" => $tikiroot . NODE_PUBLIC_DIST_PATH . "/path/to/the/target/file",
            ...
        ]
        ```

  * Now the package can be imported and used in javascript as follows:

    ```js
    import { moduleName } from "my-package";
    ```

* If it's not distributed as an ES Module, an import to the destination folder of the package is necessary, usually done using headerlib.  For example:

    ```php
    $headerlib->add_jsfile(NODE_PUBLIC_DIST_PATH . "/path/to/the/target/file");
    ```

#### For a dependency added into another module only

The package can be imported and used in javascript as follows:

```js
import { moduleName } from "my-package";
```

### 3- Remove the dependency from composer.json

Then run

```sh
composer -d vendor_bundled install
```

to make sure the package is removed.

### 4- Check that everything works

Execute:

```sh
npm run watch
```

And test every feature where your changed the inclusion of the dependency (especially since we often upgrade the dependency at the same time)
