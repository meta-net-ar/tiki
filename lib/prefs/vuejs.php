<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_vuejs_list()
{
    return [
        'vuejs_build_mode' => [
            'name'        => tra('Vue.js Deployment Mode'),
            'description' => tra('Selects which Vue.js build is served to the browser. Use "Production" on live/staging sites to eliminate console warnings and improve performance. Use "Development" locally for better error messages and Vue Devtools support.'),
            'type'        => 'list',
            'options'     => [
                'production'  => tra('Production (optimized, no warnings)'),
                'development' => tra('Development (verbose errors, Vue Devtools)'),
            ],
            'default'     => 'production',
            'tags'        => ['basic'],
            'keywords'    => 'vue js vuejs vue_js build mode production development',
        ],
    ];
}
