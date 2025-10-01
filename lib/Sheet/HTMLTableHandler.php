<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Sheet;

require_once('lib/Sheet/grid.php');

/** HTMLTableHandler
 * Class that imports a sheet from an HTML table
 * Designed to be used with jQuery.sheet.save_sheet
 */
class HTMLTableHandler extends DataHandler
{
    public $data;

    /** Constructor
     * Initializes the the serializer on a wiki page
     * @param $file The name of the wiki page to perform actions on.
     */
    public function __construct($json)
    {
        $this->data = $json;
    }

    // _load
    public function load(Sheet &$sheet)
    {
        $d = $this->data;

        foreach ($d->metadata->widths as $c => $width) {
        }

        foreach ($d->rows as $r => $row) {
            foreach ($row->columns as $c => $column) {
                $sheet->initCell($r, $c);


                //if cell has formula, use it, otherwise, use value, if value if blank, use ''
                if (! empty($column->formula)) {
                    $sheet->setCalculation($column->formula);
                } else {
                    $sheet->setValue($column->value ?? '');
                }


                //Make cell able to span multi columns and rows
                $rowSpan = 1;
                $colSpan = 1;
                if (isset($column->rowspan)) {
                    $rowSpan = $column->rowspan;
                }
                if (isset($column->colspan)) {
                    $colSpan = $column->colspan;
                }
                $sheet->setRowSpan($rowSpan);
                $sheet->setColSpan($colSpan);
                $sheet->setDeadCells();


                //setup cell css style
                if (! empty($column->style)) {
                    $sheet->setStyle($column->style);
                }

                //setup cell html class
                if (isset($column->class)) {
                    $sheet->setClass($column->class);
                }
            }
        }

        return true;
    }

    // name
    public function name()
    {
        return tr("HTML Table");
    }

    // supports
    public function supports($type)
    {
        return ( TIKISHEET_LOAD_DATA & $type ) > 0;
    }

    // version
    public function version()
    {
        return "1.0";
    }
}
