<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
require_once('tiki-setup.php');
include_once('lib/wiki-plugins/wikiplugin_snarf.php');

if ($prefs['wikiplugin_snarf'] != 'y') {
    echo tra('Feature disabled');
    die;
}
echo TikiLib::lib('parser')->invokePlugin('snarf', '', $_REQUEST);
