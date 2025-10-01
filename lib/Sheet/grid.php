<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//this script may only be included - so its better to die if called directly.
if (str_contains($_SERVER["SCRIPT_NAME"], basename(__FILE__))) {
    header("location: index.php");
    exit;
}

ini_set('include_path', ini_get('include_path') . ":lib/Sheet");

// Nice dependencies, mostly for excel support. Don't try changing the order.
require_once("lib/Sheet/excel/reader_ole.php");
require_once("lib/Sheet/excel/reader.php");
require_once("lib/Sheet/excel/writer/format.php");
require_once("lib/Sheet/excel/writer/biffwriter.php");
require_once("lib/Sheet/excel/writer/worksheet.php");
require_once("lib/Sheet/excel/writer/workbook.php");
require_once("lib/Sheet/excel/writer/parser.php");
require_once("lib/Sheet/ole/pps.php");
require_once("lib/Sheet/ole/pps/root.php");
require_once("lib/Sheet/ole/pps/file.php");
require_once("lib/Sheet/ole.php");
require_once("lib/Sheet/excel/writer.php");
//require_once( "lib/Sheet/conf/config.inc.php" );
require_once("lib/encoding/lib-encoding.php");
// Constants

/*
DATA:
End values will be preserved.

CALC:
The calculations will be preserved.

CELL:
The cell merging will be preserved.

FORMAT:
The display format of the data of the cell
*/

define('TIKISHEET_SAVE_DATA', 0x00010000);
define('TIKISHEET_SAVE_CALC', 0x00020000);
define('TIKISHEET_SAVE_CELL', 0x00040000);
define('TIKISHEET_SAVE_FORMAT', 0x00080000);

define('TIKISHEET_LOAD_DATA', 0x00000001);
define('TIKISHEET_LOAD_CALC', 0x00000002);
define('TIKISHEET_LOAD_CELL', 0x00000004);
define('TIKISHEET_LOAD_FORMAT', 0x00000008);

// Initial amount of rows and columns at TikiSheet initialisation
define('INITIAL_ROW_COUNT', 15);
define('INITIAL_COL_COUNT', 5);

// Map array indexes.
define('TS_THEAD', 0);
define('TS_TBODY', 1);
define('TS_TFOOT', 2);

define('TS_DEFAULT', 'default');


// Registration function
function TIKISHEET_REGISTER_HANDLER($class)
{
    global $globalHandlers;
    $globalHandlers[] = $class;
}
