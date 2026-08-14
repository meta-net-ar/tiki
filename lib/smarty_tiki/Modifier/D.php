<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use Kint\Kint;
use SmartyTiki\Utils\SmartyKint;

/**
 * If installed, this modifier will use Kint (from https://github.com/kint-php/kint/)
 *
 *   You need to enable dev mode for composer by running `php console.php help dev:configure`
 *   and then setup.sh
 *
 * Example usage:
 *
 *     {$smarty.request|d}
 */
class D
{
    public function handle($var, $modifier = '')
    {
        if (is_callable('Kint::dump')) {
            // add this function as an alias of Kint::dump
            Kint::$aliases[] = 'smarty_modifier_d';
            // So far SmartyKin just replaces the ugly
            Kint::$plugins[] = new SmartyKint();

            switch ($modifier) {
                case '!':                   // Expand all data in this dump automatically
                    ! Kint::dump($var);
                    break;
                case '+':                   // Disable the depth limit in this dump
                    +Kint::dump($var);
                    break;
                case '-':                   // Clear buffered output and flush after dump
                    -Kint::dump($var);
                    break;
                case '@':                   // Return the output of this dump instead of echoing it
                    @Kint::dump($var);
                    break;
                case '~':                   // Use the text renderer for this dump
                    ~Kint::dump($var);
                    break;
                default:
                    Kint::$return = true;
                    Kint::$depth_limit = 8;
                    $return = Kint::dump($var);
                    echo str_replace(['~np~', '~/np~'], '', $return);
            }
        } else {
            // @phpstan-ignore disallowedFunctions.varDump (fallback debug output when Kint is not installed)
            var_dump($var);
        }
    }
}
