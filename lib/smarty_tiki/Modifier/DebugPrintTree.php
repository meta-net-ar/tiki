<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

/**
 * Smarty plugin
 * -------------------------------------------------------------
 * Type:     modifier
 * Name:     debug_print_tree
 * Purpose:  print_r a vaiable in a collapsible tree
 * -------------------------------------------------------------
 */
class DebugPrintTree
{
    public function handle($data)
    {
        $out = print_r($data, true); // @phpstan-ignore disallowedFunctions.printR (intentional Smarty debug modifier — formats data tree as string for template rendering)
        $out = preg_replace_callback('/([ \t]*)(\[[^\]]+\][ \t]*\=\>[ \t]*[a-z0-9 \t_]+)\n[ \t]*\(/iU', [$this, 'replaceCallback'], $out);
        $out = preg_replace_callback('/(.*)([ \t]*[a-z0-9 \t_]+)\n[ \t]*\(/iU', [$this, 'replaceCallback'], $out);
        $out = preg_replace('/^\s*\)\s*$/m', '</div>', $out);
        return $out;
    }

    protected function replaceCallback($matches)
    {
        $id = uniqid();
        return "$matches[1]<a href=\"#\" onclick=\"$('#$id').toggle(); return false;\">$matches[2]</a><div id='$id' style=\"display: none;\">";
    }
}
