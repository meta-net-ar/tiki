<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function wikiplugin_param_info()
{
    return [
        'name' => tra('Parameter'),
        'documentation' => 'PluginParam',
        'description' => tra('Display content based on URL parameters'),
        'prefs' => [ 'wikiplugin_param' ],
        'body' => tr('Wiki text to display if conditions are met. The body may contain %0{ELSE}%1. Text after the
            marker will be displayed if conditions are not met.', '<code>', '</code>'),
        'iconname' => 'cog',
        'introduced' => 7,
        'params' => [
            'name' => [
                'required' => true,
                'name' => tra('Name'),
                'description' => tr('Names of parameters required to display text, separated by %0|%1.', '<code>', '</code>'),
                'since' => '7.0',
                'filter' => 'text',
                'separator' => '|',
            ],
            'source' => [
                'required' => false,
                'name' => tra('Source'),
                'default' => 'request',
                'description' => tra('Source where the parameter is checked.'),
                'since' => '7.0',
                'filter' => 'word',
                'options' => [
                    ['text' => tra('REQUEST'), 'value' => ''],
                    ['text' => tra('GET'), 'value' => 'get'],
                    ['text' => tra('POST'), 'value' => 'post'],
                    ['text' => tra('COOKIE'), 'value' => 'cookie'],
                ],
            ],
            'value' => [
                'required' => false,
                'name' => tra('Value'),
                'separator' => '|',
                'description' => tra('Value to test for. Multiple values can be separated by a pipe | character.' .
                    'If empty then just tests if the named params are set and not "empty".'),
                'since' => '13.1',
                'filter' => 'text',
            ],
            'filter' => [
                'required' => false,
                'name' => tra('Filter'),
                'separator' => '|',
                'description' => tra('Filter for the values.  Multiple values can be separated by a pipe | character.' .
                    'If empty then "text" is used".'),
                'since' => '27.6',
                'filter' => 'word',
                'default' => 'text',
                'advanced' => true,
            ],
        ]
    ];
}

function wikiplugin_param($data, $params)
{
    global $jitGet, $jitPost, $jitCookie, $jitRequest;

    $params = WikiPlugin_Helper::applySeparators($params, wikiplugin_param_info());

    $dataelse = '';
    $test = true;

    $parserlib = TikiLib::lib('parser');
    $noparsed = [];
    $parserlib->plugins_remove($data, $noparsed);

    if (str_contains($data, '{ELSE}')) {
        $dataelse = substr($data, strpos($data, '{ELSE}') + 6);
        $data = substr($data, 0, strpos($data, '{ELSE}'));
    }

    $defaults = [];
    $plugininfo = wikiplugin_param_info();
    foreach ($plugininfo['params'] as $key => $param) {
        $defaults["$key"] = $param['default'];
    }
    $params = array_merge($defaults, $params);

    if (count($params['name']) > 1) {
        if (! is_array($params['filter'])) {
            $params['filter'] = array_fill(0, count($params['name']), $params['filter']);
        }
    }

    foreach ($params['name'] as $i => $name) {
        $value = null;
        $filter = $params['filter'][$i];

        switch ($params['source']) {
            case 'get':
                $value = $jitGet->offsetExists($name) ? $jitGet->$name->filter($filter) : null;
                break;
            case 'post':
                $value = $jitPost->offsetExists($name) ? $jitPost->$name->filter($filter) : null;
                break;
            case 'cookie':
                $value = $jitCookie->offsetExists($name) ? $jitCookie->$name->filter($filter) : null;
                break;
            default:
                $value = $jitRequest->offsetExists($name) ? $jitRequest->$name->filter($filter) : null;
                break;
        }
        if (isset($params['value'])) {
            if (is_array($params['value'])) {
                if (! in_array($value, $params['value'])) {
                    $test = false;
                    break;
                }
            } elseif ($value !== $params['value']) {
                $test = false;
                break;
            }
        } elseif (empty($value)) {  // multiple "names" work as AND
            $test = false;
            break;
        }
    }

    if ($test) {
        $parserlib->plugins_replace($data, $noparsed);
        return $data;
    } else {
        $parserlib->plugins_replace($dataelse, $noparsed);
        return $dataelse;
    }
}
