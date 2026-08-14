<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\WikiDiff;

/**
 * A class to format a WikiDiff as a pretty HTML unified diff.
 *
 * Usage:
 *
 * $diff = new Base($lines1, $lines2); // compute diff.
 *
 * $fmt = new UnifiedFormatter;
 * echo $fmt->format($diff, $lines1); // Output HTMLified unified diff.
 */
class UnifiedFormatter extends Formatter
{
    /**
     * @param bool $reverse
     * @param int $context_lines
     */
    public function __construct($reverse = false, $context_lines = 3)
    {
        $this->do_reverse_diff = $reverse;
        $this->context_lines = $context_lines;
        $this->context_prefix = '&nbsp;';
        $this->deletes_prefix = '-';
        $this->adds_prefix = '+';
    }

    /**
     * @param $xbeg
     * @param $xlen
     * @param $ybeg
     * @param $ylen
     * @return string
     */
    public function diffHeader($xbeg, $xlen, $ybeg, $ylen)
    {
        $xlen = $xlen == 1 ? '' : ",$xlen";
        $ylen = $ylen == 1 ? '' : ",$ylen";

        return "@@ -$xbeg$xlen +$ybeg$ylen @@";
    }
}
