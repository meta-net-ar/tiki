<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @param $string the string representing the date to be formatted.
 * @param $format the desired date format.
 * @param $_user if specified, use this user's timezone instead of the current user's, if specified forceTimezone must be false.
 * @param $forceTimezone the time zone to be applied. Can be a timezone identifier or false, if user is not false forceTimezone must always be false.
 */

function smarty_modifier_tiki_date_format($string, $format, $_user = false, $forceTimezone = false)
{
    $tikiDateFormatModifier = new \SmartyTiki\Modifier\TikiDateFormat();
    return $tikiDateFormatModifier->handle($string, $format, $_user, $forceTimezone);
}
