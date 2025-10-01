<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Test\TestHelpers;

use Tiki\Lib\Test\AcceptanceTests\DBRestorerSQLDumps;

class TikiDbHelper
{
    public const EMPTY_DB = 'emptyDb.sql';

    public static function refreshDb($database = self::EMPTY_DB): void
    {
        $dbRestorer = new DBRestorerSQLDumps();
        $dbRestorer->restoreDBDump($database);
    }
}
