<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$inputConfiguration = [
    [
        'staticKeyFilters'         => [
             'repID'               => 'int',          //post
             'name'                => 'alpha',        //post
             'start'               => 'alpha',        //post
             'cssfile'             => 'alpha',        //post
             'expiration'          => 'int',          //post
             'description'         => 'xss',          //post
             'vis'                 => 'bool',         //post
             'cacheable'           => 'bool',         //post
             'action'              => 'striptags',    //post
             'save'                => 'striptags',    //post
        ],
    ],
];
require_once('tiki-setup.php');
require_once('lib/integrator/integrator.php');
// If Integrator is ON, check permissions...
$access->check_feature('feature_integrator');
$access->check_permission(['tiki_p_admin_integrator']);

// Setup local variables from request or set default values
$repID = $_REQUEST['repID'] ?? 0;
$name = $_REQUEST['name'] ?? '';
$path = $_REQUEST['path'] ?? '';
$start = $_REQUEST['start'] ?? '';
$cssfile = $_REQUEST['cssfile'] ?? '';
$expiration = ! empty($_REQUEST['expiration']) ? $_REQUEST['expiration'] : 0;
$description = $_REQUEST['description'] ?? '';
$vis = isset($_REQUEST['vis']) ? ($_REQUEST['vis'] == 'on' ? 'y' : 'n') : 'n';
$cacheable = isset($_REQUEST['cacheable']) ? ($_REQUEST['cacheable'] == 'on' ? 'y' : 'n') : 'n';

// Create instance of integrator
$integrator = new TikiIntegrator();

// Check if 'submit' pressed ...
if (isset($_REQUEST['save'])) {
    // ... and all mandatory paramaters r OK
    if (strlen($name) > 0) {
        if (! is_int($expiration) || $expiration < 0) {
            Feedback::errorAndDie(tra("Cache expiration must be an integer and greater than 0."), \Laminas\Http\Response::STATUS_CODE_409);
        }
        $integrator->add_replace_repository($repID, $name, $path, $start, $cssfile, $vis, $cacheable, $expiration, $description);
    } else {
        Feedback::errorAndDie(tra("Repository name can't be an empty"), \Laminas\Http\Response::STATUS_CODE_409);
    }
}

// Whether some action requested?
if (isset($_REQUEST['action'])) {
    switch ($_REQUEST['action']) {
        case 'edit':
            if ($repID != 0) {
                $rep = $integrator->get_repository($repID);
                $smarty->assign('repID', $repID);
                $smarty->assign('name', $rep['name']);
                $smarty->assign('path', $rep['path']);
                $smarty->assign('start', $rep['start_page']);
                $smarty->assign('cssfile', $rep['css_file']);
                $smarty->assign('expiration', $rep['expiration']);
                $smarty->assign('vis', $rep['visibility']);
                $smarty->assign('cacheable', $rep['cacheable']);
                $smarty->assign('description', $rep['description']);
            }
            break;

        case 'rm':
            if ($repID != 0 && $access->checkCsrf()) {
                $integrator->remove_repository($repID);
            }
            break;

        case 'clear':
            if ($repID != 0) {
                $integrator->clear_cache($repID);
            }
            header('location: ' . $_SERVER['SCRIPT_NAME'] . '?action=edit&repID=' . $repID);
            exit;

        default:
            Feedback::errorAndDie(tra('Requested action is not supported on repository'), \Laminas\Http\Response::STATUS_CODE_500);
    }
}

// Fill list of repositories
$repositories = $integrator->list_repositories(false);
$smarty->assign_by_ref('repositories', $repositories);
// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
// Display the template
$smarty->assign('mid', 'tiki-admin_integrator.tpl');
$smarty->display('tiki.tpl');
