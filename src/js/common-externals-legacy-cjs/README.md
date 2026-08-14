# common-externals-legacy-cjs

This contains older CJS format libraries that are either:

* Used more than once, or anticipated to be used more than once (including indirectly by other dependencies)
* Big (it would take a lot of CPU and RAM to compile them in, with little benefit since they are big)

They are normally loaded by path through headerlib through add_js_file().

They must be:

* Present in [vite.config.mjs](../vite.config.mjs) / rollupOptions / external so they are not compiled in.