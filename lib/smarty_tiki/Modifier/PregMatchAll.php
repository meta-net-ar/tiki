<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;

/**
 * Smarty modifier preg_match_all
 * ------------------------------
 * Purpose: Perform a global regular expression match
 */
class PregMatchAll implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'preg_match_all';
    }

    /**
     * @param string $pattern The pattern to search for, as a string.
     * @param string $subject The input string.
     * @param array $matches
     * @param int $flags
     * @param int $offset
     * @return array matches
     * @see https://php.net/manual/en/function.preg-match-all.php for details about the params description
     */
    public function handle($pattern, $subject, $matches = null, $flags = 0, $offset = 0)
    {
        // return matches here as Smarty doesn't support modifier or function arguments by reference
        preg_match_all($pattern, $subject, $matches, $flags, $offset);
        return $matches;
    }
}
