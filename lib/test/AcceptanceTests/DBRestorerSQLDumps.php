<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\AcceptanceTests;

use Tiki\Installer\Installer;

class DBRestorerSQLDumps extends AbstractDBRestorer
{
    public $current_dir;
    /*
     * This subclass uses SQL dumps of the DB to create DB snapshots and restore them.
     * It tries to only restore those tables that have changed since the last time
     * the snapshot was restored.
     */

    public function __construct()
    {
        parent::__construct();

        // enable this to run from the tiki temp folder as a cache dir
        if (realpath(__DIR__ . '/../../../temp')) {
            $this->mysql_data_dir = realpath(__DIR__ . '/../../../temp') . '/testcache/';
            if (! is_dir($this->mysql_data_dir)) {
                mkdir($this->mysql_data_dir);
            }
        }
    }

    public function checkIfDumpAndSchemaStartFilesExist($dump_file)
    {
        if (
            $this->checkIfDumpExists($dump_file) &&
                $this->checkIfDumpExists($dump_file . "_" . $this->tiki_schema_file_start)
        ) {
            return true;
        }
    }

    public function checkIfDumpExists($dump_file)
    {
        chdir($this->mysql_data_dir);
        if (file_exists($dump_file)) {
            chdir($this->current_dir);
            return true;
        }
        chdir($this->current_dir);
    }

    //This method can be called to create any dump file from a db.
    //Useful for creating dumps for diffent test db configurations
    public function createDumpFile($dump_file)
    {
        chdir($this->mysql_data_dir);
        //          echo "\nDumping the whole tiki database: ";
        //          $begTime = microtime(true);

        $mysqldump_command_line = "MYSQL_PWD=$this->tiki_test_db_pwd mysqldump --host=$this->host --user=$this->tiki_test_db_user $this->tiki_test_db > $dump_file";
        shell_exec($mysqldump_command_line);
        //          echo (microtime(true) -$begTime)." sec\n";
        chdir($this->current_dir);
        return true;
    }

    //Creates start schema files from the test db
    public function createStartSchemaFiles()
    {
        chdir($this->mysql_data_dir);
        //          echo "\n\rDumping start tables and times from information_schema: ";
        //          $begTime = microtime(true);
        $mysql_select_from_schema_command = "echo select TABLE_NAME,UPDATE_TIME from information_schema.TABLES WHERE TABLE_SCHEMA=\'$this->tiki_test_db\' | MYSQL_PWD=$this->tiki_test_db_pwd mysql --host=$this->host --user=$this->tiki_test_db_user > $this->tiki_schema_file_start";
        exec($mysql_select_from_schema_command);
        //          echo (microtime(true) - $begTime)." sec\n";
        chdir($this->current_dir);
        return true;
    }

    public function createTestdbDumpAndStartSchemaFiles()
    {
        $this->createDumpFile($this->tiki_test_db_dump);
        $this->createStartSchemaFiles();
    }

    public function restoreDBDump($tiki_test_db_dump, $save_schema = false)
    {
        $begTime = microtime(true);

        global $last_restored;
        $error_msg = null;
        chdir($this->mysql_data_dir);
        if (! file_exists($tiki_test_db_dump)) {
            chdir($this->current_dir);
            $conn = mysqli_connect($this->host, $this->tiki_test_db_user, $this->tiki_test_db_pwd);
            mysqli_select_db($conn, $this->tiki_test_db);
            foreach (['tiki_blogs', 'tiki_categories', 'tiki_category_objects', 'tiki_categorized_objects', 'tiki_forums', 'tiki_file_galleries', 'tiki_objects', 'tiki_pages', 'tiki_perspectives', 'tiki_perspective_preferences'] as $table) {
                mysqli_query($conn, "delete from $table");
                mysqli_query($conn, "ALTER TABLE $table AUTO_INCREMENT = 1");
            }
            mysqli_query($conn, "delete from tiki_profile_symbols");
            $this->reinitializeInternalValuesAndClearCaches();
            return;
        }

        if ($last_restored == $tiki_test_db_dump) {
            //restore only the changed tables

            $tiki_schema_file_end = "dump_schema_tiki_end.txt";

            //GET THE CURRENT TABLES
            //              echo "\n\rDumping end tables and times from information_schema: ";
            //              $begTime = microtime(true);

            $mysql_select_from_schema_command = "echo select TABLE_NAME,UPDATE_TIME from information_schema.TABLES WHERE TABLE_SCHEMA=\'$this->tiki_test_db\' | MYSQL_PWD=$this->tiki_test_db_pwd mysql --host=$this->host --user=$this->tiki_test_db_user > $tiki_schema_file_end";
            shell_exec($mysql_select_from_schema_command);
            //              echo (microtime(true) -$begTime)." sec";

            //COMPARE THE START AND END DUMPS
            //              echo "\n\rCompare start and end tables and times from information_schema: ";
            //              $begTime = microtime(true);

            $start_file_lines = file($this->tiki_schema_file_start, FILE_IGNORE_NEW_LINES);
            $end_file_lines = file($tiki_schema_file_end, FILE_IGNORE_NEW_LINES);
            $diff = array_diff($start_file_lines, $end_file_lines);

            //GET ONLY TABLE_NAMES THAT CHANGED
            array_walk($diff, [$this,'getTableName']);

            //              echo (microtime(true) -$begTime)." sec";

            //              echo "\n\rCreate restore sql file: ";
            //              $begTime = microtime(true);

            $tiki_test_db_dump_as_string = file_get_contents($tiki_test_db_dump);

            //CREATE SQL FILE THAT WILL RESTORE ONLY THE CHANGED TABLES
            $tiki_restore_db_file = fopen($this->tiki_restore_db_file_name, 'w') or die("can't open file for restoring DB" . $this->tiki_restore_db_file_name);/**/
            fwrite($tiki_restore_db_file, "-- Nothing\n\n");
            foreach ($diff as $table_name) {
                $match_this = "/(LOCK TABLES `" . $table_name . "`.+UNLOCK TABLES;)/Us";
                $is_matched = preg_match($match_this, $tiki_test_db_dump_as_string, $matches);
                fwrite($tiki_restore_db_file, "TRUNCATE TABLE `" . $table_name . "`;\n\n");
                fwrite($tiki_restore_db_file, $matches[0]);
                fwrite($tiki_restore_db_file, "\n\n\n");
            }
            fclose($tiki_restore_db_file);

            //              echo (microtime(true) -$begTime)." sec";

            //              echo "\n\rRestore original database: ";
            //              $begTime = microtime(true);

            //RESTORE THE ORIGINAL DATABASE
            $installer = Installer::getInstance();
            $installer->runFile($this->tiki_restore_db_file_name);

            //              echo (microtime(true) -$begTime)." sec";
            $last_restored = $tiki_test_db_dump;
            //          $this->reinitializeInternalValuesAndClearCaches();
        } else {
            //restore the whole database
            $this->restoreDBDumpFromScratch($tiki_test_db_dump);
            $last_restored = $tiki_test_db_dump;
            $this->createStartSchemaFiles();
        }
        chdir($this->current_dir);

        return null;
    }

    public function getTableName(&$table_name_date_time)
    {
        preg_match('/([a-zA-Z-_]+)(\s+)/', $table_name_date_time, $matches);
        $table_name_date_time = $matches[1];
    }

    public function restoreBareBonesDB()
    {
        chdir($this->mysql_data_dir);
        $mysql_restore_db_command = "MYSQL_PWD=$this->tiki_test_db_pwd mysql --host=$this->host --user=$this->tiki_test_db_user $this->tiki_test_db < $this->tiki_bare_bones_db_dump";
        shell_exec($mysql_restore_db_command);
        chdir($this->current_dir);
    }

    public function restoreDBDumpFromScratch($dump_file)
    {
        $dump_file_with_path = $this->mysql_data_dir . $dump_file;
        $installer = Installer::getInstance();
        $installer->runFile($dump_file_with_path);
    }
}
