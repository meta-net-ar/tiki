<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 *
 * smarty_block_mailurl creates a full URL for use in mail notifications
 * by prepending the base URL (mail_machine_raw) to the content.
 *
 * Usage:
 *  {mailurl}tiki-user_watches.php?id={$watchId}&hash={$watchUnsubscribeHash}{/mailurl}
 */
function smarty_block_mailurl($params, $content, $smarty, &$repeat)
{
    if ($repeat || $content === null) {
        return;
    }

    $mail_machine_raw = $smarty->getTemplateVars('mail_machine_raw');

    return rtrim((string) $mail_machine_raw, '/') . '/' . ltrim($content, '/');
}
