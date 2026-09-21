<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_wysiwyg_info()
{
    global $prefs;

    return [
        'name' => 'WYSIWYG',
        'documentation' => 'PluginWYSIWYG',
        'description' => tra('Use a WYSIWYG editor to edit a section of content'),
        'prefs' => ['wikiplugin_wysiwyg'],
        'iconname' => 'wysiwyg',
        'introduced' => 9,
        'tags' => [ 'experimental' ], // Several important bugs, notably #6551 and serious #6476. Most bugs are probably not specific to the WYSIWYG *plugin* (see feature_wysiwyg). Chealer 2018-01-24
        'filter' => 'purifier',         /* N.B. uses htmlpurifier to ensure only "clean" html gets in */
        'format' => 'html',
        'body' => tra('Content'),
        'extraparams' => true,
        'params' => [
            'width' => [
                'required' => false,
                'name' => tra('Width'),
                'description' => tra('Minimum width for DIV. Default:') . ' <code>100px</code>',
                'since' => '9.0',
                'filter' => 'text',
                'default' => '100px',
            ],
            'height' => [
                'required' => false,
                'name' => tra('Height'),
                'description' => tra('Minimum height for DIV. Default:') . ' <code>300px</code>',
                'since' => '9.0',
                'filter' => 'text',
                'default' => '300px',
            ],
            'use_html' => [
                'required' => false,
                'name' => tra('Use HTML'),
                'description' => tr('By default, the body (content) of calls to the WYSIWYG plugin is interpreted according to the "Use Wiki syntax in WYSIWYG" (%0wysiwyg_htmltowiki%1) preference. By default, "Use HTML" is considered enabled if "Use Wiki syntax in WYSIWYG" is disabled, and vice versa.
                 This parameter allows overriding that preference if needed.', '<code>', '</code>'),
                'since' => '14.1',
                'filter' => 'alpha',
                'default' => $prefs['wysiwyg_htmltowiki'] == 'y' ? 'n' : 'y',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
        ],
    ];
}


function wikiplugin_wysiwyg($data, $params)
{
    global $page, $prefs, $user;
    static $execution = 0;

    global $wikiplugin_included_page;
    if (! empty($wikiplugin_included_page)) {
        $sourcepage = $wikiplugin_included_page;
    } else {
        $sourcepage = $page;
    }

    $contentIsHTML = ! ($params['use_html'] !== 'y');
    $html = TikiLib::lib('edit')->parseToWysiwyg($data, true, $contentIsHTML, [
        'page' => $sourcepage,
        'html_editor' => true,
        'allow_nested_html_editor_plugins' => ['mouseover'],
    ]);

    if (TikiLib::lib('tiki')->user_has_perm_on_object($user, $sourcepage, 'wiki page', 'tiki_p_edit')) {
        $class = "wp_wysiwyg";
        $exec_key = $class . '_' . ++$execution;
        $style = " style='min-width:{$params['width']};min-height:{$params['height']}'";

        $params['section'] = empty($params['section']) ? 'wysiwyg_plugin' : $params['section'];
        $params['_wysiwyg'] = 'y';
        $params['is_html'] = $contentIsHTML;
        $params['_is_html'] = $contentIsHTML;    // needed for toolbars
        $params['area_id'] = $exec_key;
        //$params['comments'] = true;

        if ($prefs['namespace_enabled'] == 'y' && $prefs['namespace_force_links'] == 'y') {
            $namespace = TikiLib::lib('wiki')->get_namespace($sourcepage);
            if ($namespace) {
                $namespace .= $prefs['namespace_separator'];
            }
        } else {
            $namespace = '';
        }
        $namespace = htmlspecialchars($namespace);

        $smarty = TikiLib::lib('smarty');

        $html = "<div id='$exec_key' class='{$class}'$style data-initial='$namespace' data-index='$execution' data-html='{$params['use_html']}' data-ticket='"
            . \SmartyTiki\FunctionHandler\Ticket::render(['mode' => 'get'], $smarty->getEmptyInternalTemplate()) . "'>" . $html . '</div>';

        $tools = json_encode(\SmartyTiki\FunctionHandler\Toolbars::render($params, $smarty->getEmptyInternalTemplate()), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS);
        $tools = addslashes($tools);

        ['lang' => $lang, 'filePath' => $langFilePath] = TikiLib::lib('wysiwyg')->getEditorLang();

        TikiLib::lib('header')->add_js_module(<<<JS
            import('@wysiwyg/plugin').then((module) => {
                module.default('{$exec_key}', JSON.parse('{$tools}'), '{$sourcepage}', {
                    lang: '{$lang}',
                    langFilePath: '{$langFilePath}',
                });
            })
        JS);
    }
    return $html;
}
