# src/js/jquery-tiki

jquery-tiki contains legacy tiki js scripts based on jquery (loaded by, or loaded into jquery), that were modernized to compile their dependencies into themselves.  It has a single package.json, and generates multiple .mjs ESM modules.  It's meant to move:

* lib/jquery_tiki/*.js to src/js/jquery-tiki/*.js
* lib/jquery_tiki/tiki-jquery.js methods to their own files in src/js/jquery-tiki/*.js

It is most certainly NOT a place to put things to auto-compile just because you are too lazy to create a directory and add one line in vite.config.mjs.  

If you care enough about auto-compile that this is an issue for you, write the support to process [package.json main](https://docs.npmjs.com/cli/v11/configuring-npm/package-json#main) and fallback to index.js recursively under src/js in vite.config.mjs

* It should only contain things that are are loaded into jquery, and only depend on common-* or compiled in dependencies.
* It is tolerated that it contains things that are loaded from jquery (jquery selectors to attach event handlers), so that legacy jquery_tiki modules are as drop in as possible.  
  * But that is not tolerated for new NOT FOR NEW CODE.  For new code, use [normal addEventListener](https://developer.mozilla.org/en-US/docs/Web/API/EventTarget/addEventListener#examples)

Again, the module just depending on jquery (ex using jquery selectors) or using jquery in some other way (except the above) isn't a reason to put it here!
