<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;
use SmartyTiki\Traits\ModifierStaticFacadeTrait;

/**
 * Smarty plugin
 * -------------------------------------------------------------
 * Type:     modifier
 * Name:     tiki_date_format
 * Purpose:  format a string representing a moment (timezone adjusted to user specified timezone)
 * Input:    string: string representing a moment
 *           format: strftime() format for output. For standard formats, see modifiers tiki_{long,short}_{date,datetime,time}.
 *           _user: if specified, use this user timezone instead of the current user
 *           forceTimezone: the time zone to be applied, if you prefer to use a specific timezone.
 * -------------------------------------------------------------
 */
class TikiDateFormat implements TikiSmartyExtensionInterface
{
    use ModifierStaticFacadeTrait;

    public static function getSmartyName(): string
    {
        return 'tiki_date_format';
    }

    /**
     * @param $string the string representing the date to be formatted.
     * @param $format the desired date format.
     * @param $_user if specified, use this user's timezone instead of the current user's, if specified forceTimezone must be false.
     * @param $forceTimezone the time zone to be applied. Can be a timezone identifier or false, if user is not false forceTimezone must always be false.
     */
    public function handle($string, $format, $_user = false, $forceTimezone = false)
    {
        return \TikiLib::date_format(tra($format), $string, $_user, 5, true, $forceTimezone);
    }
}
