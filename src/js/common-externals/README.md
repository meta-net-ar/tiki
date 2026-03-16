# common-externals

This contains ESM libraries that are either:

* Used more than once, or anticipated to be used more than once (including indirectly by other dependencies)
* Big (it would take a lot of CPU and RAM to compile them in, with little benefit since they are big)

Some of these libraries are loaded in more than one way in tiki, but they are normally loaded by name directly in the browser through importmap using direct import statements.

* Present in [vite.config.mjs](../vite.config.mjs) / rollupOptions / external so they are not compiled in
* Present in [path_js_importmap_generator.php](../../../path_js_importmap_generator.php) to be auto-loaded by the browser by name

They should all be in rollupOptions / external in [vite.config.mjs](../vite.config.mjs).  As of 2026-03-09, they are notall there, which increases the size of tiki and slows down compilation - benoitg - 2026-03-09