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
        'name' => tra('Git detail'),
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
    $error = '';
    $content = [];
    include_once('lib/setup/twversion.class.php');
    $TWV = new TWVersion();
    $version = $TWV->getVersion();

    try {
        $content = $gitlib->get_info();
    } catch (Exception $e) {
        $error = $e->getMessage();
    } catch (Error $e) {
        $error = $e->getMessage();
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }

    if (empty($content)) {
        $content = [
            'version' => $version,
            'commit' => 'N/A',
            'date' => 'N/A',
            'branch' => 'N/A',
            'remote' => 'N/A',
        ];
    }

    $smarty->assign('error', $error);
    $smarty->assign('content', $content);
}
