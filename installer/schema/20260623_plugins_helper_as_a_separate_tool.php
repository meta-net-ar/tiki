<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @param $installer
 */
function upgrade_20260623_plugins_helper_as_a_separate_tool($installer)
{
    $installer->query(
        "UPDATE `tiki_preferences`
        SET `value` = TRIM(BOTH ',' FROM REPLACE(CONCAT(',', `value`, ','), ',help,', ',help,launchplugins,'))
        WHERE `name` LIKE 'toolbar\_%'
            AND CONCAT(',', `value`, ',') LIKE '%,help,%'
            AND CONCAT(',', `value`, ',') NOT LIKE '%,launchplugins,%'"
    );
}
