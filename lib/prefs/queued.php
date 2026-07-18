<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function prefs_queued_list()
{
    return [
        'queued_tasks_js_processing_disabled' => [
            'name' => tra('Disable Web Queue Processing using AJAX calls to trigger background task processing'),
            'description' => tra('When enabled, queued tasks will only be processed via CLI command (taskqueue:process). Web-based processing via JavaScript polling will be disabled, but status polling remains active.'),
            'type' => 'flag',
            'default' => 'n',
            'dependencies' => ['feature_queued_tasks'],
            'tags' => ['advanced'],
            // TODO: create the documentation
        ],
    ];
}
