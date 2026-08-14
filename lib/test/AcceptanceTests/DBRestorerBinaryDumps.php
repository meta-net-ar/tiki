<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\AcceptanceTests;

class DBRestorerBinaryDumps extends AbstractDBRestorer
{
    /*
     * This subclass uses binary files of the SQL database, instead of
     * files containing SQL statements to restore the DB.
     * It is faster, but possibly less robust than the SQLDumps approach.
     * We keep both approaches for now, until we decide which of the
     * two makes most sense.
     */

    private $dump_file_extension = 'binary';

    public function __construct()
    {
        parent::__construct();
    }

    public function createDumpFile($dump_name)
    {
        $tiki_test_db_data_directory =
            $this->mysql_data_dir . DIRECTORY_SEPARATOR .
            $this->tiki_test_db;
        $this->copyDir($tiki_test_db_data_directory, $this->dumpFilePath($dump_name));
    }

    private function dumpFilePath($dump_name)
    {
        return $this->mysql_data_dir . DIRECTORY_SEPARATOR .
        $dump_name .
        "." . $this->dump_file_extension;
    }

    public function checkIfDumpExists($dump_file)
    {
    }

    public function restoreDBDump($tiki_test_db_dump, $save_schema = false)
    {
    }

    private function copyDir($source, $target)
    {
        if (is_dir($source)) {
            @mkdir($target);
            $d = dir($source);
            while (false !== ($entry = $d->read())) {
                if ($entry == '.' || $entry == '..') {
                    continue;
                }
                $Entry = $source . '/' . $entry;
                if (is_dir($Entry)) {
                    full_copy($Entry, $target . '/' . $entry);
                    continue;
                }
                copy($Entry, $target . '/' . $entry);
            }

            $d->close();
        } else {
            copy($source, $target);
        }
    }
}
