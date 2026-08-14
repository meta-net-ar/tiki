<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//this script may only be included - so its better to die if called directly.
if (str_contains($_SERVER['SCRIPT_NAME'], basename(__FILE__))) {
    header('location: index.php');
    exit;
}

require_once('tiki-setup.php');

use Tiki\Lib\Socnets\PrefsGen;

$url = PrefsGen::getSocBaseUrl();
$smarty->assign('callbackUrl', $url);

$smarty->assign('socnetsAll', PrefsGen::getHybridProvidersPHP());
$smarty->assign('socBasePrefs', PrefsGen::getBasePrefs());
$smarty->assign('socPrefix', PrefsGen::getSocPrefix());

// legacy socialnetworks setup
require_once('lib/socialnetworkslib.php');
$url = $socialnetworkslib->getURL();
$url = substr($url, 0, strrpos($url, '/') + 1);
$smarty->assign('url', $url);
