<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

/**
 * Smarty plugin
 * -------------------------------------------------------------
 * Type:     modifier
 * Name:     tiki_date_format
 * Purpose:  format a string representing a moment (timezone adjusted to user specified timezone)
 * Input:    string: string representing a moment
 *           format: strftime() format for output. For standard formats, see modifiers tiki_{long,short}_{date,datetime,time}.
 *           _user: if specified, use this user timezone instead of the current user
 * -------------------------------------------------------------
 */
class TikiDateFormat
{
    public function handle($string, $format, $_user = false)
    {
        return \TikiLib::date_format(tra($format), $string, $_user);
    }
}
