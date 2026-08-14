<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Sheet;

require_once('lib/Sheet/grid.php');

/** SimpleArrayHandler
 * Class that stores the sheet representation in a
 * standard text file as a serialized PHP object.
 */
class SimpleArrayHandler extends DataHandler
{
    public $name;
    /**
     * @var string
     */
    public $type;
    public $values = [];

    public function __construct($simpleArray = [])
    {
        $this->values = $simpleArray['values'];
        $this->name = $simpleArray['name'];
        $this->type = "simpleArray";
        $this->cssName = 'readonly';
    }

    // _load
    public function load(&$sheet)
    {
        $i = 0;

        foreach ($this->values as $row) {
            $j = 0;
            foreach ($row as $key => $col) {
                $sheet->initCell($i, $j);

                if (! empty($col[0]) && $col[0] == '=') {
                    $sheet->setCalculation(substr($col, 1));
                }

                if (is_array($col)) {
                    foreach ($col as $colKey => $val) {
                        if (empty($val)) {
                            array_splice($col, $colKey, 1);
                        }
                    }
                    $col = implode(",", $col);
                }

                $col = htmlspecialchars($col);

                $sheet->setValue($i == 0 ? $key : $col);

                $sheet->setColSpan(1, 1);
                $j++;
            }
            $i++;
        }

        return true;
    }

    // _save
    public function save(&$sheet)
    {
        return false;
    }

    // name
    public function name()
    {
        return $this->name;
    }

    // supports
    public function supports($type)
    {
        return ( ( TIKISHEET_LOAD_DATA ) & $type ) > 0;
    }

    // version
    public function version()
    {
        return "1.0";
    }
}
