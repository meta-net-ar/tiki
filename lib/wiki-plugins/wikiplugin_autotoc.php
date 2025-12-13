<?php

function wikiplugin_autotoc_info()
{
    return [
        'name' => tra('Automatic Table of Contents'),
        'documentation' => 'PluginAutoToc',
        'description' => tra('Display a Table Of Contents in a wiki page'),
        'prefs' => [ 'wikiplugin_autotoc'],
        'iconname' => 'list-numbered',
        'introduced' => 23.0,
        'lateParse' => true,
        'tags' => [ 'experimental' ],
        'params' => [
            'activity' => [
                'required' => true,
                'name' => tr('Activity'),
                'description' => tr('Determine if the Table Of Contents will appear in the active page or not.'),
                'since' => '23.0',
                'filter' => 'alpha',
                'default' => 'yes',
                'options' => [
                    ['text' => tra('Yes'), 'value' => 'yes'],
                    ['text' => tra('No'), 'value' => 'no']
                ]
            ],
            'align' => [
                'required' => false,
                'name' => tra('Align'),
                'description' => tr('Position of the Table Of Contents, either right, left, page ("right" is the default and "page" will display the table of contents where it is placed in the wiki page.).'),
                'since' => '23.0',
                'filter' => 'alpha',
                'default' => 'right',
                'options' => [
                    ['text' => tra('Right'), 'value' => 'right'],
                    ['text' => tra('Left'), 'value' => 'left'],
                    ['text' => tra('Page'), 'value' => 'page']
                ],
            ],
            'levels' => [
                'required' => false,
                'name' => tra('Levels'),
                'description' => tr('Specify which levels you want to see in the TOC. Levels are integers (1 to 6) separated with colon. Example : <code>levels="1:2:3"</code> to show only level 1, 2 and 3 headers.'),
                'since' => '23.0',
                'filter' => 'text',
                'separator' => ':',
            ],
            'offset' => [
                'required' => false,
                'name' => tra('Offset'),
                'description' => tra('Offset of Table Of Contents. Offset default value is 15'),
                'since' => '23.0',
                'filter' => 'int',
                'default' => 15,
            ],
            'mode' => [
                'required' => false,
                'name' => tra('Mode'),
                'description' => tr('Change the display of the Table Of Contents for wiki pages to inline.'),
                'since' => '23.0',
                'filter' => 'alpha',
                'default' => 'off',
                'options' => [
                    ['text' => tra('Off'), 'value' => 'off'],
                    ['text' => tra('Inline'), 'value' => 'inline'],
                ],
            ],
            'title' => [
                'required' => false,
                'name' => tra('Title'),
                'description' => tra('Title for the Table Of Contents'),
                'since' => '23.0',
                'default' => '',
            ],
            'tabs' => [
                'required' => false,
                'name' => tr('Tabs'),
                'description' => tr('Determine if the table of contents includes the content of Tabs plugin or not.'),
                'since' => '25.0',
                'filter' => 'alpha',
                'default' => 'no',
                'options' => [
                    ['text' => tra('No'), 'value' => 'no'],
                    ['text' => tra('Yes'), 'value' => 'yes']
                ]
            ],
            'tabset_names' => [
                'required' => false,
                'name' => tra('Tabset names'),
                'description' => tr('If tabs = yes, determine the Tabset names that uses the plugin. Use comma separator. Example : <code>tabset_names="tab1,tab2,tab3"</code> to show only the headers included in the tab1, tab2 and tab3'),
                'since' => '25.0',
                'filter' => 'text',
                'separator' => ',',
            ],
            'tabset_panes' => [
                'required' => false,
                'name' => tra('Tabset panes'),
                'description' => tra('If tabs = yes, determine the Tabset panes that uses the plugin. Tabset panes are integers (1 to x) separated with comma. Example : <code>tabset_panes="1,2,3"</code> to show only 1, 2 and 3 headers of selected tabs names'),
                'since' => '25.0',
                'filter' => 'text',
                'separator' => ',',
            ],
        ]
    ];
}

function wikiplugin_autotoc($data, $params)
{
    global $prefs;
    $headerlib = TikiLib::lib('header');

    $currPage = $_REQUEST['page'] ?? '';
    if (
        ! empty($currPage) &&
        (! str_contains($_SERVER["SCRIPT_NAME"], "tiki-editpage.php")) &&
        (! str_contains($_SERVER["SCRIPT_NAME"], 'tiki-pagehistory.php'))
    ) {
        if ($params['tabs'] == 'yes') {
            if (is_null($params['tabset_names']) || is_null($params['tabset_panes'])) {
                Feedback::error(tra('Missing Tabs names (tabset_names) parameter: it must be filled in when the Tabs (tabs) parameter is set to yes'));
                return;
            }
        }

        if ($params['activity'] == 'yes') {
            $jqueryAutoToc['plugin_autoToc_activity'] = true;
            $jqueryAutoToc['plugin_autoToc_mode'] = $params['mode'] === 'inline' ?: 'off';
            $autotocPos = $params['align'];
            $jqueryAutoToc['plugin_autoToc_pos'] = $autotocPos;
            $jqueryAutoToc['plugin_autoToc_offset'] = $params['offset'];
            $jqueryAutoToc['plugin_autoToc_title'] = $params['title'];
            $jqueryAutoToc['plugin_autoToc_tabs'] = $params['tabs'];
            $jqueryAutoToc['plugin_autoToc_tabset_names'] = $params['tabset_names'] ?? '';
            $jqueryAutoToc['plugin_autoToc_tabset_panes'] = $params['tabset_panes'] ?? '';
            $jqueryAutoToc['plugin_autoToc_levels'] = $params['levels'] ?? '';
            $jq_autotoc = json_encode($jqueryAutoToc, JSON_UNESCAPED_SLASHES);
            $headerlib->add_js('var jqueryAutoToc = ' . $jq_autotoc . '"\n";');
            if ($prefs['wiki_auto_toc'] !== 'y' || $prefs['wiki_toc_default'] !== 'on') {
                $headerlib->add_jsfile('lib/jquery_tiki/autoToc.js');
            }
            if ($autotocPos == 'page') {
                $headerlib->add_css('
                    #autotoc ul li {
                        list-style: none;
                        position: relative;
                    }

                    #autotoc ul > li > a {
                        display: block;
                        font-size: 15px;
                    }

                    #autotoc ul > li > a:hover,
                    #autotoc ul > li > a:focus {
                        font-weight: bold;
                        padding-left: 6px;
                        text-decoration: none;
                        background-color: transparent;
                        border-left-width: 1px;
                        border-left-style: solid; // #0075ff;
                    }');

                return "<div id='autotoc'></div>";
            } else {
                $headerlib->add_css('
                    body {
                        position: relative;
                    }

                    #autotoc .nav > li > a {
                        display: block;
                        padding: 4px 20px;
                        font-size: 1.0rem;
                        font-weight: 500;
                    }

                    #autotoc .nav > li > a:hover,
                    #autotoc .nav > li > a:focus {
                        padding-left: 19px;
                        text-decoration: none;
                        background-color: transparent;
                        border-left-style: solid;
                        bordr-left-width: 1px; // #0075ff;
                    }

                    #autotoc .nav-link.active,
                    #autotoc .nav-link.active:hover,
                    #autotoc .nav-link.active:focus {
                        padding-left: 30px;
                        font-weight: bold;
                        background-color: transparent;
                        border-left-style: solid;
                        border-left-width: 2px;// solid #0075ff;
                    }

                    #autotoc .nav-link + ul {
                        display: none;
                        padding-bottom: 10px;
                    }

                    #autotoc .nav .nav > li > a {
                        padding-top: 1px;
                        padding-bottom: 1px;
                        padding-left: 30px;
                        font-size: 0.8rem;
                        font-weight: normal;
                    }

                    #autotoc .nav .nav > li > a:hover,
                    #autotoc .nav .nav > li > a:focus {
                        padding-left: 29px;
                    }

                    #autotoc .nav .nav > li > .active,
                    #autotoc .nav .nav > li > .active:hover,
                    #autotoc .nav .nav > li > .active:focus {
                        padding-left: 28px;
                        font-weight: 500;
                    }

                    #autotoc .nav-link.active + ul {
                        display: block;
                    }

                    @media screen and (max-width: 991px) {
                        .hidden {
                            display: none!important;
                        }
                    }');
            }
        }
    }
}
