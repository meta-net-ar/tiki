<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_ajax_list()
{

    return [
        'ajax_autosave' => [
            'name' => tra('Ajax auto-save'),
            'description' => tra('Save content during editing, enabling work to be recovered after any interruption. Also enable a real-time preview. This option is required for WYSIWYG plugin processing.'),
            'help' => 'Lost-edit',
            'type' => 'flag',
            'dependencies' => [
                'feature_ajax',
                'feature_warn_on_edit',
            ],
            'default' => 'y',
        ],
        'ajax_inline_edit' => [
            'name' => tr('Inline editing'),
            'description' => tr('Enable inline editing of certain values. Currently limited to tracker item fields.'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'ajax_inline_edit_trackerlist' => [
            'name' => tr('Tracker list inline editing'),
            'description' => tr('Enable inline editing of all fields on the tracker list page.'),
            'type' => 'flag',
            'default' => 'y',
            'dependencies' => ['ajax_inline_edit'],
        ],
        'ajax_edit_previews' => [
            'name' => tr('Preview Edit Tabs in Text Areas'),
            'description' => tr('Enable edit and preview tabs in supported text areas.'),
            'type' => 'flag',
            'default' => 'n',
        ],
    ]; // TODO: improve help
}
