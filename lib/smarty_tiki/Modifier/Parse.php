<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

class Parse
{
    /**
     * Smarty parse modifier plugin
     * Type:     modifier
     * Name:     parse
     * Purpose:  Parse code in Tiki syntax
     *
     * @param boolean $simple true for less parsing, false for normal parsing
     *
     * @return string Parsed string
     */
    public function handle($string, $simple = false)
    {
        $parserlib = \TikiLib::lib('parser');
        if ($simple) {
            $string = htmlentities($string, ENT_QUOTES, 'UTF-8'); // Surely this should not be done here, if it is necessary. Chealer 2017-12-29
            return $parserlib->parse_data_simple($string);
        } else {
            return $parserlib->parse_data($string);
        }
    }
}
