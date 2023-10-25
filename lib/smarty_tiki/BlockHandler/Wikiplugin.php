<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\BlockHandler;

use Smarty\BlockHandler\BlockHandlerInterface;
use Smarty\Template;

class Wikiplugin implements BlockHandlerInterface
{
    public function handle($params, $content, Template $template, &$repeat = false)
    {

        if ($repeat) {
            return '';
        }

        if (! isset($params['_name'])) {
            return '<div class="alert alert-warning">' . tra('Plugin name not specified.') . '</div>';
        }

        $name = $params['_name'];
        unset($params['_name']);

        if (! empty($params['_compactArguments_'])) {
            $params = $params['_compactArguments_'];
        }

        $parserlib = \TikiLib::lib('parser');
        $out = $parserlib->pluginExecute(
            $name,
            $content,
            $params,
            0,
            false,
            [
                'context_format' => 'html',
                'wysiwyg' => false,
                'is_html' => true
            ]
        );
        $parserlib->setOptions();
        return $out;
    }

    public function isCacheable(): bool
    {
        return true;
    }
}
