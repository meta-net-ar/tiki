<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\WikiPlugin\Enums\PluginParameterTags;

function wikiplugin_dialog_info()
{
    return [
        'name' => tra('Dialog'),
        'documentation' => 'PluginDialog',
        'validate' => 'all',
        'description' => tra('Create a custom popup dialog box'),
        'prefs' => [ 'wikiplugin_dialog' ],
        'iconname' => 'link-external',
        'introduced' => 8,
        'body' => [
            'description' => tra('Content to display in the dialog'),
            'depends' => [
                'field' => 'useWikiContent',
                'value' => 'n'
            ]
        ],
        'params' => [
            'title' => [
                'required' => false,
                'name' => tra('Title'),
                'description' => tra(''),
                'since' => '8.0',
                'filter' => 'text',
                'default' => '',
            ],
            'buttons' => [
                'required' => true,
                'name' => tra('Button Label'),
                'description' => tra('Use comma-separated values for multiple buttons'),
                'since' => '8.0',
                'filter' => 'text',
            ],
            'buttonsClassNames' => [
                'required' => false,
                'name' => tra('Button class names'),
                'description' => tra('Css class names to apply to the buttons. Use comma-separated values for multiple buttons.'),
                'since' => '28.0',
                'filter' => 'text',
            ],
            'buttonsActions' => [
                'required' => false,
                'name' => tra('Button Action'),
                'description' => tra('JavaScript to perform on button click. Use comma-separated values for multiple buttons'),
                'since' => '28.0',
                'filter' => 'text',
            ],
            'actions' => [
                'required' => false,
                'name' => tra('Buttons Actions'),
                'description' => tra('JavaScript to perform on button click. Use comma-separated values for multiple buttons'),
                'since' => '8.0',
                'filter' => 'text',
                'tag' => PluginParameterTags::Deprecated->value,
                'tagMessage' => tra('Do not use this parameter and the buttonsActions similtaneously. It is no longer recommended to use this parameter as it will be removed in future versions.'),
            ],
            'size' => [
                'required' => true,
                'name' => tra('Dialog size'),
                'description' => tra('Size of the modal dialog. Default is medium'),
                'since' => '28.0',
                'filter' => 'text',
                'default' => 'md',
                'options' => [
                    ['text' => tra('Small'), 'value' => 'sm'],
                    ['text' => tra('Medium'), 'value' => 'md'],
                    ['text' => tra('Large'), 'value' => 'lg'],
                    ['text' => tra('Extra Large'), 'value' => 'xl'],
                    ['text' => tra('Full Screen'), 'value' => 'fullscreen']
                ],
            ],
            'staticBackdrop' => [
                'required' => false,
                'name' => tra('Static Backdrop'),
                'description' => tra('If true, the modal will not close when clicking outside of the modal'),
                'since' => '28.0',
                'filter' => 'text',
                'default' => 'n',
                'options' => [
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ],
            ],
            'useWikiContent' => [
                'required' => false,
                'name' => tra('Use Wiki page Content'),
                'description' => tra('Use wiki page content as the dialog body'),
                'since' => '28.0',
                'filter' => 'alpha',
                'default' => 'n',
                'options' => [
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ],
                'profile_reference' => 'wiki_page',
                'tag' => PluginParameterTags::Experimental->value,
                'tagMessage' => tra("The usage of this field is still experimental, and doesn't strictly count as a dependency for the final plugin output.")
            ],
            'wiki' => [
                'required' => true,
                'name' => tra('Wiki Page'),
                'description' => tra('Wiki page to use as dialog body'),
                'since' => '8.0',
                'filter' => 'pagename',
                'default' => '',
                'profile_reference' => 'wiki_page',
                'depends' => [
                    'field' => 'useWikiContent',
                    'value' => 'y'
                ]
            ],
            'id' => [
                'required' => false,
                'advanced' => true,
                'name' => tra('HTML ID'),
                'description' => tr('Allowing to control the modal via JS. Automatically generated if left empty in the form (it has to be unique)'),
                'since' => '8.0',
                'filter' => 'text',
                'default' => '',
            ],
            'showAnim' => [
                'required' => false,
                'advanced' => true,
                'name' => tra('Fade In'),
                'description' => tra('Allow the modal to open with a fade in effect'),
                'since' => '8.0',
                'filter' => 'text',
                'default' => 'y',
                'options' => [
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ],
            ],
            'showCloseIcon' => [
                'required' => false,
                'advanced' => true,
                'name' => tra('Show Close Icon'),
                'description' => tra('Show a close icon in the header of the modal'),
                'since' => '28.0',
                'filter' => 'text',
                'default' => 'y',
                'options' => [
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ],
            ],
            'autoOpen' => [
                'required' => false,
                'advanced' => true,
                'name' => tra('Auto Open'),
                'description' => tra('Open the modal automatically'),
                'since' => '8.0',
                'filter' => 'alpha',
                'default' => 'n',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ],
            ],
            'openAction' => [
                'required' => false,
                'advanced' => true,
                'name' => tra('Open Action'),
                'description' => tra('JavaScript callback function to execute when dialog opens.'),
                'since' => '8.0',
                'filter' => 'rawhtml_unsafe',
                'default' => '',
            ],
        ],
    ];
}

function wikiplugin_dialog($data, $params)
{

    $defaults = [];
    $plugininfo = wikiplugin_dialog_info();
    foreach ($plugininfo['params'] as $key => $param) {
        $defaults["$key"] = $param['default'];
    }

    $params = array_merge($defaults, $params);

    $buttonsLabel = explode(',', $params['buttons']);
    $buttonsClasses = explode(',', $params['buttonsClassNames']);
    $buttonsAction = explode(',', $params['buttonsActions']);
    $actions = explode(',', $params['actions']);

    if (count($buttonsClasses) > count($buttonsLabel) || count($buttonsAction) > count($buttonsLabel)) {
        trigger_error('Buttons parameters do not match with the specified buttons', E_USER_WARNING);
    }

    $buttons = [];

    for ($i = 0; $i < count($buttonsLabel); $i++) {
        if (! $buttonsLabel[$i]) {
            continue;
        }

        $action = $buttonsAction[$i] ?: $actions[$i];

        $buttons[] = [
            'label' => $buttonsLabel[$i],
            'className' => $buttonsClasses[$i],
            'action' => $action
        ];
    }

    $smarty = TikiLib::lib('smarty');
    foreach ($params as $key => $value) {
        $smarty->assign($key, $value);
    }

    // We would check against the parameter 'useWikiContent' but, since Tiki 27 and earlier versions do not recognize this parameter, their content would have been broken.
    // At least, let's keep checking against the 'wiki' parameter until the new parameter is stable enough.
    $content = $params['wiki'] ? TikiLib::lib('wiki')->get_parse($params['wiki']) : $data;
    $id = $params['id'] ?: 'dialog-' . uniqid();
    $smarty->assign('content', $content);
    $smarty->assign('id', $id);
    $smarty->assign('buttons', $buttons);

    return $smarty->fetch('plugin/output/dialog.tpl');
}
