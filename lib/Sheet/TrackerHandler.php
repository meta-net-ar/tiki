<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Sheet;

use TikiLib;
use Tracker_Definition;
use Tracker_Query;

require_once('lib/Sheet/grid.php');

/** TrackerHandler
 * Class that stores the sheet representation in a
 * standard text file as a serialized PHP object.
 */
class TrackerHandler extends DataHandler
{
    public $id;
    public $def;
    public $info;
    /**
     * @var string
     */
    public $type;
    public $file;
    public $lineLen;

    /** Constructor
     * Initializes the the serializer on a file.
     * @param $file The file path to save or load from.
     */
    public function __construct($trackerId)
    {
        $this->id = $trackerId;
        $this->def = Tracker_Definition::get($trackerId);
        $this->info = $this->def->getInformation();
        $this->type = "tracker";
        $this->cssName = 'readonly';
    }

    // _load
    public function load(&$sheet)
    {
        $i = 0;
        $trackerName = $this->info['name'];
        $tracker = Tracker_Query::tracker($trackerName)
            ->byName()
            ->excludeDetails()
            ->render(false)
            ->query();

        foreach ($tracker as $item) {
            $j = 0;
            foreach ($item as $key => $field) {
                $sheet->initCell($i, $j);

                if (! empty($field[0]) && $field[0] == '=') {
                    $sheet->setCalculation(substr($field, 1));
                }

                $sheet->setValue($i == 0 ? $key : $field);

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
        return $this->info['name'];
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
