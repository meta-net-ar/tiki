<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Return module information
 *
 * @return array
 */
function module_git_detail_info()
{
    return [
        'name' => tra('Git information'),
        'description' => tra('Git commit and last update information.'),
        'params' => [],
    ];
}

/**
 * Collect information about current git repository and assign information
 * on smarty template engine
 *
 * @param $mod_reference
 * @param $module_params
 */
function module_git_detail($mod_reference, $module_params)
{
    /** @var \Tiki\Smarty\SmartyTiki $smarty */
    $smarty = TikiLib::lib('smarty');
    /** @var GitLib $gitlib */
    $gitlib = TikiLib::lib('git');

    $result = $gitlib->getGitDetails();

    $smarty->assign('error', $result['error']);
    $smarty->assign('content', $result['content']);
}
