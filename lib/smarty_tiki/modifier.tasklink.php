<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// Martin Hausner

function smarty_modifier_tasklink($taskId, $class_name = "link", $offset = "0", $sort_mode = "priority_desc")
{
    $smartyTikiExtension = new \SmartyTiki\Extension\SmartyTikiExtension();
    return $smartyTikiExtension->smarty_modifier_tasklink($taskId, $class_name, $offset, $sort_mode);
}
