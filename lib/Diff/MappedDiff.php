<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Diff;

/**
 * $Horde: framework/Text_Diff/Diff.php,v 1.8 2004/10/13 09:30:20 jan Exp $
 *
 * @package Text_Diff
 * @author  Geoffrey T. Dairiki <dairiki@dairiki.org>
 */
class MappedDiff extends TextDiff
{
    public $edits;
    /**
     * Computes a diff between sequences of strings.
     *
     * This can be used to compute things like case-insensitve diffs, or diffs
     * which ignore changes in white-space.
     *
     * @param array $from_lines         An array of strings.
     * @param array $to_lines           An array of strings.
     * @param array $mapped_from_lines  This array should have the same size
     *                                  number of elements as $from_lines.  The
     *                                  elements in $mapped_from_lines and
     *                                  $mapped_to_lines are what is actually
     *                                  compared when computing the diff.
     * @param array $mapped_to_lines    This array should have the same number
     *                                  of elements as $to_lines.
     */
    public function __construct(
        $from_lines,
        $to_lines,
        $mapped_from_lines,
        $mapped_to_lines
    ) {

        // assert(count($from_lines) == count($mapped_from_lines));
        // assert(count($to_lines) == count($mapped_to_lines));

        parent::__construct($mapped_from_lines, $mapped_to_lines);

        $xi = $yi = 0;
        for ($i = 0, $count_edits = count($this->edits); $i < $count_edits; $i++) {
            $orig = &$this->edits[$i]->orig;
            if (is_array($orig)) {
                $orig = array_slice($from_lines, $xi, count($orig));
                $xi += count($orig);
            }

            $final = &$this->edits[$i]->final;
            if (is_array($final)) {
                $final = array_slice($to_lines, $yi, count($final));
                $yi += count($final);
            }
        }
    }
}
