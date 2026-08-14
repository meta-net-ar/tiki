<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_quote_info()
{
    return [
        'searchable_by_default' => true,
        'name' => tra('Quote'),
        'documentation' => 'PluginQuote',
        'description' => tra('Format text as a quote'),
        'prefs' => [ 'wikiplugin_quote' ],
        'body' => tra('Quoted text'),
        'iconname' => 'quotes',
        'introduced' => 1,
        'filter' => 'text',
        'tags' => [ 'basic' ],
        'format' => 'html',
        'params' => [
            'replyto' => [
                'required' => false,
                'name' => tra('Reply To'),
                'description' => tra('Name of the quoted person.'),
                'since' => '1',
                'filter' => 'text',
            ],
            'thread_id' => [
                'required' => false,
                'name' => tra('Thread Id for Forum replies'),
                'description' => tra('The thread Id of the comment being replied to in forums. Overwrites replyto'),
                'since' => '15',
                'filter' => 'text',
            ],
            'source_url' => [
                'required' => false,
                'name' => tra('Source URL'),
                'description' => tra('The URL to the source quoted.'),
                'since' => '16',
                'filter' => 'text',
            ],
            'date' => [
                'required' => false,
                'name' => tra('Date'),
                'description' => tra('Date when the statement quoted was made') . ' (<code>' . tr('YYYY-MM-DD') . '</code>)',
                'filter' => 'date',
                'since' => '16',
            ]
        ],
    ];
}

function wikiplugin_quote($data, $params)
{

    $source_url = '';
    $date = null;
    $replyto = '';
    $comment_info = '';

    if (! is_null($params['thread_id'])) {
        $comment_info = TikiLib::lib('comments')->get_comment($params['thread_id']);
        if ($comment_info) {
            $replyto = $comment_info['userName'];
        }
    } elseif (! is_null($params['replyto']) && $params['replyto']) {
        $replyto = $params['replyto'];
    }
    if (! is_null($params['source_url']) && $params['source_url']) {
        $source_url = $params['source_url'];
    }
    if (! is_null($params['date']) && $params['date']) {
        $date = strtotime($params['date']);
    }

    $smarty = TikiLib::lib('smarty');

    $smarty->assign('date', $date);
    $smarty->assign('comment_info', $comment_info);
    $smarty->assign('replyto', $replyto);
    $smarty->assign('wpQuoteContent', TikiLib::lib('parser')->parse_data_plugin($data));
    $smarty->assign('source_url', trim($source_url));

    return $smarty->fetch("wiki-plugins/wikiplugin_quote.tpl");
}
