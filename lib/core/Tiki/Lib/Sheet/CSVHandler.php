<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Sheet;

use Encoding;
use TikiLib;

require_once('lib/Sheet/grid.php');

/** CSVHandler
 * Class that stores the sheet representation in a
 * standard text file as a serialized PHP object.
 */
class CSVHandler extends DataHandler
{
    /**
     * @var string
     */
    public $type;
    public $data;
    public $name;
    public $id;
    public $encoding;
    /**
     * @var bool
     */
    public $truncated;
    public $file = 'php://stdout';
    public $lineLen;

    /** Constructor
     * Initializes the the serializer on a file.
     * @param $file The file path to save or load from.
     */
    public function __construct($fileInfo = "php://stdout", $inputEncoding = '', $outputEncoding = '', $lineLen = 1024)
    {
        $this->lineLen = $lineLen;
        $this->type = "file";

        if (is_array($fileInfo)) { // When loading from FileGalLib
            $this->data = strip_tags($fileInfo['data']);
            $this->name = $fileInfo['name'];
            $this->id = $fileInfo['fileId'];
        } else { // when FileInfo is in fact the name of a file
            $this->file = $fileInfo;
            if ($this->file === 'php://stdout' || $this->file === 'php://output') {
                $this->data = '';
            } else {
                $this->data = strip_tags(file_get_contents($this->file));
            }
        }

        $this->encoding = new Encoding($inputEncoding, $outputEncoding);
    }

    // _load
    public function load(Sheet &$sheet)
    {
        $rows = explode("\n", $this->data);
        for ($i = 0; $i < count($rows) && $i < $this->maxrows; $i++) {
            $cols = str_getcsv($rows[$i], escape: TikiLib::TIKI_GLOBAL_CSV_ESCAPE_CHAR);

            for ($j = 0; $j < count($cols) && $j < $this->maxcols; $j++) {
                $sheet->initCell($i, $j);

                if (! empty($cols[$j])) {
                    if ($cols[$j][0] == '=') {
                        $sheet->setCalculation(substr($cols[$j], 1));
                    } else {
                        $sheet->setValue($cols[$j]);
                    }
                } else {
                    $sheet->setValue("");
                }

                $sheet->setRowSpan(1);
                $sheet->setColSpan(1);
            }
        }

        if ($i >= $this->maxrows || $j >= $this->maxcols) {
            $this->truncated = true;
        }

        return true;
    }

    // _save
    public function save(&$sheet)
    {
        $total = [];

        ksort($sheet->dataGrid);
        foreach ($sheet->dataGrid as $row) {
            if (is_array($row)) {
                ksort($row);
                $values = (array)$row;
                array_walk($values, function (&$item) {
                    if (is_array($item) && array_key_exists('value', $item)) {
                        $item = $item['value'];
                    }
                });
                    $total[] = implode(",", $values);
            }
        }

        if (is_array($total)) {
            $total = implode("\n", $total);
        }

        $total = $this->encoding->convert_encoding($total);

        if ($this->file === 'php://stdout' || $this->file === 'php://output') {
            $this->output = $total;
            return true;
        } else {
            if ($file = @fopen($this->file, "w")) {
                if (! @fwrite($file, $total)) {
                    return false;
                }

                @fclose($file);
                return true;
            } else {
                return false;
            }
        }
    }

    // name
    public function name()
    {
        return tr("CSV File (commas)");
    }

    // supports
    public function supports($type)
    {
        return ( ( TIKISHEET_SAVE_DATA | TIKISHEET_LOAD_DATA ) & $type ) > 0;
    }

    // version
    public function version()
    {
        return "1.0";
    }
}
