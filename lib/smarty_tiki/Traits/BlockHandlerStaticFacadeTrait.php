<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Traits;

/**
 * @method mixed handle($params, $content, \Smarty\Template $template, &$repeat)
 */
trait BlockHandlerStaticFacadeTrait
{
    /**
     * Static facade for calling this handler from PHP code without a template.
     */
    public static function render(array $params, $content, ?\Smarty\Template $template = null, &$repeat = false)
    {
        if ($template === null) {
            $template = \TikiLib::lib('smarty')->getEmptyInternalTemplate();
        }

        $className = static::class;

        return (new $className())->handle($params, $content, $template, $repeat);
    }
}
