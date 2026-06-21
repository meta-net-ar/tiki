<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Sheet;

use TikiLib;

require_once('lib/Sheet/grid.php');

/** DatabaseHandler
 * Class to handle transactions with the database.
 * The class and database structure allow data
 * rollbacks. The class does not allow to manipulate
 * the sheets themselves. The data will only be filled
 * and extracted based on the given sheet ID. As a default
 * value, the most recent entries will be read.
 *
 * The database loader will also select the appropriate
 * layout based on the timestamped database entries. Using
 * the database handler will not require to specify manually
 * using Sheet::configureLayout() as it is required by all
 * other known handler as this comment is being written.
 */
class DatabaseHandler extends DataHandler
{
    public $id;
    public $readDate;
    public $rowCount;
    public $columnCount;
    public $metadata;
    public $type;
    public $name;
    public $className;

    /** Constructor
     * Assigns a sheet ID to the handler.
     * @param $id Integer The ID of the sheet in the database.
     * @param $date Integer The database link to use.
     */
    public function __construct($id, $date = null, $metadata = null)
    {
        $sheetlib = TikiLib::lib('sheet');

        $this->id = $id;
        $this->readDate = ( $date ? $date : time() );

        $info = $sheetlib->get_sheet_info($this->id);

        $this->type = "sheet";
        $this->name = $info['title'];
        $this->metadata = $metadata;
    }

    // _load
    public function load(Sheet &$sheet)
    {
        $sheetlib = TikiLib::lib('sheet');
        $tikilib = TikiLib::lib('tiki');

        $result = $tikilib->query("
            SELECT `rowIndex`, `columnIndex`, `value`, `calculation`, `width`, `height`, `format`, `style`, `class`, `user`
            FROM `tiki_sheet_values`
            WHERE
                `sheetId` = ? AND
                ? >= `begin` AND
                (
                    `end` IS NULL OR
                    `end` > ?
                )
        ", [ $this->id, (int)$this->readDate, (int)$this->readDate ]);

        while ($row = $result->fetchRow()) {
            $sheet->initCell($row['rowIndex'], $row['columnIndex']);
            $sheet->setValue($row['value']);
            $sheet->setCalculation($row['calculation']);
            $sheet->setColSpan($row['width']);
            $sheet->setRowSpan($row['height']);
            $sheet->setDeadCells();
            $sheet->setFormat($row['format']);
            $sheet->setStyle($row['style']);
            $sheet->setClass($row['class']);
        }

        // Fetching the layout informations.
        $result2 = $tikilib->query("
            SELECT `className`, `headerRow`, `footerRow`, `parseValues`, `metadata`
            FROM `tiki_sheet_layout`
            WHERE
                `sheetId` = ? AND
                ? >= `begin` AND
                ( `end` IS NULL OR `end` > ? )
        ", [ $this->id, (int)$this->readDate, (int)$this->readDate ]);

        if ($row = $result2->fetchRow()) {
            $sheet->configureLayout($row['className'], $row['headerRow'], $row['footerRow'], $row['parseValues'], $row['metadata']);
        }

        return true;
    }

    public function name()
    {
        return $this->name;
    }

    // _save
    public function save(Sheet &$sheet)
    {
        global $user, $prefs;
        $tikilib = TikiLib::lib('tiki');

        // Load the current database state {{{3
        $current = new Sheet();
        $handler = new DatabaseHandler($this->id, null, $this->metadata);
        $current->import($handler);

        // Find differences {{{3
        $mods = [];
        for ($row = 0; $sheet->getRowCount() > $row; $row++) {
            for ($col = 0; $sheet->getColumnCount() > $col; $col++) {
                if (! $sheet->equals($current, $row, $col)) {
                    $mods[] = [ "row" => $row, "col" => $col ];
                }
            }
        }

        $stamp = time();

        $inserts = [];
        $updates = [];
        $updates[] = $stamp;
        $updates[] = $this->id;

        // Update the database {{{3
        if (is_array($mods)) {
            foreach ($mods as $coord) {
                extract($coord);
                $value = $sheet->dataGrid[$row][$col]['value'] ?? null;
                $calc = $sheet->calcGrid[$row][$col]['calculation'] ?? null;
                $width = $sheet->cellInfo[$row][$col]['width'] ?? 1;
                $height = $sheet->cellInfo[$row][$col]['height'] ?? 1;
                $format = $sheet->cellInfo[$row][$col]['format'] ?? null;
                $style = $sheet->cellInfo[$row][$col]['style'];
                $class = $sheet->cellInfo[$row][$col]['class'];

                $updates[] = $row;
                $updates[] = $col;

                //Now that sheets have styles, many things can change and the cell not have a value.
                //if ( !$sheet->isEmpty( $row, $col ) )
                $inserts[] = [ (int)$this->id, $stamp, $row, $col, $value, $calc, $width, $height, $format, $style, $class, $user ];
            }
        }

        $updates[] = $sheet->getRowCount();
        $updates[] = $sheet->getColumnCount();

        $conditions = str_repeat("( rowIndex = ? AND columnIndex = ? ) OR ", ( count($updates) - 4 ) / 2);
        if ($prefs['feature_actionlog'] == 'y') { // must keep the previous value to do the difference
            $query = "SELECT `rowIndex`, `columnIndex`, `value`, `style`, `class` FROM `tiki_sheet_values` WHERE `sheetId` = ? AND  `end` IS NULL";
            $result = $tikilib->query($query, [$this->id]);
            $old = [];
            while ($row = $result->fetchRow()) {
                $old[$row['rowIndex'] . '-' . $row['columnIndex']] = $row['value'];
                if (isset($old[$row['rowIndex'] . '-' . $row['columnIndex']]['style'])) {
                    $old[$row['rowIndex'] . '-' . $row['columnIndex']]['style'] = $row['style'];
                }

                if (isset($old[$row['rowIndex'] . '-' . $row['columnIndex']]['class'])) {
                    $old[$row['rowIndex'] . '-' . $row['columnIndex']]['class'] = $row['class'];
                }
            }

            $tikilib->query("UPDATE `tiki_sheet_layout` SET `metadata` = ?  WHERE `sheetId` = ?", [$handler->metadata, $handler->id]);
        }

        $tikilib->query("UPDATE `tiki_sheet_values` SET `end` = ?  WHERE `sheetId` = ? AND `end` IS NULL AND ( {$conditions}`rowIndex` >= ? OR `columnIndex` >= ? )", $updates);

        if (count($inserts) > 0) {
            foreach ($inserts as $values) {
                $tikilib->query("INSERT INTO `tiki_sheet_values` (`sheetId`, `begin`, `rowIndex`, `columnIndex`, `value`, `calculation`, `width`, `height`, `format`, `style`, `class`, `user` ) VALUES( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )", $values);
            }
        }

        if ($prefs['feature_actionlog'] == 'y') {
            $logslib = TikiLib::lib('logs');
            $add = 0;
            $del = 0;
            foreach ($inserts as $values) {
                $add += strlen($values[4]);
                if (! empty($old[$values[2] . '-' . $values[3]])) {
                    $del += strlen($old[$values[2] . '-' . $values[3]]);
                }
            }
            if ($prefs['feature_contribution'] == 'y' && isset($_REQUEST['contributions'])) {
                $contributionlib = TikiLib::lib('contribution');
                $contributionlib->assign_contributions($_REQUEST['contributions'], $this->id, 'sheet', '', '', '');
            }
            if (isset($_REQUEST['contributions'])) {
                $logslib->add_action('Updated', $this->id, 'sheet', "add=$add&amp;del=$del&amp;sheetId=" . $this->id, '', '', '', '', $_REQUEST['contributions']);
            } else {
                $logslib->add_action('Updated', $this->id, 'sheet', "add=$add&amp;del=$del&amp;sheetId=" . $this->id);
            }
        }

        // }}}3

        return true;
    }

    /** setReadDate
     * Modifies the instant at which the snapshot of the
     * database is taken.
     * @param $timestamp A unix timestamp.
     */
    public function setReadDate($timestamp)
    {
        $this->readDate = $timestamp;
    }

    // supports
    public function supports($type)
    {
        return ( ( TIKISHEET_SAVE_DATA | TIKISHEET_SAVE_CALC | TIKISHEET_SAVE_CELL | TIKISHEET_SAVE_FORMAT | TIKISHEET_LOAD_DATA | TIKISHEET_LOAD_CALC | TIKISHEET_LOAD_CELL | TIKISHEET_LOAD_FORMAT ) & $type ) > 0;
    }

    // version
    public function version()
    {
        return "1.0";
    }
}
