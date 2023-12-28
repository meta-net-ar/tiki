<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

/**
 * smarty modifier tra
 * -------------------
 * Purpose: Translate an English string
 */
class Tra
{
    /**
     * @param string $content English string
     * @param string $lg      language - if not specify = global current language
     * @param bool   $unused
     * @param array  $args
     *
     * @return mixed|string
     */
    public function handle($content, $lg = '', $unused = false, $args = [])
    {
        require_once(__DIR__ . '/../../init/tra.php');
        return tra($content, $lg, $unused, $args);
    }
}
