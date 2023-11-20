<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

/**
 * \brief Smarty plugin to return plural or singular form of given word based on count
 * Usage format {pluralize word_count=2 singular_form="mouse" plural_form="mice"}
 *
 */
class Pluralize extends Base
{
    public function handle($params, Template $template)
    {
        if (empty($params['singular_form']) || ! isset($params['word_count'])) {
            return;
        }

        if (empty($params['plural_form'])) {
            $params['plural_form'] = $params['singular_form'] . 's';
        }

        return ($params['word_count'] == 1) ? $params['singular_form'] : $params['plural_form'];
    }
}
