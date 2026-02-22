<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Lib\Wiki\PluginsLib;
use Tiki\Lib\Wiki\PluginsLibUtil;

require_once 'lib/wiki-plugins/wikiplugin_tikidocfromcode.php';

class WikiPluginPluginManager extends PluginsLib
{
    public function getDefaultArguments()
    {
        return [
                    'info' => 'description|parameters|paraminfo',
                    'type' => '',
                    'plugin' => '',
                    'module' => '',
                    'preference' => '',
                    'trackerfield' => '',
                    'prefsexport' => '',
                    'singletitle' => 'none',
                    'titletag' => 'h3',
                    'start' => '',
                    'limit' => '',
                    'paramtype' => '',
                    'showparamtype' => 'n',
                    'showtopinfo' => 'y'
                ];
    }
    public function getName()
    {
        return 'PluginManager';
    }
    public function getVersion()
    {
        return preg_replace("/[Revision: $]/", '', "\$Revision: 1.11 $");
    }
    public function getDescription()
    {
        return wikiplugin_pluginmanager_info()['description'];
    }
}

function wikiplugin_pluginmanager_info()
{
    return [
        'name' => tra('Plugin Manager'),
        'documentation' => 'PluginPluginManager',
        'description' => tra('List wiki plugin or module information for the site'),
        'prefs' => [ 'wikiplugin_pluginmanager' ],
        'introduced' => 1,
        'iconname' => 'plugin',
        'params' => [
            'info' => [
                'required' => false,
                'name' => tra('Information'),
                'description' => tr('Determines what information is shown. Values separated with %0|%1.
                    Ignored when %0singletitle%1 is set to %0top%1 or %0none%1.', '<code>', '</code>'),
                   'filter' => 'text',
                'accepted' => tra('One or more of: description | parameters | paraminfo'),
                'default' => 'description | parameters | paraminfo ',
                'since' => '1',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Description'), 'value' => 'description'],
                    ['text' => tra('Description and Source Code'), 'value' => 'description|sourcecode'],
                    ['text' => tra('Description and Parameters'), 'value' => 'description|parameters'],
                    ['text' => tra('Description & Parameter Info'), 'value' => 'description|paraminfo'],
                    ['text' => tra('Parameters & Parameter Info'), 'value' => 'parameters|paraminfo'],
                    ['text' => tra('All'), 'value' => 'description|parameters|paraminfo']
                ]
            ],
            'plugin' => [
                'required' => false,
                'name' => tra('Plugin'),
                'description' => tr('Name of a plugin (e.g., backlinks), or list separated by %0|%1, or range separated
                     by %0-%1. Single plugin can be used with %0limit%1 parameter.', '<code>', '</code>'),
                'filter' => 'text',
                'default' => '',
                'since' => '5.0',
            ],
            'module' => [
                'required' => false,
                'name' => tra('Module'),
                'description' => tr('Name of a module (e.g., calendar_new), or list separated by %0|%1, or range separated
                    by %0-%1. Single module can be used with %0limit%1 parameter.', '<code>', '</code>'),
                'filter' => 'text',
                'default' => '',
                'since' => '6.1',
            ],
            'preference' => [
                'required' => false,
                'name' => tra('Preference'),
                'description' => tr('Name of a preference (e.g., bigbluebutton_dynamic_configuration), or list separated by %0|%1, or range separated
                    by %0-%1. Single preference can be used with %0limit%1 parameter.', '<code>', '</code>'),
                'filter' => 'text',
                'default' => '',
                'since' => '27',
            ],
            'trackerfield' => [
                'required' => false,
                'name' => tra('Tracker field'),
                'description' => tr('Name of a tracker field, or list separated by %0|%1, or range separated
                    by %0-%1. Single preference can be used with %0limit%1 parameter.', '<code>', '</code>'),
                'filter' => 'text',
                'default' => '',
                'since' => '27',
            ],
            'prefsexport' => [
                'required' => false,
                'name' => tra('Preferences export'),
                'description' => tr('Default preferences export with columns name, description, location or list more separated by %0|%1.', '<code>', '</code>'),
                'filter' => 'text',
                'default' => '',
                'since' => '27',
            ],
            'type' => [
                'required' => false,
                'name' => tra('Type'),
                'description' => tr('Type of the object to get tiki doc from (e.g: plugin, module,  preference, ...)'),
                'filter' => 'text',
                'default' => '',
                'since' => '27',
            ],
            'singletitle' => [
                'required' => false,
                'name' => tra('Single Title'),
                'description' => tr('Set placement of plugin name and description when displaying information for only one plugin'),
                'filter' => 'alpha',
                'default' => 'none',
                'since' => '5.0',
                'options' => [
                    ['text' => tra(''), 'value' => ''],
                    ['text' => tra('Top'), 'value' => 'top'],
                    ['text' => tra('Table'), 'value' => 'table'],
                ],
            ],
            'titletag' => [
                'required' => false,
                'name' => tra('Title Heading'),
                'description' => tr('Sets the heading size for the title, e.g., %0h2%1.', '<code>', '</code>'),
                'filter' => 'alnum',
                'default' => 'h3',
                'since' => '5.0',
                'advanced' => true,
            ],
            'start' => [
                'required' => false,
                'name' => tra('Start'),
                'description' => tra('Start with this plugin record number (must be an integer 1 or greater).'),
                'filter' => 'digits',
                'default' => '',
                'since' => '5.0',
            ],
            'limit' => [
                'required' => false,
                'name' => tra('Limit'),
                'description' => tra('Number of plugins to show. Can be used either with start or plugin as the starting
                    point. Must be an integer 1 or greater.'),
                'filter' => 'digits',
                'default' => '',
                'since' => '5.0',
            ],
            'paramtype' => [
                'required' => false,
                'name' => tra('Parameter Type'),
                'description' => tr('Only list parameters with this %0doctype%1 setting. Set to %0none%1 to show only
                    parameters without a type setting and the body instructions.', '<code>', '</code>'),
                'since' => '15.0',
                'filter' => 'alpha',
                'default' => '',
                'advanced' => true,
            ],
            'showparamtype' => [
                'required' => false,
                'name' => tra('Show Parameter Type'),
                'description' => tr('Show the parameter %0doctype%1 value.', '<code>', '</code>'),
                'since' => '15.0',
                'filter' => 'alpha',
                'default' => '',
                'advanced' => true,
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'showtopinfo' => [
                'required' => false,
                'name' => tra('Show Top Info'),
                'description' => tr('Show information above the table regarding preferences required and the first
                    version when the plugin became available. Shown by default.'),
                'since' => '15.0',
                'filter' => 'alpha',
                'default' => '',
                'advanced' => true,
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
        ],
    ];
}

function wikiplugin_pluginmanager($data, $params)
{
    $plugin = new WikiPluginTikiDocFromCode();
    return $plugin->run($data, $params);
}
