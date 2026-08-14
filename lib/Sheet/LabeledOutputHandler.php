<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Sheet;

use TikiLib;

require_once('lib/Sheet/grid.php');

/** LabeledOutputHandler
 * Class to output the data sheet as a standard HTML table.
 * Importing is not supported.
 */
class LabeledOutputHandler extends DataHandler
{
    /** Constructor
     */
    public function __construct()
    {
    }

    // _save
    public function save(&$sheet)
    {
        $this->output = "<table class=\"default\">\n";

        $this->output .= "    <thead>\n";
        $this->output .= "        <tr><th></th>\n";

        $prev = 'A';
        for ($j = 0; $sheet->getColumnCount() > $j; $j++) {
            $this->output .= "            <th>$prev</th>\n";
            $prev = $sheet->increment($prev);
        }

        $this->output .= "        </tr>\n";
        $this->output .= "    </thead>\n";

        $this->output .= "    <tbody>\n";
        $this->drawRows($sheet);
        $this->output .= "    </tbody>\n";

        $this->output .= "</table>\n";

        return true;
    }

    /** drawRows
     * Draws out a defined set of rows from the sheet.
     * @param $sheet The data container.
     * @param $begin The index of the begining row. (included)
     * @param $end The index of the end row (excluded)
     */
    public function drawRows(&$sheet)
    {
        $sheetlib = TikiLib::lib('sheet');

        $beginRow = $sheet->getRangeBeginRow();
        $endRow = $sheet->getRangeEndRow();

        $beginCol = $sheet->getRangeBeginCol();
        $endCol = $sheet->getRangeEndCol();

        for ($i = $beginRow; $i < $endRow; $i++) {
            $trHeight = "20px";
            $td = '';
            for ($j = $beginCol; $j < $endCol; $j++) {
                $width = $height = "";
                extract($sheet->cellInfo[$i][$j]);
                $append = "";

                if (empty($width) || empty($height) || $width == 0 || $height == 0) {
                    continue;
                }

                if ($width > 1) {
                    $append .= " colspan='{$width}'";
                }

                if ($height > 1) {
                    $append .= " rowspan='{$height}'";
                }

                if (isset($sheet->dataGrid[$i][$j])) {
                    $data = $sheet->dataGrid[$i][$j];
                } else {
                    $data = '';
                }

                $format = $sheet->cellInfo[$i][$j]['format'];
                if (! empty($format)) {
                    $data = DataFormat::$format($data);
                }

                $style = $sheet->cellInfo[$i][$j]['style'];
                if (! empty($style)) {
                    $append .= " style='{$style}'";

                    $trHeight = $sheetlib->get_attr_from_css_string($style, "height", "20px");
                }

                $class = $sheet->cellInfo[$i][$j]['class'];
                if (! empty($class)) {
                    $append .= " class='{$class}'";
                }

                $td .= "            <td$append>$data</td>\n";
            }

            $tr = "        <tr  style='height: $trHeight;' height='$trHeight'><th>" . ($i + 1) . "</th>\n";
            $tr .= $td;
            $tr .= "    </tr>\n";

            $this->output .= $tr;
        }
    }

    // supports
    public function supports($type)
    {
        return ( ( TIKISHEET_SAVE_DATA | TIKISHEET_SAVE_CELL | TIKISHEET_SAVE_FORMAT ) & $type ) > 0;
    }

    // version
    public function version()
    {
        return "1.0";
    }
}
