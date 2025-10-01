<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// aris002@yahoo.co.uk
//namespace TikiLib\Socnets\LLOG;

if (str_contains($_SERVER['SCRIPT_NAME'], basename(__FILE__))) {
    header('location: index.php');
    exit;
}


require_once('lib/socnets/Util.php');
use TikiLib\Socnets\Util\Util;


function LLOG($msg, $msg2 = '')
{
    if ($msg2 === '') {
        Util::log($msg);
    } else {
        Util::log2($msg, $msg2);
    }
}
