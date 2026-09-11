<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\BlockHandler;

use Smarty\BlockHandler\Base;
use Smarty\Template;
use SmartyTiki\TikiSmartyExtensionInterface;

/**
 * Smarty plugin wiki
 * Smarty plugin to display wiki-parsed content
 *
 * Usage: {wiki}wiki text here{/wiki}
 * {wiki isHtml="true" }html text as stored by the wysiwyg editor here{/wiki}
 */
class Wiki extends Base implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'wiki';
    }

    public function handle($params, $content, Template $template, &$repeat)
    {
        if ($repeat) {
            return;
        }

        if ((isset($params['isHtml'])) and ($params['isHtml'] )) {
            $isHtml = true;
        } else {
            $isHtml = false;
        }
        $ret = \TikiLib::lib('parser')->parse_data($content, ['is_html' => $isHtml]);
        if (isset($params['line']) && $params['line'] == 1) {
            $ret = preg_replace(['/<br \/>$/', '/[\n\r]*$/'], '', $ret);
        }
        return $ret;
    }
}
