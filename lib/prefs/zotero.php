<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function prefs_zotero_list()
{
    $zoteroHelp = 'Zotero';

    return [
        'zotero_enabled' => [
            'name' => tra('Zotero bibliography'),
            'help' => $zoteroHelp,
            'description' => tra('Connect Tiki to the <a href="https://www.zotero.org">Zotero</a> online bibliography management system (API mode, requires server connection).'),
            'type' => 'flag',
            'hint' => tr('API mode only. You must supply the following items: Zotero Client Key, Zotero Client Secret, Zotero Group, and Zotero Reference Style. For offline Pandoc mode, use the Pandoc preferences below.'),
            'default' => 'n',
            'tags' => ['basic'],
        ],
        'zotero_client_key' => [
            'name' => tra('Zotero client key'),
            'help' => $zoteroHelp,
            'description' => tra('Required identification key. Registration required.'),
            'type' => 'text',
            'size' => 20,
            'default' => '',
            'dependencies' => [
                'zotero_enabled',
            ],
        ],
        'zotero_client_secret' => [
            'name' => tra('Zotero client secret'),
            'help' => $zoteroHelp,
            'description' => tra('Required identification key. Registration required.'),
            'type' => 'text',
            'size' => 20,
            'default' => '',
            'dependencies' => [
                'zotero_enabled',
            ],
        ],
        'zotero_group_id' => [
            'name' => tra('Zotero group ID'),
            'help' => $zoteroHelp,
            'description' => tra('Numeric ID of the group, can be found in the URL.'),
            'type' => 'text',
            'filter' => 'digits',
            'size' => 7,
            'default' => '',
            'dependencies' => [
                'zotero_enabled',
            ],
        ],
        'zotero_style' => [
            'name' => tra('Zotero reference style'),
            'help' => $zoteroHelp,
            'description' => tra('Use an alternate Zotero reference style when formatting the references. The reference formats must be installed on the Zotero server.'),
            'type' => 'text',
            'filter' => 'text',
            'size' => 20,
            'default' => '',
            'dependencies' => [
                'zotero_enabled',
            ],
        ],
        'zotero_pandoc_enabled' => [
            'name' => tra('Enable Pandoc citations'),
            'help' => $zoteroHelp,
            'description' => tra('Enable {@citekey} syntax with Pandoc citeproc for CSL-styled citations. Works offline with exported JSON library. Independent from Zotero API mode above.'),
            'type' => 'flag',
            'default' => 'n',
            'tags' => ['basic'],
        ],
        'zotero_pandoc_library' => [
            'name' => tra('Pandoc library file'),
            'help' => $zoteroHelp,
            'description' => tra('Path to CSL-JSON file exported from Zotero (e.g., storage/zotero/My Library.json)'),
            'type' => 'text',
            'size' => 60,
            'default' => 'storage/zotero/My Library.json',
            'tags' => ['basic'],
            'dependencies' => [
                'zotero_pandoc_enabled',
            ],
        ],
        'zotero_pandoc_style' => [
            'name' => tra('Pandoc default citation style'),
            'help' => $zoteroHelp,
            'description' => tra('Default CSL style for Pandoc mode (without .csl extension, e.g., chicago-author-date)'),
            'type' => 'text',
            'size' => 30,
            'default' => 'chicago-author-date',
            'tags' => ['basic'],
            'dependencies' => [
                'zotero_pandoc_enabled',
            ],
        ],
        'zotero_pandoc_path' => [
            'name' => tra('Pandoc executable path'),
            'help' => $zoteroHelp,
            'description' => tra('Path to pandoc binary (leave as "pandoc" if in system PATH)'),
            'type' => 'text',
            'size' => 60,
            'default' => 'pandoc',
            'tags' => ['basic'],
            'dependencies' => [
                'zotero_pandoc_enabled',
            ],
        ],
    ];
}
