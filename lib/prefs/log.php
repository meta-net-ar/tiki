<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_log_list()
{
    $generalPreferencesHelp = 'General-Preferences';

    return [
        'log_mail' => [
            'name' => tra('Log mail in Tiki logs'),
            'description' => tra('A line of type mail will be included in the System Log with the destination address and subject of each email sent.'),
            'type' => 'flag',
            'warning' => tra('May impact performance'),
            'help' => 'System-Log',
            'default' => 'y',
        ],
        'log_tpl' => [
            'name' => tra('Smarty template usage indicator'),
            'description' => tra('Add HTML comment at start and end of each Smarty template (.tpl file)'),
            'hint' => tra('Use only for development, not in production at a live site, because these warnings are added to emails as well, and are visible to users in the page source.'),
            'detail' => tra('Clear the Tiki system cache for this change to take effect.'),
            'type' => 'flag',
            'warning' => tra('May impact performance'),
            'default' => 'n',
            'help' => 'Miscellaneous',
        ],
        'log_sql' => [
            'name' => tra('Log SQL'),
            'description' => tra('All SQL queries will be registered in the database in the tiki_sql_query_logs table.'),
            'warning' => tra('Do not enable this feature all the time. It can be very resource intensive and will impact performance.'),
            'type' => 'flag',
            'default' => 'n',
            'help' => $generalPreferencesHelp,
        ],
        'log_failed_logins' => [
            'name' => tra('Log failed logins'),
            'description' => tra('Failed logins will be logged with respective login error code.'),
            'type' => 'flag',
            'default' => 'y',
            'help' => $generalPreferencesHelp,
        ],
        'log_file_path' => [
            'name' => tra('Action log file'),
            'description' => tra('Full path to the optional file for Tiki action-log entries. This is separate from the Monolog application log.'),
            'warning' => tra('Make sure that the path you insert is writable and exists.'),
            'type' => 'text',
            'default' => '',
            'help' => $generalPreferencesHelp,
        ],
        'log_sql_perf_min' => [
            'name' => tra('Log queries using more than'),
            'description' => tra('Use to log only queries that exceed a specific amount of time.'),
            'warning' => tra('May impact performance'),
            'type' => 'text',
            'units' => tra('seconds'),
            'size' => 5,
            'help' => $generalPreferencesHelp,
            'dependencies' => [
                'log_sql',
            ],
            'default' => '0.05',
        ],
        'log_tiki_enabled' => [
            'name' => tra('Enable Monolog application logging'),
            'description' => tra('Enable the shared PSR-3/Monolog logger for operational application events. This is separate from the Tiki action log.'),
            'type' => 'flag',
            'default' => 'n',
            'help' => 'log',
        ],
        'log_tiki_destination' => [
            'name' => tra('Monolog application log destination'),
            'description' => tra('Choose PHP configuration for the error-log destination or a file in the Tiki logs directory.'),
            'type' => 'list',
            'options' => [
                'php_error_log' => tra('PHP configured error log'),
                'file' => tra('Tiki logs directory'),
            ],
            'default' => 'file',
            'help' => 'log',
            'dependencies' => [
                'log_tiki_enabled',
            ],
        ],
        'log_tiki_file' => [
            'name' => tra('Monolog application log filename'),
            'description' => tra('Filename used inside the Tiki logs directory for Monolog application events.'),
            'type' => 'text',
            'default' => 'tiki.log',
            'help' => 'log',
            'dependencies' => [
                'log_tiki_enabled',
                'log_tiki_destination',
            ],
        ],
    ];
}
