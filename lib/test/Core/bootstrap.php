<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
define('TIKI_IN_TEST', 1);
require_once(__DIR__ . '/../TikiTestCase.php');

ini_set('display_errors', 'on');
error_reporting(E_ALL & ~E_DEPRECATED);

ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . "." . PATH_SEPARATOR . "../../core" . PATH_SEPARATOR . "../../..");

if (getenv('TIKI_TEST_SKIP_DB') === '1' && ! function_exists('tra')) {
    function tra($string)
    {
        return $string;
    }
}

require __DIR__ . '/../../../vendor_bundled/vendor/autoload.php';

$tikidomain = '';
$testLocal = __DIR__ . '/../local.php';
if (file_exists($testLocal)) {
    require $testLocal;
} else {
    require 'db/local.php';
}

if (getenv('TIKI_TEST_SKIP_DB') !== '1') {
    if (extension_loaded("pdo") && file_exists('db/tiki-db-pdo.php')) {
        require_once 'db/tiki-db-pdo.php';
    } else {
        require_once 'db/tiki-db.php';
    }

    $db = TikiDb::get();
}

if (getenv('TIKI_TEST_INIT_CACHE') === '1' && defined('TIKI_PATH') && class_exists('TikiLib')) {
    $pwd = getcwd();
    chdir(__DIR__ . '/../../../');
    try {
        $cachelib = TikiLib::lib('cache');
    } catch (\Throwable $e) {
        // ignore
    }
    chdir($pwd);
}
