<?php

/** @noinspection ALL */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/*
 * Created on Apr 7, 2009
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
namespace Tiki\Lib\Test\AcceptanceTests;

use TikiLib;

abstract class AbstractDBRestorer
/*
 * This class is invoked between automated tests, in order to restore the Tiki DB
 * to a given starting state. This avoids side effects between tests, which could
 * happen if a given test put data into the DB which would affect the success/failure
 * of subsequent tests.
 *
 * There are currently two subclasses that use different approaches for dumping the DB
 * and restoring it. We keep both of them for now, until we decide which of the two
 * is most appropriate.
 *
 * The SQLDumps approach uses files containing SQL statements that can be used to restore
 * the DB. It is slower, but possibly more reliable.
 *
 * The BinaryDumps approach uses the actual binary files of the DB. It is faster, but possibly
 * less reliable.
 */
{
    /**
     * @var string|bool
     */
    public $current_dir;

    protected $host = "localhost";

    protected $tiki_test_db = "tiki_db_for_acceptance_tests";
    protected $tiki_test_db_user = "tiki_automated_test_user";
    protected $tiki_test_db_pwd = "tiki_automated_test_user";

    protected $tiki_test_db_dump = "tiki_db_for_acceptance_tests_dump.sql";

    protected $mysql_data_dir = "";
    protected $tiki_schema_file_start = "dump_schema_tiki_start.txt";
    protected $tiki_restore_db_file_name = "tiki_testdb_restore_file.sql";
    protected $tiki_bare_bones_db_dump = "bareBonesDBDump.sql";


    public function __construct()
    {
        global $host_tiki, $user_tiki, $pass_tiki, $dbs_tiki;

        $this->host = $host_tiki;
        $this->tiki_test_db = $dbs_tiki;
        $this->tiki_test_db_user = $user_tiki;
        $this->tiki_test_db_pwd = $pass_tiki;

        $this->current_dir = getcwd();
        $this->mysql_data_dir = $this->setMysqlDataDir();
    }

    //This method can be called to create any dump file from a db.
    //Useful for creating dumps for diffent test db configurations
    abstract public function createDumpFile($dump_file);

    public function setMysqlDataDir()
    {
        $conn = mysqli_connect($this->host, $this->tiki_test_db_user, $this->tiki_test_db_pwd) or die(mysqli_error($conn));
        $result = mysqli_query($conn, "select @@datadir;");
        while ($array = mysqli_fetch_array($result)) {
            $datadir = $array[0];
        }
        return $datadir;
    }

    abstract public function checkIfDumpExists($dump_file);

    public function restoreDB($tiki_test_db_dump, $save_schema = false)
    {
        $begTime = microtime(true);
        $this->restoreDBDump($tiki_test_db_dump, $save_schema);
        $this->reinitializeInternalValuesAndClearCaches();
        echo "<pre>" . __METHOD__ . " DB restored in " . (microtime(true) - $begTime) . " seconds</pre>\n";
    }

    abstract public function restoreDBDump($tiki_test_db_dump, $save_schema = false);

    public function reinitializeInternalValuesAndClearCaches()
    {
        global $prefs;
        $tikilib = TikiLib::lib('tiki');
        $cachelib = TikiLib::lib('cache');

        initialize_prefs();
        $tikilib->cache_page_info = [];
        $cachelib->empty_cache();
    }


    public function printCallStack()
    {
        // Can't believe this is not standard in PHP!
        $backtrace = debug_backtrace();

        // Remove printCallStack() element from the stack, and print just the rest.
        array_shift($backtrace);
        foreach ($backtrace as $backtraceElement) {
            $line = "In File: " . $backtraceElement['file'] . ", at line: " . $backtraceElement['line'] . "\n";
            if (isset($backtraceElement['class'])) {
                $line .= $backtraceElement['class'] . "::";
            }
            $line .= $backtraceElement['function'] . "\n";
            echo $line;
        }
    }
}
