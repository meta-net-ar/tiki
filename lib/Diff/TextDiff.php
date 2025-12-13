<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Diff;

use Tiki\Lib\Diff\Op\Copy;

/**
 * Base Class
 *
 * General API for generating and formatting diffs - the differences between
 * two sequences of strings.
 *
 * The PHP diff code used in this package was originally written by Geoffrey
 * T. Dairiki and is used with his permission.
 *
 * $Horde: framework/Text_Diff/Diff.php,v 1.8 2004/10/13 09:30:20 jan Exp $
 *
 * @package Text_Diff
 * @author  Geoffrey T. Dairiki <dairiki@dairiki.org>
 */
class TextDiff
{
    /**
     * Array of changes.
     *
     * @var array $edits
     * @var array $xind
     * @var array $yind
     * @var array $xv
     * @var array $yv
     * @var array $xchanged
     * @var array $ychanged
     */
    public $edits;
    public $xind;
    public $yind;
    public $xv;
    public $yv;
    public $xchanged;
    public $ychanged;

    /**
     * Computes diffs between sequences of strings.
     *
     * @param array $from_lines  An array of strings.  Typically these are
     *                           lines from a file.
     * @param array $to_lines    An array of strings.
     * @param boolean $forceNative
     */
    public function __construct($from_lines, $to_lines, $forceNative = false)
    {
        array_walk($from_lines, [$this, '_trimNewlines']);
        array_walk($to_lines, [$this, '_trimNewlines']);

        if (extension_loaded('xdiff') && ! $forceNative) {
            $engine = new EngineXdiff();
        } else {
            $engine = new EngineNative();
        }

        $this->edits = $engine->diff($from_lines, $to_lines);
    }

    /**
     * Returns the array of differences.
     */
    public function getDiff()
    {
        return $this->edits;
    }

    /**
     * Computes a reversed diff.
     *
     * Example:
     * <code>
     * $diff = new Base($lines1, $lines2);
     * $rev = $diff->reverse();
     * </code>
     *
     * @return Base A Diff object representing the inverse of the original diff.
     * Note that we purposely don't return a reference here,
     * since this essentially is a clone() method.
     */
    public function reverse()
    {
        if (version_compare(zend_version(), '2', '>')) {
            $rev = clone($obj);
        } else {
            $rev = $this;
        }
        $rev->edits = [];
        foreach ($this->edits as $edit) {
            $rev->edits[] = $edit->reverse();
        }
        return $rev;
    }

    /**
     * Checks for an empty diff.
     *
     * @return boolean  True if two sequences were identical.
     */
    public function isEmpty()
    {
        foreach ($this->edits as $edit) {
            if (! is_a($edit, Copy::class)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Computes the length of the Longest Common Subsequence (LCS).
     *
     * This is mostly for diagnostic purposed.
     *
     * @return int  The length of the LCS.
     */
    public function lcs()
    {
        $lcs = 0;
        foreach ($this->edits as $edit) {
            if (is_a($edit, Copy::class)) {
                $lcs += count($edit->orig);
            }
        }
        return $lcs;
    }

    /**
     * Gets the original set of lines.
     *
     * This reconstructs the $from_lines parameter passed to the constructor.
     *
     * @return array  The original sequence of strings.
     */
    public function getOriginal()
    {
        $lines = [];
        foreach ($this->edits as $edit) {
            if ($edit->orig) {
                array_splice($lines, count($lines), 0, $edit->orig);
            }
        }
        return $lines;
    }

    /**
     * Gets the final set of lines.
     *
     * This reconstructs the $to_lines parameter passed to the constructor.
     *
     * @return array  The sequence of strings.
     */
    public function getFinal()
    {
        $lines = [];
        foreach ($this->edits as $edit) {
            if ($edit->final) {
                array_splice($lines, count($lines), 0, $edit->final);
            }
        }
        return $lines;
    }

    /**
     * Removes trailing newlines from a line of text. This is meant to be used
     * with array_walk().
     *
     * @param string $line  The line to trim.
     * @param integer $key  The index of the line in the array. Not used.
     */
    public function _trimNewlines(&$line, $key)
    {
        $line = str_replace(["\n", "\r", "\r\n"], '', $line);
    }

    /**
     * Checks a diff for validity.
     *
     * This is here only for debugging purposes.
     */
    public function _check($from_lines, $to_lines)
    {
        if (serialize($from_lines) != serialize($this->getOriginal())) {
            trigger_error("Reconstructed original doesn't match", E_USER_WARNING);
        }
        if (serialize($to_lines) != serialize($this->getFinal())) {
            trigger_error("Reconstructed final doesn't match", E_USER_WARNING);
        }

        $rev = $this->reverse();
        if (serialize($to_lines) != serialize($rev->getOriginal())) {
            trigger_error("Reversed original doesn't match", E_USER_WARNING);
        }
        if (serialize($from_lines) != serialize($rev->getFinal())) {
            trigger_error("Reversed final doesn't match", E_USER_WARNING);
        }

        $prevtype = null;
        foreach ($this->edits as $edit) {
            if ($prevtype == get_class($edit)) {
                trigger_error("Edit sequence is non-optimal", E_USER_WARNING);
            }
            $prevtype = get_class($edit);
        }

        return true;
    }
}
