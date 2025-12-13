<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Diff;

use Tiki\Lib\Diff\Op\Add;
use Tiki\Lib\Diff\Op\Copy;
use Tiki\Lib\Diff\Op\Delete;

/**
 * Class used internally by Diff to actually compute the diffs.  This class
 * uses the xdiff PECL package (http://pecl.php.net/package/xdiff) to compute
 * the differences between the two input arrays.
 *
 * @author  Jon Parise <jon@horde.org>
 * @package Text_Diff
 * @access  private
 */
class EngineXdiff
{
    public function diff($from_lines, $to_lines)
    {
        /* Convert the two input arrays into strings for xdiff processing. */
        $from_string = implode("\n", $from_lines);
        $to_string = implode("\n", $to_lines);

        /* Diff the two strings and convert the result to an array. */
        $diff = xdiff_string_diff($from_string, $to_string, count($to_lines));
        $diff = explode("\n", $diff);

        /* Walk through the diff one line at a time.  We build the $edits
         * array of diff operations by reading the first character of the
         * xdiff output (which is in the "unified diff" format).
         *
         * Note that we don't have enough information to detect "changed"
         * lines using this approach, so we can't add Tiki\Lib\Diff\Op\Change
         * instances to the $edits array.  The result is still perfectly
         * valid, albeit a little less descriptive and efficient. */
        $edits = [];
        foreach ($diff as $line) {
            switch ($line[0]) {
                case ' ':
                    $edits[] = new Copy([substr($line, 1)]);
                    break;

                case '+':
                    $edits[] = new Add([substr($line, 1)]);
                    break;

                case '-':
                    $edits[] = new Delete([substr($line, 1)]);
                    break;
            }
        }

        return $edits;
    }
}
