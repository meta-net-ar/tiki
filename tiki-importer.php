<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Lib\Importer\Base as BaseImporter;
use Tiki\Lib\Importer\BlogWordpress;
use Tiki\Lib\Importer\WikiMediawiki;

$inputConfiguration = [
    [
        'staticKeyFilters'                => [
        'importerClassName'               => 'text',              //post
        ],
    ],
];
require_once('tiki-setup.php');
require_once('lib/wiki/editlib.php');

$access->check_permission('tiki_p_admin_importer');

/**
 * $availableSoftwares is an array that control the list of available software importers.
 * The array key is the name of the importer class and the value is the name of the software
 */
$availableSoftwares = [
    WikiMediawiki::class => 'MediaWiki',
    BlogWordpress::class => 'WordPress',
];

if (! empty($_POST['importerClassName'])) {
    $importerClassName = filter_input(INPUT_POST, 'importerClassName', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    if (! isset($availableSoftwares[$importerClassName])) {
        Feedback::errorAndDie(tra("Invalid software name"), \Laminas\Http\Response::STATUS_CODE_400);
    }

    $importer = new $importerClassName();
    $smarty->assign('softwareName', $importer->softwareName);
    BaseImporter::changePhpSettings();
}

if (isset($_SESSION['tiki_importer_feedback'])) {
    $smarty->assign('importFeedback', $_SESSION['tiki_importer_feedback']);
    $smarty->assign('importLog', $_SESSION['tiki_importer_log']);
    $smarty->assign('importErrors', $_SESSION['tiki_importer_errors']);
    unset($_SESSION['tiki_importer_feedback']);
    unset($_SESSION['tiki_importer_log']);
    unset($_SESSION['tiki_importer_errors']);

    // wordpress specific
    if (isset($_SESSION['tiki_importer_wordpress_urls'])) {
        $smarty->assign('wordpressUrls', $_SESSION['tiki_importer_wordpress_urls']);
        unset($_SESSION['tiki_importer_wordpress_urls']);
    }
} elseif (! empty($_FILES['importFile']) && $access->checkCsrf()) {
    // third step: start the importing process

    if ($_FILES['importFile']['error'] === UPLOAD_ERR_OK) {
        try {
            $importer->import($_FILES['importFile']['tmp_name']);
        } catch (Exception $e) {
            Feedback::errorAndDie($e->getMessage(), \Laminas\Http\Response::STATUS_CODE_409);
        }
    } else {
        $msg = BaseImporter::displayPhpUploadError($_FILES['importFile']['error']);
        Feedback::errorAndDie($msg, \Laminas\Http\Response::STATUS_CODE_409);
    }

    die;
} elseif (! empty($_POST['importerClassName'])) {
    // second step: display import options for the software previously chosen
    if (! class_exists($importerClassName)) {
        Feedback::errorAndDie(tra("Invalid software name"), \Laminas\Http\Response::STATUS_CODE_400);
    }

    try {
        $importer->checkRequirements();
    } catch (Exception $e) {
        Feedback::errorAndDie($e->getMessage(), \Laminas\Http\Response::STATUS_CODE_409);
    }

    $importerOptions = $importer->getOptions();

    $smarty->assign('importerOptions', $importerOptions);
    $smarty->assign('softwareSpecificOptions', true);
    $smarty->assign('importerClassName', $importerClassName);
} else {
    // first step: display the list of available software importers

    $smarty->assign('availableSoftwares', $availableSoftwares);
    $smarty->assign('chooseSoftware', true);
}

$smarty->assign('mid', 'tiki-importer.tpl');
$smarty->display('tiki.tpl');
