<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Sheet;

/** DataFormat Class
 * Class containing the different supported data formats by Sheet.
 * The formats coded in this class should also exist in lib/Sheet/formula.js
 */
class DataFormat
{
    public function currency($value, $before = '', $after = '')
    {
        return $before . sprintf("%.2f", (float)$value) . $after;
    }

    public function currency_ca($value)
    {
        return $this->currency($value, '', '$');
    }

    public function currency_us($value)
    {
        return $this->currency($value, '$');
    }
}
