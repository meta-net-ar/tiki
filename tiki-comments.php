<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

require_once('tiki-setup.php');



$headerlib = TikiLib::lib('header');
$headerlib->add_js_module('
        import "@vue-mf/comments";
    ');


$smarty->assign('mid', 'tiki-comments.tpl');
$smarty->display("tiki.tpl");
