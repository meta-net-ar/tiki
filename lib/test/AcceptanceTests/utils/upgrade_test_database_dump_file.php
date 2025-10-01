<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// Make sure script is run from a shell

use Tiki\Lib\Test\AcceptanceTests\DBRestorerSQLDumps;

if (PHP_SAPI !== 'cli') {
    die("Please run from a shell");
}

require_once __DIR__ . '/../../../../vendor_bundled/vendor/autoload.php';

//die ("WARNING: This script will destroy the current Tiki db. Comment out this line in the script to proceed.");

if ($argc != 2) {
    die("Missing argument. USAGE: $argv[0] <dump_filename>");
}

$test_TikiAcceptanceTestDBRestorer = new DBRestorerSQLDumps();
$test_TikiAcceptanceTestDBRestorer->restoreDB($argv[1]);

$local_php = 'db/local.php';

require_once('installer/installlib.php');
$dbTiki = null;

include $local_php;
try {
    $dbTiki = new PDO("mysql:host=$host_tiki;dbname=$dbs_tiki", $user_tiki, $pass_tiki);
} catch (Exception $e) {
    die(tra("Error while connecting using PDO" . $e->getMessage()));
}
$installer = Installer::getInstance();
$installer->update();

$test_TikiAcceptanceTestDBRestorer->createDumpFile($argv[1]);
