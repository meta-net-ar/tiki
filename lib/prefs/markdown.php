<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_markdown_list()
{
    $wysiwygHelp = 'wysiwyg';

    return [
        'markdown_enabled' => [
            'name' => tr('Markdown'),
            'description' => tr('Support markdown syntax.'),
            'type' => 'flag',
            'default' => 'n',
            'help' => 'Tiki-Flavored-Markdown',
            'keywords' => 'Markdown',
            'tags' => ['advanced', 'experimental'],
        ],
        'markdown_gfm' => [
            'name' => tr('Github-flavored markdown'),
            'description' => tra('Enable Github-flavored markdown.'),
            'type' => 'flag',
            'default' => 'y',
            'tags' => ['advanced', 'experimental'],
            'dependencies' => [
                'markdown_enabled',
            ],
            'help' => 'https://github.github.com/gfm/',
        ],
        'markdown_default' => [
            'name' => tr('Default syntax'),
            'description' => tr('Which syntax to use as the default wiki syntax when a new content block is created.'),
            'type' => 'list',
            'options' => [
                'tiki' => tra('Tiki-style wiki syntax'),
                'markdown' => tra('Markdown'),
            ],
            'default' => 'tiki',
            'tags' => ['advanced', 'experimental'],
            'dependencies' => [
                'markdown_enabled',
            ],
            'help' => 'Markdown-Syntax#How_to_enable_Markdown_in_Tiki',
        ],
        'markdown_wysiwyg_height' => [
            'name' => tr('WYSIWYG Height'),
            'description' => tr('Vertical or tabbed.'),
            'type' => 'text',
            'size' => 5,
            'filter' => 'imgsize',
            'default' => '300px',
            'tags' => ['advanced', 'experimental'],
            'help' => $wysiwygHelp,
            'dependencies' => [
                'markdown_enabled',
                'feature_wysiwyg',
            ],
        ],
        'markdown_wysiwyg_preview_style' => [
            'name' => tr('WYSIWYG Preview Style'),
            'description' => tr('Vertical or tabbed.'),
            'type' => 'list',
            'help' => $wysiwygHelp,
            'options' => [
                'vertical' => tra('Vertical'),
                'tab' => tra('Tab'),
            ],
            'default' => 'tab',
            'tags' => ['advanced', 'experimental'],
            'dependencies' => [
                'markdown_enabled',
                'feature_wysiwyg',
            ],
        ],
        'markdown_wysiwyg_initial_edit_type' => [
            'name' => tr('WYSIWYG Initial Edit Mode'),
            'description' => tr('WYSIWYG or Markdown.'),
            'type' => 'list',
            'options' => [
                'wysiwyg' => tra('WYSIWYG'),
                'markdown' => tra('Markdown'),
            ],
            'default' => 'wysiwyg',
            'tags' => ['advanced', 'experimental'],
            'help' => $wysiwygHelp,
            'dependencies' => [
                'markdown_enabled',
                'feature_wysiwyg',
            ],
        ],
        'markdown_wysiwyg_usage_statistics' => [
            'name' => tr('Usage Statistics'),
            'description' => tra('Send hostname to Toast UI.'),
            'type' => 'flag',
            'default' => 'n',
            'tags' => ['advanced', 'experimental'],
            'help' => $wysiwygHelp,
            'dependencies' => [
                'markdown_enabled',
                'feature_wysiwyg',
            ],
        ]
    ];
}
