<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_signature_info()
{
    return [
        'name'          => tra('Signature'),
        'documentation' => 'PluginSignature',
        'description'   => tra('Allows to draw signatures'),
        'prefs'         => ['wikiplugin_signature'],
        'extraparams'   => true,
        'params'        => [
            'name'   => [
                'required'    => false,
                'name'        => tra('Name'),
                'description' => tra(
                    'Name of component.'
                ),
                'filter' => 'text',
            ],
            'width'  => [
                'required'    => false,
                'name'        => tra('Width'),
                'description' => tra(
                    'Signature image width (default is 400px)'
                ),
                'filter' => 'digits',
            ],
            'height' => [
                'required'    => false,
                'name'        => tra('Height'),
                'description' => tra(
                    'Signature image height (default is 200px)'
                ),
                'filter' => 'digits',
            ],
            'align' => [
                'required'    => false,
                'name'        => tra('Align'),
                'description' => tra(
                    'Signature image align in document (default is left)'
                ),
                'filter' => 'text',
            ],
            'editable_by_user' => [
                'required'    => false,
                'name'        => tra('Editable by user'),
                'description' => tra(
                    'Put some usernames that are allowed to edit this plugin'
                ),
                'filter' => 'text',
            ],
            'editable_by_groups' => [
                'required'    => false,
                'name'        => tra('Editable by groups'),
                'description' => tra(
                    'Put some groups that are allowed to edit this plugin'
                ),
                'filter' => 'text',
            ],
        ],
    ];
}

function wikiplugin_signature($data, $params)
{
    global $user, $page;

    $parserlib = TikiLib::lib('parser');

    static $signatureIndex = 0;
    ++$signatureIndex;

    $editPerm = TikiLib::lib('tiki')->user_has_perm_on_object(
        $user,
        $page,
        'wiki page',
        'tiki_p_edit'
    );

    if (! $editPerm) {
        // Checking permission from plugin
        $editPerm = $parserlib->check_permission_from_plugin_params($params) == 'y' ? true : false;
    }

    $headerlib = TikiLib::lib('header');
    $headerlib->add_jsfile(
        SIGNATURE_PAD_DIST_PATH . '/signature_pad.umd.min.js'
    );
    $headerlib->add_jsfile('lib/jquery_tiki/wikiplugin-signature.js', true);
    $headerlib->add_cssfile(
        THEMES_BASE_FILES_FEATURE_CSS_PATH . '/wikiplugin-signature.css'
    );

    $defaults = [
        'name' => $params['name'] ?? '',
        'editPerm' => $editPerm ? 1 : 0,
        'data' => $data,
        'height' => '200px',
        'width' => '400px',
        'align' => 'left',
        'index' => $signatureIndex,
    ];

    $params = array_merge($defaults, $params);

    if (! preg_match('/px$|%$/', trim($params['height']))) {
        $params['height'] = $params['height'] . 'px';
    }

    if (! preg_match('/px$|%$/', trim($params['width']))) {
        $params['width'] = $params['width'] . 'px';
    }

    $smarty = TikiLib::lib('smarty');
    $smarty->assign($params);

    $access = TikiLib::lib('access');
    $access->setTicket();
    $ticket = $access->getTicket();
    $smarty->assign('ticket', $ticket);
    $smarty->assign('pageName', $page);

    return'~np~' . $smarty->fetch('wiki-plugins/wikiplugin_signature.tpl') . '~/np~' ;
}
