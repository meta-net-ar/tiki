<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//this script may only be included - so its better to die if called directly.
if (str_contains($_SERVER['SCRIPT_NAME'], basename(__FILE__))) {
    header('location: index.php');
    exit;
}

/**
 * Class Table_Code_WidgetOptionsOutput
 *
 * Creates the code for the output (export) widget options portion of the Tablesorter jQuery code
 *
 * @package Tiki
 * @subpackage Table
 * @uses Table_Code_WidgetOptions
 */
class Table_Code_WidgetOptionsOutput extends Table_Code_WidgetOptions
{
    protected function getOptionArray()
    {
        $pre = 'output_';
        $m = [];

        if (parent::$output) {
            foreach (parent::$s['output'] as $key => $val) {
                if ($key === 'button') {
                    if (isset(parent::$s['output']['button']['id'])) {
                        $m[] = $pre . $key . ' : "button#' . parent::$s['output']['button']['id'] . '"';
                    }
                } elseif (! is_numeric($key)) {
                    $m[] = $pre . $key . ' : "' . $val . '"';
                }
            }
        }

        return $m;
    }
}
