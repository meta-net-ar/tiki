<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Installer\Installer;

/**
 * Set the default value on upgraded Tikis for theme_unified_admin_backend to 'n'
 *
 * @param Installer $installer
 */
function upgrade_20260312_change_fixed_width_default_tiki($installer)
{
    global $prefs;

    if ($prefs['feature_fixed_width'] === 'y') {
        // keep the default for upgrades if in use
        $installer->preservePreferenceDefault('layout_fixed_width', '1170px');
    }
}
