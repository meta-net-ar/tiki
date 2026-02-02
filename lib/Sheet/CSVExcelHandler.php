<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Sheet;

use Encoding;

require_once('lib/Sheet/grid.php');

 /** CSVExcelHandler
 * Class that stores the sheet representation in a
 * standard text file as a serialized PHP object. The difference
 * betwen this and standard CSV is that fields here are separarated by ';'
 */
class CSVExcelHandler extends DataHandler
{
    public $data;
    public $encoding;
    /**
     * @var bool
     */
    public $truncated;
    public $file;
    public $lineLen;

    /** Constructor
     * Initializes the the serializer on a file.
     * @param $file The file path to save or load from.
     */
    public function __construct($file = "php://stdout", $inputEncoding = '', $outputEncoding = '', $lineLen = 1024)
    {
        $this->file = $file;
        $this->lineLen = $lineLen;
        $this->data = strip_tags(file_get_contents($this->file));
        $this->encoding = new Encoding($inputEncoding, $outputEncoding);
    }

    // _load
    public function load(&$sheet)
    {
        $rows = explode("\n", $this->data);
        for ($i = 0; $i < count($rows) && $i < $this->maxrows; $i++) {
            $cols = preg_split("/[,;](?!(?:[^\\\",;]|[^\\\"],[^\\\"])+\\\")/", $rows[$i]);

            for ($j = 0; $j < count($cols) && $j < $this->maxcols; $j++) {
                $sheet->initCell($i, $j);
                $sheet->setValue($cols[$j]);

                if (isset($cols[$j])) {
                    if (strlen($cols[$j])) {
                        if ($cols[$j][0] == '=') {
                            $sheet->setCalculation(substr($cols[$j], 1));
                        }
                    }
                }

                $sheet->setColSpan(1, 1);
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

        foreach ($sheet->dataGrid as $row) {
            $values = (array)$row;
            array_walk($values, function (&$item) {
                if (is_array($item) && array_key_exists('value', $item)) {
                    $item = $item['value'];
                }
            });

            $total[] = implode(';', $values);
        }

        if (is_array($total)) {
            $total = implode("\n", $total);
        }

        $total = $this->encoding->convert_encoding($total);

        if ($this->file == "php://stdout") {
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
        return tr("CSV-Excel File (semicolons)");
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

    private function fputcsvexcel($row, $fd = ';', $quot = '"', $limit = null)
    {
        $str = '';
        $i = 0;
        foreach ($row as $col) {
            if ($i && $i < $limit) {
                $cell = ($col->formula ? $col->formula : $col->value);
                str_replace(
                    [$quot,        "\n"],
                    [$quot . $quot,  ''],
                    $cell
                );

                if (strchr($cell, $fd)) {
                    $str .= $quot . $cell . $quot . $fd;
                } else {
                    $str .= $cell . $fd;
                }
            }
            $i++;
        }

        return  $str;
    }
}
