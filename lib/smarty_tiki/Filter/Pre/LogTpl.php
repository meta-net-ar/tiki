<?php

// / (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Filter\Pre;

class LogTpl implements \Smarty\Filter\FilterInterface
{
    public function filter($source, \Smarty\Template $template)
    {
        global $prefs;
        $smarty = \TikiLib::lib('smarty');
        if ($prefs['log_tpl'] != 'y' || $smarty->getTemplateVars('log_tpl') === false) {
            return $source;
        }

        $resource = $smarty->template_resource;

        // Refrain from logging for some templates
        if (
            strpos($resource, 'eval:') === 0 || // Evaluated templates
            strpos($resource, 'mail/') !== false // email tpls
        ) {
            return $source;
        }

        // The opening comment cannot be inserted before the DOCTYPE in HTML documents; put it right after.
        $commentedSource = preg_replace('/^<!DOCTYPE .*>/i', '$0' . '<!-- TPL: ' . $resource . ' -->', $source, 1, $replacements);
        if ($replacements) {
            return $commentedSource . '<!-- /TPL: ' . $resource . ' -->';
        }

        return '<!-- TPL: ' . $resource . ' -->' . $source . '<!-- /TPL: ' . $resource . ' -->';
    }
}
