<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Sheet;

use TikiLib;

require_once('lib/Sheet/grid.php');

/** OutputHandler
 * Class to output the data sheet as a standard HTML table.
 * Importing is not supported.
 */
class OutputHandler extends DataHandler
{
    public $heading;
    public $parseOutput;

    /** Constructor
     * Identifies the caption of the table if it applies.
     * @param $heading          The heading
     * @param $parseOutput      Parse wiki markup in cells if parseValues=y in sheet layout
     */
    public function __construct($heading = null, $parseOutput = true)
    {
        $this->heading = $heading;
        $this->parseOutput = $parseOutput;
    }

    // _save
    public function save(Sheet &$sheet)
    {
        // if ( $sheet->headerRow + $sheet->footerRow > $sheet->getRowCount() )
        // return false;
        $beginRow = $sheet->getRangeBeginRow();
        $endRow = $sheet->getRangeEndRow();

        $beginCol = $sheet->getRangeBeginCol();
        $endCol = $sheet->getRangeEndCol();

        if (
            $beginRow > -1 &&
            $beginRow == $endRow - 1 &&
            $beginCol == $endCol - 1
        ) {
            if (isset($sheet->dataGrid[$beginRow][$beginCol])) {
                $data = $sheet->dataGrid[$beginRow][$beginCol];
                if (is_array($data)) {
                    $data = implode('', $data);
                }

                if ($sheet->parseValues == 'y' && mb_ereg_match('[^A-Za-z0-9\s]', $data)) { // needs to be multibyte regex here
                    global $tikilib;
                    $data = TikiLib::lib('parser')->parse_data($data, ['suppress_icons' => true]);
                }
                $sheet->dataGrid[$beginRow][$beginCol] = $data;
            }
        }

        $class = empty($sheet->cssName) ? "" : " class='{$sheet->cssName}'";
        $id = empty($sheet->id) ? '' : " data-id='{$sheet->id}'";
        $title = " title='" . htmlspecialchars($sheet->name(), ENT_QUOTES) . "'";
        $type = (! empty($sheet->type) ? ' data-type="' . $sheet->type . '" ' : '');

        $this->output = "<table" . $class . $id . $title . $type . ">\n";

        if (! is_null($this->heading)) {
            $this->output .= "    <caption>{$this->heading}</caption>\n";
        }

        if ($sheet->headerRow > 0 && $beginRow < 0) {
            $this->output .= "    <thead>\n";
            $this->drawRows($sheet);
            $this->output .= "    </thead>\n";
        }

        $this->output .= "    <colgroup>\n";
        $this->drawCols($sheet);
        $this->output .= "    </colgroup>\n";

        $this->output .= "    <tbody>\n";
        $this->drawRows($sheet);
        $this->output .= "    </tbody>\n";

        if ($sheet->footerRow > 0 && $beginRow < 0) {
            $this->output .= "    <tfoot>\n";
            $this->drawRows($sheet);
            $this->output .= "    </tfoot>\n";
        }

        $this->output .= "</table>\n";
        return true;
    }

    /** drawRows
     * Draws out a defined set of rows from the sheet.
     * @param $sheet The data container.
     * @param $begin The index of the begining row. (included)
     * @param $end The index of the end row (excluded)
     */
    public function drawRows(Sheet &$sheet)
    {
        $sheetlib = TikiLib::lib('sheet');

        $beginRow = $sheet->getRangeBeginRow();
        $endRow = $sheet->getRangeEndRow();

        $beginCol = $sheet->getRangeBeginCol();
        $endCol = $sheet->getRangeEndCol();

        for ($i = $beginRow; $i < $endRow; $i++) {
            $td = "";
            $trStyleHeight = "";
            $trHeight = "20px";
            $trHeightIsSet = false;

            for ($j = $beginCol; $j < $endCol; $j++) {
                $width = $height = '';
                if (! empty($sheet->cellInfo[$i][$j])) {
                    extract($sheet->cellInfo[$i][$j]);
                }

                $append = '';

                if ($width > 1) {
                    $append .= " colspan='{$width}'";
                }

                if ($height > 1) {
                    $append .= " rowspan='{$height}'";
                }

                if (! empty($sheet->calcGrid[$i][$j]['calculation'])) {
                    $append .= ' data-formula="=' . str_replace('"', "'", $sheet->calcGrid[$i][$j]['calculation']) . '"';
                }

                if (isset($sheet->dataGrid[$i][$j]['value'])) {
                    $data = $sheet->dataGrid[$i][$j]['value'];
                } else {
                    $data = '';
                }

                $format = $sheet->cellInfo[$i][$j]['format'];
                if (! empty($format)) {
                    $data = DataFormat::$format($data);
                }

                $style = $sheet->cellInfo[$i][$j]['style'];
                if (! empty($style)) {
                    //we have to sanitize the css style here
                    $tdStyle = "";
                    $color = $sheetlib->get_attr_from_css_string($style, "color", "");
                    $bgColor = $sheetlib->get_attr_from_css_string($style, "background-color", "");
                    $tdHeight = '';

                    if ($trHeightIsSet == false) {
                        $trHeight = $sheetlib->get_attr_from_css_string($style, "height", "20px");
                        $trHeightIsSet = true;
                    }

                    if ($color) {
                        $tdStyle .= "color:$color;";
                    }
                    if ($bgColor) {
                        $tdStyle .= "background-color:$bgColor;";
                    }

                    $tdHeight = $trHeight;
                    if ($tdHeight) {
                        $tdStyle .= "height:$tdHeight;";
                        $append .= " height='" . str_replace("px", "", $tdHeight) . "'";
                    }

                    $append .= " style='$tdStyle'";
                }

                $class = $sheet->cellInfo[$i][$j]['class'];
                if (! empty($class)) {
                    $append .= ' class="' . $class . '"';
                }

                if ($this->parseOutput && $sheet->parseValues == 'y') {
                    global $tikilib;
                    // only parse if we have non-alphanumeric or space chars
                    if (mb_ereg_match('[^A-Za-z0-9\s]', $data)) {   // needs to be multibyte regex here
                        $data = TikiLib::lib('parser')->parse_data($data, ['suppress_icons' => true]);
                    }
                    if (str_starts_with($data, '<p>')) {   // remove containing <p> tag
                        $data = substr($data, 3);
                        if (strrpos($data, '</p>') === strlen($data) - 4) {
                            $data = substr($data, 0, -4);
                        }
                    }
                }
                $td .= "            <td" . $append . ">$data</td>\n";
            }

            if (! empty($td)) {
                $this->output .= "        <tr style='height: $trHeight;' height='" . str_replace("px", "", $trHeight) . "'>\n";
                $this->output .= $td;
                $this->output .= "        </tr>\n";
            }
        }
    }

    /** drawCols
     * Draws out a defined set of rows from the sheet.
     * @param $sheet The data container.
     * @param $begin The index of the begining row. (included)
     * @param $end The index of the end row (excluded)
     */
    public function drawCols(Sheet &$sheet)
    {
        $sheetlib = TikiLib::lib('sheet');

        if (isset($sheet->metadata) && isset($sheet->metadata->widths)) {
            foreach ($sheet->metadata->widths as $width) {
                $this->output .= "<col style='width:" . ($width * 1) . "px;' />";
            }
        } else {
            $beginCol = $sheet->getRangeBeginCol();
            $endCol = $sheet->getRangeEndCol();
            for ($i = $beginCol; $i < $endCol; $i++) {
                $style = $sheet->cellInfo[0][$i]['style'];
                $width = $sheetlib->get_attr_from_css_string($style, "width", "118px");
                $this->output .= "<col style='width: $width;' width='$width' />\n";
            }
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
