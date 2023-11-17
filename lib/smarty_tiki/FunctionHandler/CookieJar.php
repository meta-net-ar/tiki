<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

/**
 * smarty_function_cookie_jar handler: Get a cookie value from the Tiki Cookie Jar
 *
 * params:
 *    - name: Name of the cookie
 */
class CookieJar extends Base
{
    public function handle($params, Template $template)
    {
        if (empty($params['name'])) {
            return;
        }
        return getCookie($params['name']);
    }
}
