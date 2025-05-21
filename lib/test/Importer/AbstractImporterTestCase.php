<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//require_once('PHPUnit/Framework/TestCase.php');
namespace Tiki\Lib\Test\Importer;

use TikiTestCase;

/**
 * @group importer
 */
abstract class AbstractImporterTestCase extends TikiTestCase
{
    protected $backupGlobals = false;
}
