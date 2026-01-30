<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// This file contains the compatible php versions for a tiki instance
if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
    return;
}

/** The minimum PHP version supported by this version of Tiki. This is a hard limit.  Below this tiki will refuse to run.
 * If you update this, don't forget to update tiki-check.php
*/
const TIKI_MIN_PHP_VERSION = '7.4.0';

/** Beyond this (usually the next major PHP version), tiki will not even attempt to run.  Even developpers haven't tried this.
 *  * If you update this, don't forget to update tiki-check.php
 */
const TIKI_TOO_RECENT_PHP_VERSION = '8.0.0';
