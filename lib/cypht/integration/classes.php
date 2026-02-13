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

global $tikiroot;

define('VENDOR_PATH', __DIR__ . '/../../../' . TIKI_VENDOR_BUNDLED_PATH . '/'); //I presume VENDOR_PATH is a constant used by the cypht codebase - benoitg - 2026-01-25
define('APP_PATH', VENDOR_PATH . 'jason-munro/cypht/');
define('CONFIG_PATH', VENDOR_PATH . 'jason-munro/cypht/config/');
define('WEB_ROOT', 'vendor_bundled/vendor/jason-munro/cypht/');
define('DEBUG_MODE', false);
define('ASSETS_PATH', 'public/generated/assets/');

define('CACHE_ID', 'FoHc85ubt5miHBls6eJpOYAohGhDM61Vs%2Fm0BOxZ0N0%3D'); // Cypht uses for asset cache busting but we run the assets through Tiki pipeline, so no need to generate a unique key here
define('SITE_ID', 'Tiki-Integration');

require_once APP_PATH . 'lib/framework.php';
require_once __DIR__ . '/Tiki_Hm_Output_HTTP.php';
require_once __DIR__ . '/Tiki_Hm_Custom_Session.php';
require_once __DIR__ . '/Tiki_Hm_Custom_Auth.php';
require_once __DIR__ . '/Tiki_Hm_Tiki_Cache.php';
require_once __DIR__ . '/Tiki_Hm_Custom_Cache.php';
require_once __DIR__ . '/Tiki_Hm_Site_Config_File.php';
require_once __DIR__ . '/Tiki_Hm_User_Config.php';
require_once __DIR__ . '/Tiki_Hm_Sieve_Custom_Client.php';
require_once __DIR__ . '/Tiki_Hm_Sieve_Client_Factory.php';
require_once __DIR__ . '/Tiki_Hm_Functions.php';
require_once __DIR__ . '/Tiki_Hm_Dispatch.php';

putenv('WORKER_CUSTOM_IMPORTS=' . __DIR__ . '/Tiki_Hm_User_Config.php,' . realpath(__DIR__ . '/../../core/Cache/CacheLib.php') . ',' . __DIR__ . '/Tiki_Hm_Tiki_Cache.php,' . __DIR__ . '/Tiki_Hm_Custom_Cache.php');

$environment = Hm_Environment::getInstance();
$environment->load();
