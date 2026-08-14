<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Sheet;

use APIC;

require_once('lib/Sheet/grid.php');

/** OpenOfficeHandler
 * Class to generate OpenOffice sxc documents.
 */
class OpenOfficeHandler extends DataHandler
{
    /** Constructor
     * Does nothing special.
     */
    public function __construct($file = "php://stdout")
    {
    }

    // _save
    public function save(&$sheet)
    {
        // Get rid of debug output
        ob_start();

        APIC::import("org.apicnet.io.OOo.objOOo.OOoTable");
        $OOoCalc = APIC::loadClass("org.apicnet.io.OOo.OOoDoc");

        $OOoCalc->newCalc();
        $OOoCalc->setName("export.sxc");
        $OOoCalc->meta->setCreator("TikiSheet");
        $OOoCalc->meta->setTitle(tr("TikiSheet Export"));

        $OOoCalc->content->addFeuille();

        foreach ($sheet->dataGrid as $rowIndex => $row) {
            foreach ($row as $columnIndex => $value) {
                $OOoCalc->content->addcellData($rowIndex + 1, $columnIndex + 1, ["DATA" => $value['value']]);
            }
        }

        $OOoCalc->save();
        $OOoCalc->close();
        ob_end_clean();

        $OOoCalc->download();

        return true;
    }

    // name
    public function name()
    {
        return tr("OpenOffice.org File");
    }


    // supports
    public function supports($type)
    {
        return ( ( TIKISHEET_SAVE_DATA ) & $type ) > 0;
    }

    // version
    public function version()
    {
        return "0.1-dev";
    }
}
