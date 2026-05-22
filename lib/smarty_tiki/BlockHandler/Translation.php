<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\BlockHandler;

use Smarty\BlockHandler\Base;
use Smarty\Template;

/**
 * Smarty {translation lang=XX}{/translation} block plugin
 *
 * Type:     block function<br>
 * Name:     translation<br>
 * Purpose:  Support many languages in a template, only showing block
             if language matches
 * @param array
 * <pre>
 * Params:   lang: string (language, ex: en, pt-br)
 * </pre>
 * @param string contents of the block
 * @param Smarty clever simulation of a method
 * @return string string $content re-formatted
 */
class Translation extends Base
{
    public function handle($params, $content, Template $template, &$repeat)
    {
        if (! $repeat && ! empty($content)) {
            $lang = $params['lang'];
            $smarty = \TikiLib::lib('smarty');
            if ($smarty->getTemplateVars('language') == $lang) {
                $output = $content;

                // Only escape when explicitly requested with escape='y'
                if (! empty($params['escape']) && $params['escape'] === 'y') {
                    $output = htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
                }

                // Auto-detect wiki parsing context for noparse wrapping
                $shouldNoparse = false;

                // Explicit parameter takes precedence
                if (isset($params['noparse'])) {
                    $shouldNoparse = $params['noparse'] == 'y';
                } else {
                    // Auto-detect: if we're in a wiki parsing context, apply noparse automatically
                    $inWikiContext = $smarty->getTemplateVars('wiki_parse_context') === true;
                    if ($inWikiContext) {
                        $shouldNoparse = true;
                    }
                }

                if ($shouldNoparse) {
                    $output = '~np~' . $output . '~/np~';
                }

                return $output;
            } else {
                return '';
            }
        }
    }
}
