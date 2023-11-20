<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

/*
 * ignore syntax lines in tpls and other smarty parsed code
 * they are used to distinguish if content is markdown or tiki
 */
class Syntax extends Base
{
    public function handle($params, Template $template)
    {
        return '';
    }
}
