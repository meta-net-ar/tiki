<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_pagetabs_help()
{
        return "Cutom Tabs Engine";
}

function wikiplugin_pagetabs_info()
{
    return [
        'name' => tra('Page Tabs'),
        'documentation' => tra('PluginPageTabs'),
        'description' => tra('Display the content of a wiki page in a set of tabs.'),
        'prefs' => [ 'wikiplugin_pagetabs' ],
        'iconname' => 'copy',
        'introduced' => 9,
        'body' => null,
        'params' => [
            'pages' => [
                'required' => true,
                'name' => tra('Wiki page names'),
                'description' => tr('The wiki pages you would like to use in this plugin, optional, separate with
                    pipe %0|%1. Or a table with the class of "pagetabs" on the main page. On child pages use as a way
                    to redirect to the parent.', '<code>', '</code>'),
                'since' => '9.0',
                'separator' => '|',
                'filter' => 'pagename',
                'profile_reference' => 'wiki_page',
            ],
        ],
    ];
}

function wikiplugin_pagetabs($data, $params)
{
    $smarty = TikiLib::lib('smarty');

    static $pagetabsindex = 0;
    ++$pagetabsindex;
    extract($params, EXTR_SKIP);

    $smarty->assign('id', $pagetabsindex);
    $smarty->assign('pages', $pages);
    $template = $smarty->fetch('plugin/output/pagetabs.tpl');

    return $template;
}
