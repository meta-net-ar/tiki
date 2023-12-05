<?php

namespace SmartyTiki\Filter\Output;

// / (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

class Sefurl implements \Smarty\Filter\FilterInterface
{
    public function filter($code, \Smarty\Template $template)
    {
        require_once('tiki-sefurl.php');
        return filter_out_sefurl($code);
    }
}
