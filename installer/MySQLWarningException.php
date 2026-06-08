<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Installer;

use Exception;

/**
 * Exception thrown when MySQL warnings are caught during database upgrade and
 * stricter checks are enabled (e.g. in CI environments).
 */
class MySQLWarningException extends Exception
{
}
