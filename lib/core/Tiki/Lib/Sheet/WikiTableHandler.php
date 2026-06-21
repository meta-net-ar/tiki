<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Sheet;

use TikiLib;

require_once('lib/Sheet/grid.php');

/** WikiTableHandler
 * Class that stores the sheet representation in a
 * standard text file as a serialized PHP object.
 */
class WikiTableHandler extends DataHandler
{
    public $pageName;

    /** Constructor
     * Initializes the the serializer on a wiki page
     * @param $file The name of the wiki page to perform actions on.
     */
    public function __construct($pageName)
    {
        $this->pageName = $pageName;
    }

    // _load
    public function load(Sheet &$sheet)
    {
        $tikilib = TikiLib::lib('tiki');

        $result = $tikilib->query("SELECT `data` FROM `tiki_pages` WHERE `pageName` = ?", [ $this->pageName ]);
        if ($row = $result->fetchRow()) {
            $tables = $this->getRawTables($row['data']);

            $row = 0;
            foreach ($tables as $table) {
                $table = explode("\n", $table);

                foreach ($table as $line) {
                    $line = explode('|', trim($line));

                    foreach ($line as $col => $cellValue) {
                        $value = $cellValue['value'];
                        $sheet->initCell($row, $col);
                        $sheet->setValue($value);
                        if (isset($value)) {
                            if (preg_match("/^::(.*)::$/", $value, $matches)) {
                                $sheet->setClass($sheet->getClass() . " styleCenter");
                                $value = $matches[1];
                                $sheet->setValue($value);
                            }
                            if (preg_match("/^__(.*)__$/", $value, $matches)) {
                                $sheet->setClass($sheet->getClass() . " styleBold");
                                $value = $matches[1];
                                $sheet->setValue($value);
                            }
                            if (preg_match("/^''(.*)''$/", $value, $matches)) {
                                $sheet->setClass($sheet->getClass() . " styleItalic");
                                $value = $matches[1];
                                $sheet->setValue($value);
                            }
                            if (strlen($value)) {
                                if ($value[0] == '=') {
                                    $sheet->setCalculation(substr($value, 1));
                                } else {
                                    $sheet->setCalculation($value);
                                }
                            }
                        }
                        $sheet->setRowSpan(1);
                        $sheet->setColSpan(1);
                        $sheet->setDeadCells();
                    }
                    ++$row;
                }
            }

            return true;
        } else {
            return false;
        }
    }

    /** getRawTables
     * Returns an array containing all table-like structures
     * in the wiki-content.
     */
    public function getRawTables($data)
    {
        $pos = 0;
        $tables = [];
        while (true) { // Keep looping
            if (( $begin = strpos($data, '||', $pos) ) === false) {
                break;
            };
            if (( $end = strpos($data, '||', $begin + 2) ) === false) {
                break;
            }

            $pos = $end + 2;

            $content = substr($data, $begin + 2, $end - $begin - 2);

            if (str_contains($content, '|')) {
                $tables[] = $content;
            }
        }


        return $tables;
    }

    // name
    public function name()
    {
        return tr("CSV File");
    }

    // supports
    public function supports($type)
    {
        return ( TIKISHEET_LOAD_DATA & $type ) > 0;
    }

    // version
    public function version()
    {
        return "0.1-dev";
    }
}
