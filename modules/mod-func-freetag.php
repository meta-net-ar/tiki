<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Sections;

/**
 * @return array
 */
function module_freetag_info()
{
    return [
        'name' => tra('Tags Editor'),
        'description' => tra('Shows current tags and enables adding and removing some if permissions allow.'),
        'prefs' => ['feature_freetags'],
        'params' => []
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_freetag($mod_reference, $module_params)
{
    global $sections;
    $section = Sections::getCurrentSection();
    $smarty = TikiLib::lib('smarty');
    $modlib = TikiLib::lib('mod');

    $globalperms = Perms::get();
    if ($globalperms->view_freetags && isset($sections[$section])) {
        $tagid = 0;
        $par = $sections[$section];
        if (isset($par['itemkey']) && ! empty($_REQUEST["{$par['itemkey']}"])) {
            $tagid = $_REQUEST["{$par['itemkey']}"];
        } elseif (isset($par['key']) && ! empty($_REQUEST["{$par['key']}"])) {
            $tagid = $_REQUEST["{$par['key']}"];
        }
        if ($tagid) {
            $smarty->assign('viewTags', 'y');
        }
    } elseif ($modlib->is_admin_mode(true)) {
        $smarty->assign('viewTags', 'y');
    }
}
