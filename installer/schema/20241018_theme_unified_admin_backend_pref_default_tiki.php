<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Installer\Installer;

/**
 * Set the default value on upgraded Tikis for theme_unified_admin_backend to 'y'
 *
 * @param Installer $installer
 */
function upgrade_20241018_theme_unified_admin_backend_pref_default_tiki($installer)
{
    $tikilib = TikiLib::lib('tiki');

    $tikilib->set_preference('theme_unified_admin_backend', 'y');
    $installer->preservePreferenceDefault('theme_unified_admin_backend', 'y');
}
