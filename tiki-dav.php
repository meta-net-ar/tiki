<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\SabreDav\BasicAuth;
use Tiki\SabreDav\Utilities;

define('TIKI_CALDAV', true);

require_once 'tiki-setup.php';
TikiLib::setExternalContext(true);
$access->check_feature('feature_calendar');
$authBackend = new BasicAuth();
$server = Utilities::buildSabreDavServer($authBackend, 'both');
$server->start();
