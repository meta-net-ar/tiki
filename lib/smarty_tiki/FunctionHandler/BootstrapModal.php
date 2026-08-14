<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

/**
 * BootstrapModal handler
 * ----------------------
 */
class BootstrapModal extends Base
{
    /**
     * Returns a string with the href and data attributes to make a bootstrap modal appear on a link
     * Note: Expects to be inside a "double quoted" href attribute in an html anchor
     *
     * @param array $params [size => 'modal-sm|modal-lg|modal-xl' (default: 'modal-md')]
     * @param \Smarty\Template $template
     *
     * @return string href attribute contents
     * @throws \Smarty\Exception
     */
    public function handle($params, Template $template)
    {
        if (! empty($params['size'])) {
            $size = '" data-size="' . $params['size'];
            unset($params['size']);
        } else {
            $size = '';
        }
        $params['modal'] = 1;
        $href = smarty_function_service($params, $template);
        return "$href\" data-tiki-bs-toggle=\"modal\" data-bs-backdrop=\"static\" data-bs-target=\".footer-modal.fade:not(.show):first$size";
    }
}
