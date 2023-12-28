<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

/**
 * Smarty modifier plugin to remove ~np~ tags from smarty variable. For use in templates used by the {list} wiki plugin
 *
 * - type:     modifier
 * - name:     nonp (short for "no nonparsed")
 * - purpose:  to return a usable string
 *
 * @param string to be replaced (optional)
 * @return corrected string
 *
 * Example: {if $row.title|nonp eq ''}
 */
class Nonp
{
    public function handle($string)
    {
        return preg_replace('/~[\/]?np~/', '', $string);
    }
}
