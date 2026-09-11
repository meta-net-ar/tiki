<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\WikiDiff;

/**
 * Class representing a diff between two files.
 */
class Base
{
    public $edits;

    /**
     * Set to true only for debugging.
     * Do not enable in production – may cause blank pages.
     * @var bool
     */
    private const USE_ASSERTS = false;

    /**
     * Compute diff between files (or deserialize serialized WikiDiff.)
     */
    public function __construct($from_lines = false, $to_lines = false)
    {
        if ($from_lines && $to_lines) {
            $compute = new Engine($from_lines, $to_lines);
            $this->edits = $compute->edits;
        } elseif ($from_lines) {
            // $from_lines is not really from_lines, but rather
            // a serialized WikiDiff.
            $this->edits = unserialize($from_lines);
        } else {
            $this->edits = [];
        }
    }

    /**
     * Compute reversed WikiDiff.
     *
     * SYNOPSIS:
     *
     * $diff = new self($lines1, $lines2);
     * $rev = $diff->reverse($lines1);
     *
     * // reconstruct $lines1 from $lines2:
     * $out = $rev->apply($lines2);
     */
    public function reverse($from_lines)
    {
        $x = 0;
        $rev = new self();

        for (reset($this->edits), $currentedits = current($this->edits); $edit = $currentedits; next($this->edits)) {
            if (is_array($edit)) { // Was an add, turn it into a delete.
                $nadd = count($edit);
                self::USE_ASSERTS && assert($nadd > 0);
                $edit = -$nadd;
            } elseif ($edit > 0) {
                // Was a copy --- just pass it through. }
                $x += $edit;
            } elseif ($edit < 0) { // Was a delete, turn it into an add.
                $ndelete = -$edit;
                $edit = [];
                while ($ndelete-- > 0) {
                    $edit[] = '' . $from_lines[$x++];
                }
            } else {
                die('assertion error');
            }

            $rev->edits[] = $edit;
        }

        return $rev;
    }

    /**
     * Compose (concatenate) WikiDiffs.
     *
     * SYNOPSIS:
     *
     * $diff1 = new self($lines1, $lines2);
     * $diff2 = new self($lines2, $lines3);
     * $comp = $diff1->compose($diff2);
     *
     * // reconstruct $lines3 from $lines1:
     * $out = $comp->apply($lines1);
     */
    public function compose($that)
    {
        reset($this->edits);
        reset($that->edits);

        $comp = new self();
        $left = current($this->edits);
        $right = current($that->edits);

        while ($left || $right) {
            if (! is_array($left) && $left < 0) { // Left op is a delete.
                $newop = $left;
                $left = next($this->edits);
            } elseif (is_array($right)) { // Right op is an add.
                $newop = $right;
                $right = next($that->edits);
            } elseif (! $left || ! $right) {
                die('assertion error');
            } elseif (! is_array($left) && $left > 0) { // Left op is a copy.
                if ($left <= abs($right)) {
                    $newop = $right > 0 ? $left : -$left;
                    $right -= $newop;
                    if ($right == 0) {
                        $right = next($that->edits);
                    }
                    $left = next($this->edits);
                } else {
                    $newop = $right;
                    $left -= abs($right);
                    $right = next($that->edits);
                }
            } else { // Left op is an add.
                if (! is_array($left)) {
                    die('assertion error');
                }
                $nleft = count($left);

                if ($nleft <= abs($right)) {
                    if ($right > 0) { // Right op is copy
                        $newop = $left;
                        $right -= $nleft;
                    } else { // Right op is delete
                        $newop = false;
                        $right += $nleft;
                    }
                    if ($right == 0) {
                        $right = next($that->edits);
                    }
                    $left = next($this->edits);
                } else {
                    unset($newop);
                    if ($right > 0) {
                        for ($i = 0; $i < $right; $i++) {
                            $newop[] = $left[$i];
                        }
                    }

                    $tmp = [];
                    for ($i = abs($right); $i < $nleft; $i++) {
                        $tmp[] = $left[$i];
                    }

                    $left = $tmp;
                    $right = next($that->edits);
                }
            }

            if (! $op) {
                $op = $newop;
                continue;
            }

            if (! $newop) {
                continue;
            }

            if (is_array($op) && is_array($newop)) {
                // Both $op and $newop are adds.
                for ($i = 0, $sizeof_newop = count($newop); $i < $sizeof_newop; $i++) {
                    $op[] = $newop[$i];
                }
            } elseif (($op > 0 && $newop > 0) || ($op < 0 && $newop < 0)) {
                // $op and $newop are both either deletes or copies.
                $op += $newop;
            } else {
                $comp->edits[] = $op;
                $op = $newop;
            }
        }
        if ($op) {
            $comp->edits[] = $op;
        }

        return $comp;
    }

    /* Debugging only:
    function _dump ()
    {
        echo "<ol>";
        for (reset($this->edits); $edit = current($this->edits); next($this->edits)) {
            echo "<li>";
            if ($edit > 0)
                echo "Copy $edit";
            else if ($edit < 0)
                echo "Delete " . -$edit;
            else if (is_array($edit)) {
                echo "Add " . sizeof($edit) . "<ul>";
                for ($i = 0; $i < sizeof($edit); $i++)
                    echo "<li>" . htmlspecialchars($edit[$i]);
                echo "</ul>";
            } else
                die("assertion error");
        }
        echo "</ol>";
    }
    */

    /**
     * Apply a WikiDiff to a set of lines.
     *
     * SYNOPSIS:
     *
     * $diff = new self($lines1, $lines2);
     *
     * // reconstruct $lines2 from $lines1:
     * $out = $diff->apply($lines1);
     */
    public function apply($from_lines)
    {
        $x = 0;
        $xlim = count($from_lines);

        for (reset($this->edits); $edit = current($this->edits); next($this->edits)) {
            if (is_array($edit)) {
                reset($edit);
                foreach ($edit as $line) {
                    $output[] = $line;
                }
            } elseif ($edit > 0) {
                while ($edit--) {
                    $output[] = $from_lines[$x++];
                }
            } else {
                $x += -$edit;
            }
        }

        if ($x != $xlim) {
            $msg = tra(sprintf('Tiki\Lib\WikiDiff\Base::apply: line count mismatch: %s != %s', $x, $xlim));
            throw new \Exception($msg);
        }

        return $output;
    }

    /**
     * Serialize a WikiDiff.
     *
     * SYNOPSIS:
     *
     * $diff = new self($lines1, $lines2);
     * $string = $diff->serialize;
     *
     * // recover WikiDiff from serialized version:
     * $diff2 = new self($string);
     */
    public function serialize()
    {
        return serialize($this->edits);
    }

    /**
     * Return true if two files were equal.
     */
    public function isEmpty()
    {
        if (count($this->edits) > 1) {
            return false;
        }
        if (count($this->edits) == 0) {
            return true;
        }
        // Test for: only edit is a copy.

        return ! is_array($this->edits[0]) && $this->edits[0] > 0;
    }

    /**
     * Compute the length of the Longest Common Subsequence (LCS).
     *
     * This is mostly for diagnostic purposed.
     */
    public function lcs()
    {
        $lcs = 0;
        for (reset($this->edits), $currentedit = current($this->edits); $edit = $currentedit; next($this->edits)) {
            if (! is_array($edit) && $edit > 0) {
                $lcs += $edit;
            }
        }

        return $lcs;
    }

    /**
     * Check a WikiDiff for validity.
     *
     * This is here only for debugging purposes.
     */
    public function check($from_lines, $to_lines)
    {
        $test = $this->apply($from_lines);
        if (serialize($test) != serialize($to_lines)) {
            throw new \Exception(tra('Tiki\Lib\WikiDiff\Base::_check: failed'));
        }

        reset($this->edits);
        $prev = current($this->edits);
        $prevtype = is_array($prev) ? 'a' : ($prev > 0 ? 'c' : 'd');

        while ($edit = next($this->edits)) {
            $type = is_array($edit) ? 'a' : ($edit > 0 ? 'c' : 'd');
            if ($prevtype == $type) {
                throw new \Exception(tra('Tiki\Lib\WikiDiff\Base::_check: edit sequence is non-optimal'));
            }

            $prevtype = $type;
        }
        $lcs = $this->lcs();
        printf('<strong>' . tra('Tiki\Lib\WikiDiff\Base Okay: LCS = %s') . "</strong>\n", $lcs);
    }
}
