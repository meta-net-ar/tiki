<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Installer\Installer;

/**
 * Update existing toolbar preferences to include the launchplugins tool alongside the help tool, if not already present.
 *
 * @param Installer $installer
 */
function upgrade_20260624_plugins_help_as_a_separate_tool_tiki(Installer $installer)
{
    $res = $installer->query("SELECT `name`, `value` FROM `tiki_preferences` WHERE `name` LIKE 'toolbar\_%'");
    while ($row = $res->fetchRow()) {
        $name = $row['name'];
        $value = $row['value'];

      // Check if 'help' is present and 'launchplugins' is not present
        if (preg_match('/(^|[|,])\s*help\s*([|,]|$)/', $value) && ! preg_match('/(^|[|,])\s*launchplugins\s*([|,]|$)/', $value)) {
          // Add 'launchplugins' alongside 'help'
            $newValue = preg_replace('/(^|[|,])\s*help\s*([|,]|$)/', '$1help,launchplugins$2', $value);
            $installer->query("UPDATE `tiki_preferences` SET `value` = ? WHERE `name` = ?", [$newValue, $name]);
        }
    }
}
