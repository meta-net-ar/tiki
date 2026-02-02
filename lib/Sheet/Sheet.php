<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Sheet;

use TikiLib;

require_once('lib/Sheet/grid.php');

 /** Sheet Class
 * Calculation sheet data container. Used as a bridge between
 * different formats.
 * @author Louis-Philippe Huberdeau (lphuberdeau@phpquebec.org)
 */
class Sheet
{
     // Attributes
    /**
     * Two dimensional array, grid containing the end values ([y][x])
     */
    public $dataGrid;

    /**
     * Two dimensional array, grid containing the raw values ([y][x])
     */
    public $calcGrid;

    /**
     * Two dimensional array, grid containing an associative arrays
     * with 'height' and 'width' values.
     */
    public $cellInfo;

    /**
     * Row and column count once finalized.
     */
    public $rowCount;
    public $columnCount;
    public $metadata;

    /**
     * Layout parameters.
     */
    public $headerRow;
    public $footerRow;
    public $parseValues;
    public $cssName;

    /**
     * Internal values.
     */
    public $COLCHAR;
    public $indexes;
    public $lastIndex;
    public $lastID;

    public $usedRow;
    public $usedCol;

    public $errorFlag;

    public $contributions;
    public $id;
    public $name;
    public $type;

    public $rangeBeginRow = -1;
    public $rangeEndRow   = -1;
    public $rangeBeginCol = -1;
    public $rangeEndCol = -1;

    public $className;

    public function getRangeBeginRow()
    {
        return $this->rangeBeginRow > -1 ? $this->rangeBeginRow : 0;
    }

    public function getRangeEndRow()
    {
        return $this->rangeEndRow > -1 ? $this->rangeEndRow : $this->getRowCount();
    }

    public function getRangeBeginCol()
    {
        return $this->rangeBeginCol > -1 ? $this->rangeBeginCol : 0;
    }

    public function getRangeEndCol()
    {
        return $this->rangeEndCol > -1 ? $this->rangeEndCol : $this->getColumnCount();
    }

    /** getHandlerList
     * Returns an array containing the list of all valid
     * handlers for general file import/export.
     * @return array.
     * @static
     */
    public function getHandlerList()
    {
        return [
            \Tiki\Lib\Sheet\CSVHandler::class,
            \Tiki\Lib\Sheet\CSVExcelHandler::class,
        ];
    }

    /** Sheet
     * Initializes the data container.
     */
    public function __construct()
    {
        $this->dataGrid = [];
        $this->calcGrid = [];
        $this->cellInfo = [];

        $this->COLCHAR = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $this->indexes = [ $this->COLCHAR[0] => 0 ];
        $this->lastIndex = 0;
        $this->lastID = $this->COLCHAR[0];

        $this->rowCount = INITIAL_ROW_COUNT;
        $this->columnCount = INITIAL_COL_COUNT;

        $this->headerRow = 0;
        $this->footerRow = 0;
        $this->parseValues = 'n';
        $this->className = '';
    }

    /** configureLayout
     * Assigns the different parameters for the output
     * @param $className    String The class that will be assigned
     *                      to the table tag of the output.
     *                      If used for an other output than
     *                      HTML, it can be used as an identifier
     *                      for the type of layout.
     * @param $headerRow    Integer The amount of rows that are considered
     *                      as part of the header.
     * @param $footerRow    Integer The amount of rows that are considered
     *                      as part of the footer.
     * @param $parseValues  String Parse cell values as wiki text if ='y'
     *                      when using output handler
     */
    public function configureLayout($className, $headerRow = 0, $footerRow = 0, $parseValues = 'n', $metadata = '')
    {
        $this->cssName = $className;
        $this->headerRow = $headerRow;
        $this->footerRow = $footerRow;
        $this->parseValues = $parseValues;
        $this->metadata = json_decode($metadata ?? "");
    }

    /** getColumnIndex
     * Returns the index of the column from a cell ID.
     * @param $id String Cell ID in [A-Z]+[0-9]+ format.
     * @return Integer Zero-based column index.
     */
    public function getColumnIndex($id)
    {
        if (! preg_match("/^([A-Z]+)([0-9]+)$/", $id, $parts)) {
            return false;
        }

        if (! isset($this->indexes[ $parts[1] ])) {
            while ($this->lastID != $parts[1]) {
                $this->lastID = $this->increment($this->lastID);
                $this->lastIndex++;

                $this->indexes[$this->lastID] = $this->lastIndex;
            }

            return $this->lastIndex;
        } else {
            return $this->indexes[ $parts[1] ];
        }
    }

    /** getRowIndex
     * Returns the index of the row from a cell ID.
     * @param $id String Cell ID in [A-Z]+[0-9]+ format.
     * @return Integer Zero-based row index.
     */
    public function getRowIndex($id)
    {
        if (! preg_match("/^([A-Z]+)([0-9]+)$/", $id, $parts)) {
            return false;
        }

        return $parts[2] - 1;
    }

    /** equals
     * Determines if the value, calculation and size are equal at
     * certain coordinates in the current and the given sheet.
     * @param $sheet Sheet The sheet to compare.
     * @param $rowIndex Integer The row coordinate.
     * @param $columnIndex Integer The column coordinate.
     * @return Boolean True if all values are equal.
     */
    public function equals(Sheet &$sheet, $rowIndex, $columnIndex)
    {
        if (isset($this->dataGrid[$rowIndex][$columnIndex]) && ! isset($sheet->dataGrid[$rowIndex][$columnIndex])) {
            return false;
        }

        if (isset($this->calcGrid[$rowIndex][$columnIndex]) && ! isset($sheet->calcGrid[$rowIndex][$columnIndex])) {
            return false;
        }

        $dataGrid = $this->dataGrid[$rowIndex][$columnIndex];
        $calcGrid = $this->calcGrid[$rowIndex][$columnIndex];
        $cellInfo = $this->cellInfo[$rowIndex][$columnIndex];

        $sheetDataGrid = $sheet->dataGrid[$rowIndex][$columnIndex];
        $sheetCalcGrid = $sheet->calcGrid[$rowIndex][$columnIndex];
        $sheetCellInfo = $sheet->cellInfo[$rowIndex][$columnIndex];

        return (
            $dataGrid == $sheetDataGrid
            && $calcGrid == $sheetCalcGrid
            && isset($sheetCellInfo['value'])
            && isset($cellInfo['value'])
            && isset($sheetCellInfo['calculation'])
            && isset($cellInfo['calculation'])
            && $cellInfo['value'] == $sheetCellInfo['value']
            && $cellInfo['calculation'] == $sheetCellInfo['calculation']
            && $cellInfo['width'] == $sheetCellInfo['width']
            && $cellInfo['height'] == $sheetCellInfo['height']
            && $cellInfo['format'] == $sheetCellInfo['format']
            && $cellInfo['style'] == $sheetCellInfo['style']
            && $cellInfo['class'] == $sheetCellInfo['class']
        );
    }

    /** export
     * Exports the content of the calculation sheet
     * to the given format handler.
     * @param $handler The format handler.
     * @return True on success.
     */
    public function export(&$handler)
    {
        return $handler->save($this);
    }

    /**
     * @param $incsubs boolean Include sub-sheets
     * @param $date
     * @return String
     */
    public function getTableHtml($incsubs = true, $date = null)
    {
        global $prefs;

        $isParse = isset($_REQUEST['parse']) && $_REQUEST['parse'] != 'n';

        $sheetlib = TikiLib::lib('sheet');
        $filegallib = TikiLib::lib("filegal");

        $handler = new OutputHandler(null, ($this->parseValues == 'y' && $isParse));

        $this->export($handler);

        $data = $handler->output;

        if ($incsubs == true) {
            //get sheets from db first
            foreach ($sheetlib->get_related_sheet_ids($this->id) as $childSheetId) {
                $handler = new DatabaseHandler($childSheetId, $date);
                $childSheet = new Sheet();
                $childSheet->import($handler);
                $childSheet->parseValues = true;
                $data .= $childSheet->getTableHtml(false);
            }
        }
        foreach ($sheetlib->get_related_file_ids($this->id) as $childFileId) {
            $fileInfo = $filegallib->get_file_info($childFileId);

            switch ($fileInfo['filetype']) {
                case 'text/csv':
                    $handler = new CSVHandler($fileInfo);
                    break;
                default:
                        $handler = false;
            }

            if (! empty($handler)) {
                $childSheet = new Sheet();
                $childSheet->import($handler);
                $data .= $childSheet->getTableHtml();
            }
        }

        foreach ($sheetlib->get_related_tracker_ids($this->id) as $childTrackerId) {
            $handler = new TrackerHandler($childTrackerId);
            $childSheet = new Sheet();
            $childSheet->import($handler);
            $data .= $childSheet->getTableHtml();
        }


        return $data;
    }

    /** finalize
     * Analyses the content of the sheet and complete the
     * the load.
     */
    public function finalize()
    {
        $maxRow = 0;
        $maxCol = 0;

        $this->finalizeGrid($this->dataGrid, $maxRow, $maxCol);
        $this->finalizeGrid($this->calcGrid, $maxRow, $maxCol);
        $this->finalizeGrid($this->cellInfo, $maxRow, $maxCol, true);

        $this->rowCount = ($maxRow >= INITIAL_ROW_COUNT || $maxRow > 0 ? $maxRow : INITIAL_ROW_COUNT);
        $this->columnCount = ($maxCol >= INITIAL_COL_COUNT || $maxCol > 0 ? $maxCol : INITIAL_COL_COUNT);

        $base = [ 'width' => 1, 'height' => 1, 'format' => null, 'style' => '', 'class' => '' ];
        for ($y = 0; $this->rowCount > $y; $y++) {
            for ($x = 0; $this->columnCount > $x; $x++) {
                if (! isset($this->dataGrid[$y])) {
                    $this->dataGrid[$y] = [];
                }
                if (! isset($this->calcGrid[$y])) {
                    $this->calcGrid[$y] = [];
                }
                if (! isset($this->cellInfo[$y])) {
                    $this->cellInfo[$y] = [];
                }

                if (! isset($this->dataGrid[$y][$x])) {
                    $this->dataGrid[$y][$x] = '';
                }
                if (! isset($this->calcGrid[$y][$x])) {
                    $this->calcGrid[$y][$x] = '';
                }
                if (! isset($this->cellInfo[$y][$x])) {
                    $this->cellInfo[$y][$x] = $base;
                }


                $this->cellInfo[$y][$x] = array_merge($base, $this->cellInfo[$y][$x]);
            }
        }
        return true;
    }

    /** finalizeGrid
     * Locates the maximal values in a grid if they are above the initial ones.
     * @param $grid Array The grid to scan
     * @param $maxRow Integer The highest row index.
     * @param $maxCol Integer The highest column index.
     * @param $addIndex Boolean value, used for merged cells, determines
     *                  if the actual value should be added when calculating
     *                  the maximal values. As merged cells use more space,
     *                  they should be considered as more cells.
     */
    public function finalizeGrid($grid, &$maxRow, &$maxCol, $addIndex = false)
    {
        foreach ($grid as $key => $row) {
            $this->finalizeRow($row, $maxRow, $maxCol, $key, $addIndex);
        }
    }

    /** finalizeRow
     * Identifies the largest key in an array and set it as the new maximum.
     * @param $row Integer The row to scan.
     * @param $maxRow Integer The current maximum value of the row.
     * @param $maxCol Integer The current maximum value of the column.
     * @param $rowIndex Integer
     * @param $addIndex Boolean Used for merged cells. Leave value blank (false) if the current scan is not on the
     * merged cell grid. Other possible values are 'width' and 'height' which should be used based on which side of the
     * grid is being scanned.
     */
    public function finalizeRow($row, &$maxRow, &$maxCol, $rowIndex, $addIndex = false)
    {
        $localMax = max(array_keys($row));

        $total = $localMax;
        if ($addIndex) {
            $total += $row[$localMax]['width'];
        }

        if ($total > $maxCol) {
            $maxCol = $total;
        }

        if ($addIndex) {
            foreach ($row as $info) {
                if (isset($info['height'])) {
                    $total = $rowIndex + $info['height'];
                }

                if ($total > $maxRow) {
                    $maxRow = $total;
                }
            }
        } else {
            if ($rowIndex > $maxRow) {
                $maxRow = $rowIndex;
            }
        }
    }

    /** getColumnCount
     * Returns the column count.
     */
    public function getColumnCount()
    {
        return $this->columnCount == 0 ? INITIAL_COL_COUNT : $this->columnCount;
    }

    /** getRange
     * Reutrns an array containing the values located in
     * a given range (ex: A1:B9)
     */
    public function getRange($range)
    {
        if (preg_match('/^([A-Z]+)([0-9]+):([A-Z]+)([0-9]+)$/', strtoupper($range), $parts)) {
            $beginRow = $parts[2] - 1;
            $endRow = $parts[4] - 1;
            $beginCol = $this->getColumnNumber($parts[1]);
            $endCol = $this->getColumnNumber($parts[3]);

            if ($beginRow > $endRow) {
                $a = $endRow;
                $endRow = $beginRow;
                $beginRow = $a;
            }
            if ($beginCol > $endCol) {
                $a = $endCol;
                $endCol = $beginCol;
                $beginCol = $a;
            }

            $data = [];
            for ($row = $beginRow; $endRow + 1 > $row; $row++) {
                for ($col = $beginCol; $endCol + 1 > $col; $col++) {
                    if (isset($this->dataGrid[$row]) && isset($this->dataGrid[$row][$col])) {
                        $data[] = $this->dataGrid[$row][$col];
                    }
                }
            }

            return $data;
        } else {
            return false;
        }
    }

    /** setRange
     * Limits display (so far)
     * a given range (ex: A1:B9)
     */
    public function setRange($range)
    {
        if (preg_match('/^([A-Z]+)([0-9]+):([A-Z]+)([0-9]+)$/', strtoupper($range), $parts)) {
            $this->rangeBeginRow = (int)$parts[2] - 1;
            $this->rangeEndRow = (int)$parts[4];
            $this->rangeBeginCol = $this->getColumnNumber($parts[1]);
            $this->rangeEndCol = $this->getColumnNumber($parts[3]) + 1;
        }
    }

    /** getRowCount
     * Returns the row count.
     */
    public function getRowCount()
    {
        return $this->rowCount;
    }

    public function name()
    {
        return $this->name;
    }

    /** import
     * Fills the content of the calculation sheet with
     * data from the given handler.
     * @param $handler Object The format handler.
     * @return True on success.
     */
    public function import(&$handler)
    {
        $this->name = $handler->name();
        if (isset($handler->id)) {
            $this->id = $handler->id;
        }
        $this->type = (isset($handler->type) ? $handler->type : $this->type);
        $this->cssName = $handler->cssName;
        $this->rowCount = ($handler->rowCount ?? $this->rowCount);
        $this->columnCount = ($handler->columnCount ?? $this->columnCount);

        $this->dataGrid = [];
        $this->calcGrid = [];
        $this->cellInfo = [];
        $this->errorFlag = false;

        set_error_handler([ &$this, "error_handler" ]);
        if (! $handler->load($this) || $this->errorFlag) {
            restore_error_handler();
            return false;
        }

        restore_error_handler();
        return $this->finalize();
    }

    /** increment
     * Implementation of the column ID incrementation used
     * on client side.
     * @param $val String The value to increment.
     * @return Integer The incremented value.
     */
    public function increment($val)
    {
        if (empty($val)) {
            return substr($this->COLCHAR, 0, 1);
        }

        $n = strpos($this->COLCHAR, substr($val, -1)) + 1;

        if ($n < strlen($this->COLCHAR)) {
            return substr($val, 0, -1) . substr($this->COLCHAR, $n, 1);
        } else {
            return $this->increment(substr($val, 0, -1)) . substr($this->COLCHAR, 0, 1);
        }
    }

    /** initCell
     * Indicates the next cell that will be filled.
     * @param $cellID Integer The Identifier of the cell or the row index
     *                  if there are 2 parameters.
     * @param $col Integer The index of the column.
     * @return True on success.
     */
    public function initCell($cellID, $col = null)
    {
        if ($col === null) {
            $this->usedRow = $this->getRowIndex($cellID);
            $this->usedCol = $this->getColumnIndex($cellID);
        } else {
            $this->usedRow = $cellID;
            $this->usedCol = $col;
        }

        return $this->usedRow !== false && $this->usedCol !== false;
    }

    /** isEmpty
     * Determines if the value, calculation and size are equal at
     * certain coordinates in the current and the given sheet.
     * @param $rowIndex Integer The row coordinate.
     * @param $columnIndex Integer The column coordinate.
     * @return True if all values are empty.
     */
    public function isEmpty($rowIndex, $columnIndex)
    {
        return $this->dataGrid[$rowIndex][$columnIndex] == ''
            && $this->calcGrid[$rowIndex][$columnIndex] == ''
            && ( $this->cellInfo[$rowIndex][$columnIndex]['width'] == ''
            ||   $this->cellInfo[$rowIndex][$columnIndex]['width'] == 1 )
            && ( $this->cellInfo[$rowIndex][$columnIndex]['height'] == ''
            ||   $this->cellInfo[$rowIndex][$columnIndex]['height'] == 1 );
    }

    /** setCalculation
     * Assigns a calculation to the currently initialized
     * cell.
     * @param $calculation String The calculation to set.
     */
    public function setCalculation($calculation)
    {
        $this->calcGrid[$this->usedRow][$this->usedCol]['calculation'] = $calculation;
    }

    /** setFormat
     * Indicates the cell's data format during display.
     * The format is a text identifier that matches a function
     * name that will be executed.
     */
    public function setFormat($format)
    {
        if (empty($format) || ! method_exists(new DataFormat(), $format)) {
            $format = null;
        }
        $this->cellInfo[$this->usedRow][$this->usedCol]['format'] = $format;
    }

    /** setRowSpan
     * Sets the cell's row span
     * @param $rowSpan Number row span
     */
    public function setRowSpan($rowSpan)
    {
        $this->cellInfo[$this->usedRow][$this->usedCol]["height"] = $rowSpan;
    }

    /** setSize
     * Sets the size of the last initialized cell.
     * @param $colSpan Number col span
     */
    public function setColSpan($colSpan)
    {
        $this->cellInfo[$this->usedRow][$this->usedCol]["width"] = $colSpan;
    }

    public function setDeadCells()
    {
        $usedRow = $this->usedRow;
        $usedCol = $this->usedCol;
        $cellInfo = $this->cellInfo[$this->usedRow][$this->usedCol];

        for ($y = $usedRow; $usedRow + $cellInfo['height'] > $y; $y++) {
            for ($x = $usedCol; $usedCol + $cellInfo['width'] > $x; $x++) {
                if (! ($y == $usedRow && $x == $usedCol)) {
                    $this->createDeadCell($x, $y);
                }
            }
        }
    }

    /** setValue
     * Assigns a value to the currently initialized
     * cell.
     * @param $value String The value to set.
     */
    public function setValue($value)
    {
        $this->dataGrid[$this->usedRow][$this->usedCol]['value'] = $value;
    }

    /** setStyle
     * Sets html style,if any, to the currently initialized
     * cell.
     * @param $style String The value to set.
     */
    public function setStyle($style = '')
    {
        $this->cellInfo[$this->usedRow][$this->usedCol]['style'] = $style;
    }

    /** setClass
     * Sets html class, if any, to the currently initialized
     * cell.
     * @param $class String The value to set.
     */
    public function setClass($class = '')
    {
        $this->cellInfo[$this->usedRow][$this->usedCol]['class'] = $class;
    }

    /** createDeadCell
     * Assigns the cell as overlapped by a wide cell.
     * @param $x Integer Coordinate of the cell
     * @param $y Integer Coordinate of the cell
     */
    public function createDeadCell($x, $y)
    {
        $this->dataGrid[$y][$x] = null;
        $this->cellInfo[$y][$x] = [ "width" => 0, "height" => 0, "format" => null, "style" => "", "class" => "" ];
    }

    /** getClass
     * Returns the class of a the current cell if it exist.
     */
    public function getClass()
    {
        if (isset($this->cellInfo[$this->usedRow][$this->usedCol]['class'])) {
            return $this->cellInfo[$this->usedRow][$this->usedCol]['class'];
        } else {
            return "";
        }
    }

    /** getColumnNumber
     * Returns the column number from the letter-style.
     */
    public function getColumnNumber($letter)
    {
        $val = 0;
        $len = strlen($letter);

        for ($i = 0; $len > $i; $i++) {
            $pow = pow(26, $len - $i - 1);
            $val += $pow * ( ord($letter[$i]) - 64 );
        }
        $val--;

        return $val;
    }

    /** error_handler
     * Callback error handler function. Used by import.
     * @see http://ca.php.net/set_error_handler
     */
    public function error_handler($errno, $errstr, $errfile, $errline)
    {
        echo $errstr . ': ' . $errfile . ' (' . $errline . ')';
        $this->errorFlag = true;
    }
}
