<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_smarty_list()
{
    return [
        'smarty_notice_reporting' => [
            'name' => tra('Include Smarty notices in the PHP error report'),
            'hint' => tra('In most cases, Smarty notices can be safely ignored. However, they may be useful in the development process when unexpected issues occur.'),
            'type' => 'flag',
            'description' => tr('Tiki will display <a href="https://www.smarty.net">Smarty</a> notices with the PHP error messages.'),
            'default' => 'n',
        ],
        'smarty_security' => [
            'name' => tra('Smarty security'),
            'description' => tra('Enable/Disable Smarty security. If checked, you can then define allowed and disabled modifiers and tags(functions, blocks and filters) that should be or not accesible to the template.'),
            'warning' => tra('You should leave this on unless you know what you are doing.'),
            'type' => 'flag',
            'perspective' => false,
            'default' => 'y',
        ],
        'smarty_security_allowed_tags' => [
            'name' => tr('Allowed Smarty tags'),
            'description' => tr("This is a list of allowed tags. It's the list of (registered / autoloaded) function-, block and filter plugins that should be accessible to the template.
                                If empty, no restriction by allowed_tags. This may be needed for custom templates."),
            'warning' => tr('There may be security implications. Make sure you know what you are doing.'),
            'hint' => tr('Use "," to separate values'),
            'type' => 'text',
            'separator' => ',',
            'perspective' => false,
            'default' => '',
            'dependencies' => [
                'smarty_security',
            ],
        ],
        'smarty_security_disabled_tags' => [
            'name' => tr('Disabled Smarty tags'),
            'description' => tr("This is a list of disabled tags. It's the list of (registered / autoloaded) function-, block and filter plugins that may not be accessible to the template.
                                If empty, no restriction by disabled_tags. This may be needed for custom templates."),
            'warning' => tr('There may be security implications. Make sure you know what you are doing.'),
            'hint' => tr('Use "," to separate values'),
            'type' => 'text',
            'separator' => ',',
            'perspective' => false,
            'default' => '',
            'dependencies' => [
                'smarty_security',
            ],
        ],
        'smarty_security_allowed_modifiers' => [
            'name' => tr('Allowed Smarty modifiers'),
            'description' => tr("This is the list of allowed modifier plugins. It's the array of (registered / autoloaded) modifiers that should be accessible to the template.
                                If this array is non-empty, only the herein listed modifiers may be used. This is a whitelist. If empty, no restriction by allowed_modifiers. This may be needed for custom templates."),
            'warning' => tr('There may be security implications. Make sure you know what you are doing.'),
            'hint' => tr('Use "," to separate values'),
            'type' => 'text',
            'separator' => ',',
            'perspective' => false,
            'default' => '',
            'dependencies' => [
                'smarty_security',
            ],
        ],
        'smarty_security_disabled_modifiers' => [
            'name' => tr('Disabled Smarty modifiers'),
            'description' => tr("This is a list of disabled modifier plugins. It's the list of (registered / autoloaded) modifiers that may not be accessible to the template.
                                If empty, no restriction by disabled_modifiers. This may be needed for custom templates."),
            'warning' => tr('There may be security implications. Make sure you know what you are doing.'),
            'hint' => tr('Use "," to separate values'),
            'type' => 'text',
            'separator' => ',',
            'perspective' => false,
            'default' => '',
            'dependencies' => [
                'smarty_security',
            ],
        ],
        'smarty_security_dirs' => [
            'name' => tr('Extra Smarty directories'),
            'description' => tr('Make additional directories available as Smarty directories. This may be needed for custom icons (clear temp/cache after changing).'),
            'warning' => tr('There may be security implications. Make sure you know what you are doing.'),
            'type' => 'text',
            'separator' => ',',
            'perspective' => false,
            'default' => '',
            'dependencies' => [
                'smarty_security',
            ],
        ],
        'smarty_compilation' => [
            'name' => tra('Smarty compilation'),
            'description' => tra('Indicates when the template cache should be refreshed.'),
            'type' => 'list',
            'options' => [
                'modified' => tra('Modified'),
                'never' => tra('Never check (performance)'),
                'always' => tra('Always (development, slow)'),
            ],
            'default' => 'modified',
        ],
        'smarty_cache_perms' => [
            'name' => tra('Smarty cache permissions'),
            'description' => tra('Permissions that enable Smarty to write to the templates_c directory.'),
            'type' => 'list',
            'options' => [
                0644 => tra('User writable (0644)'),
                0664 => tra('User & group writable (0664)'),
            ],
            'default' => 0644,
            'keywords' => 'file perms templates',
        ],
    ];
}
