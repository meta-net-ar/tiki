<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// This file contains the compatible php versions for a tiki instance

/** The minimum PHP version supported by this version of Tiki. This is a hard limit.  Below this tiki will refuse to run.
 * If you update this, don't forget to update tiki-check.php
*/
const TIKI_MIN_PHP_VERSION = '8.1.0';

/** The maximum PHP minor version this branch is >expected< to support.  This is a soft limit.  If you exceed this, tiki may warn you, but will accept to run.
 */
const TIKI_MAX_SUPPORTED_PHP_VERSION = '8.4.99';

/** Beyond this (usually the next major PHP version), tiki will not even attempt to run.  Even developpers haven't tried this.
 *  * If you update this, don't forget to update tiki-check.php
 */
const TIKI_TOO_RECENT_PHP_VERSION = '9.0';

/**  */
const TIKI_PHP_CLI_VERSIONS_TO_SEARCH = [
        '8.1', //Should match TIKI_MIN_PHP_VERSION
        '8.2',
        '8.3',
        '8.4'  //Should match the majot of TIKI_MAX_SUPPORTED_PHP_VERSION
    ];
