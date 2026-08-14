<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Webservices\Base;

require_once 'tiki-setup.php';

$access->check_feature('feature_webservices');

if (is_null($_GET['wsdl'])) {
    $protocol = isset($_SERVER['HTTPS']) ? 'https' : 'http';
    $server = new Laminas\Soap\Server($protocol . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME'] . '?wsdl');
    $server->setClass(Base::class);
    $server->handle();
} else {
    $wsdl = new Laminas\Soap\AutoDiscover();
    $wsdl->setUri($_SERVER['SCRIPT_NAME']);
    $wsdl->setClass(Base::class);
    $wsdl->handle();
}
