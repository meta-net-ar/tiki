<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Installer\Installer;

/**
 * Update existing sitemap:generate scheduler tasks to include the site URL,
 * fixing scheduler failure caused by the missing required url argument.
 *
 * @param Installer $installer
 * @return bool
 */
function upgrade_20260508_fix_sitemap_scheduler_url_tiki(Installer $installer): bool
{
    $url = TikiLib::tikiUrl();

    if (empty($url)) {
        return true;
    }

    $tasks = $installer->fetchAll(
        "SELECT id, params FROM tiki_scheduler WHERE task = 'ConsoleCommandTask'"
    );

    foreach ($tasks as $task) {
        $params = json_decode($task['params'], true);

        if (isset($params['console_command']) && $params['console_command'] === 'sitemap:generate') {
            $params['console_command'] = 'sitemap:generate ' . $url;
            $installer->query(
                "UPDATE tiki_scheduler SET params = ? WHERE id = ?",
                [json_encode($params), $task['id']]
            );
        }
    }

    return true;
}
