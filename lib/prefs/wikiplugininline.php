<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_wikiplugininline_list($partial = false)
{
    $parserlib = TikiLib::lib('parser');

    $defaultInline = [
        'file' => 'y',
        'getaccesstoken' => 'y',
        'googleanalytics' => 'y',
        'group' => 'y',
        'grouplist' => 'y',
        'mail' => 'y',
        'perm' => 'y',
        'smarty' => 'y',
        'trackeritemfield' => 'y',
        'transclude' => 'y',
        'zotero' => 'y',
    ];

    if ($partial) {
        $out = [];
        $list = array_keys(WikiPlugin_Negotiator_Wiki::getWikipluginFromFiles());
        $alias = [];

        global $prefs;
        if (isset($prefs['pluginaliaslist'])) {
            $alias = @unserialize($prefs['pluginaliaslist']);
            $alias = array_filter($alias);
        }
        $list = array_filter(array_merge($list, $alias));
        sort($list);

        foreach ($list as $plugin) {
            $preference = 'wikiplugininline_' . $plugin;
            $out[$preference] = [
                'default' => isset($defaultInline[$plugin]) ? 'y' : 'n',
            ];
        }

        return $out;
    }

    $prefs = [];
    $inlineDescription = tr('Hide the edit plugin icon on wiki pages so this plugin renders as inline content without the visual edit UI.');

    foreach ($parserlib->plugin_get_list() as $plugin) {
        $info = $parserlib->plugin_info($plugin);

        $prefs['wikiplugininline_' . $plugin] = [
            'name' => tr('Inline plugin %0', $info['name']),
            'description' => $inlineDescription,
            'type' => 'flag',
            'help' => $info['documentation'] ?? ('Plugin' . $plugin),
            'default' => isset($defaultInline[$plugin]) ? 'y' : 'n',
        ];

        if (isset($info['tags'])) {
            $prefs['wikiplugininline_' . $plugin]['tags'] = (array) $info['tags'];
        }
    }

    return $prefs;
}
