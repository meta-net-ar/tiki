<?php

// / (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Filter\Pre;

/**
 * Smarty prefilter jq
 * -------------------------------------------------------------
 * Prefilter {jq} contents - replace {{ with {literal} etc
 *
 * Doesn't check $prefs['feature_jquery'] here as prefilter only loaded if enabled (in lib/setup/javascript.php)
 * -------------------------------------------------------------
 */
class Jq implements \Smarty\Filter\FilterInterface
{
    public function filter($source, \Smarty\Template $template)
    {
        if (strpos($source, '{jq') === false) {
            return $source;         // quick escape if no jq tags
        }
        $return = preg_replace_callback('/(?s)(\{jq.*?\})(.+?)\{\/jq\}/', [$this, 'escapeSmartyJq'], $source);

        return $return;
    }

    private function escapeSmartyJq($key)
    {
        $s = $key[2];
        if (preg_match('/\{literal\}/Ums', $s)) {
            return $key[1] . $s . '{/jq}';  // don't parse {{s if already escaped
        }
        $s = preg_replace('/(?s)\{\*.*?\*\}/', '', $s);
        $s = preg_replace('/(?s)\{\{/', '{/literal}{', $s);                 // replace {{ with {/literal}{ and wrap with {literal}
        $s = preg_replace('/(?s)\}\}/', '}{literal}', $s);                  // close }}s
        $s = preg_replace('/(?s)\{literal\}\s*\{\/literal\}/', '', $s);     // remove empties
        return ! empty($s) ? $key[1] . '{literal}' . $s . '{/literal}{/jq}' : '';   // wrap
    }
}
