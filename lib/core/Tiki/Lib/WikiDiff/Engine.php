<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// A PHP diff engine for phpwiki.
//
// Copyright (C) 2000 Geoffrey T. Dairiki <dairiki@dairiki.org>
// You may copy this code freely under the conditions of the GPL.
namespace Tiki\Lib\WikiDiff;

/**
 * Class used internally by WikiDiff to actually compute the diffs.
 *
 * The algorithm used here is mostly lifted from the perl module
 * Algorithm::Diff (version 1.06) by Ned Konz.
 *
 * More ideas are taken from:
 *   http://www.ics.uci.edu/~eppstein/161/960229.html
 *
 * Some ideas are (and a bit of code) are from from analyze.c, from GNU
 * diffutils-2.7, which can be found at:
 *   ftp://gnudist.gnu.org/pub/gnu/diffutils/diffutils-2.7.tar.gz
 *
 * Finally, some ideas (subdivision by NCHUNKS > 2, and some optimizations)
 * are my own.
 */
class Engine
{
    /**
     * @var int[]
     */
    public $yind;
    /**
     * @var int[]
     */
    public $xind;
    public $lcs;
    public $seq;
    public $in_seq;
    public $edits;    // List of editing operation to convert XV to YV.
    public $xv = [];
    public $yv = [];
    public $xchanged = [];
    public $ychanged = [];

    /**
     * Set to true only for debugging.
     * Do not enable in production – may cause blank pages.
     * @var bool
     */
    private const USE_ASSERTS = false;

    /**
     * @param $from_lines
     * @param $to_lines
     */
    public function __construct($from_lines, $to_lines)
    {
        $n_from = count($from_lines);
        $n_to = count($to_lines);
        $endskip = 0;

        // Ignore differences in line endings
        for ($i = 0; $i < $n_from; $i++) {
            $from_lines[$i] = rtrim($from_lines[$i]);
        }

        for ($i = 0; $i < $n_to; $i++) {
            $to_lines[$i] = rtrim($to_lines[$i]);
        }

        // Ignore trailing and leading matching lines.
        while ($n_from > 0 && $n_to > 0) {
            if ($from_lines[$n_from - 1] != $to_lines[$n_to - 1]) {
                break;
            }
            $n_from--;
            $n_to--;
            $endskip++;
        }

        for ($skip = 0, $min_from_to = min($n_from, $n_to); $skip < $min_from_to; $skip++) {
            if ($from_lines[$skip] != $to_lines[$skip]) {
                break;
            }
        }
        $n_from -= $skip;
        $n_to -= $skip;

        $xlines = [];
        $ylines = [];

        // Ignore lines which do not exist in both files.
        for ($x = 0; $x < $n_from; $x++) {
            $xhash[$from_lines[$x + $skip]] = 1;
        }

        for ($y = 0; $y < $n_to; $y++) {
            $line = $to_lines[$y + $skip];
            $ylines[] = $line;
            if (($this->ychanged[$y] = empty($xhash[$line]))) {
                continue;
            }
            $yhash[$line] = 1;
            $this->yv[] = $line;
            $this->yind[] = $y;
        }

        for ($x = 0; $x < $n_from; $x++) {
            $line = $from_lines[$x + $skip];
            $xlines[] = $line;
            if (($this->xchanged[$x] = empty($yhash[$line]))) {
                continue;
            }
            $this->xv[] = $line;
            $this->xind[] = $x;
        }

        // Find the LCS.
        $this->compareSeq(0, count($this->xv), 0, count($this->yv));

        // Merge edits when possible
        $this->shiftBoundaries($xlines, $this->xchanged, $this->ychanged);
        $this->shiftBoundaries($ylines, $this->ychanged, $this->xchanged);

        // Compute the edit operations.
        $this->edits = [];
        if ($skip) {
            $this->edits[] = $skip;
        }

        $x = 0;
        $y = 0;

        while ($x < $n_from || $y < $n_to) {
            self::USE_ASSERTS && assert($y < $n_to || $this->xchanged[$x]);
            self::USE_ASSERTS && assert($x < $n_from || $this->ychanged[$y]);

            // Skip matching "snake".
            $x0 = $x;
            $ncopy = 0;

            while ($x < $n_from && $y < $n_to && ! $this->xchanged[$x] && ! $this->ychanged[$y]) {
                ++$x;
                ++$y;
                ++$ncopy;
            }

            if ($x > $x0) {
                $this->edits[] = $x - $x0;
            }

            // Find deletes.
            $x0 = $x;
            $ndelete = 0;
            while ($x < $n_from && $this->xchanged[$x]) {
                ++$x;
                ++$ndelete;
            }
            if ($x > $x0) {
                $this->edits[] = -($x - $x0);
            }

            // Find adds.
            if (isset($this->ychanged[$y]) && $this->ychanged[$y]) {
                $adds = [];
                while ($y < $n_to && $this->ychanged[$y]) {
                    $adds[] = '' . $ylines[$y++];
                }
                $this->edits[] = $adds;
            }
        }

        if ($endskip != 0) {
            $this->edits[] = $endskip;
        }
    }

    /**
     * Divide the Largest Common Subsequence (LCS) of the sequences
     * [XOFF, XLIM) and [YOFF, YLIM) into NCHUNKS approximately equally
     * sized segments.
     *
     * Returns (LCS, PTS). LCS is the length of the LCS. PTS is an
     * array of NCHUNKS+1 (X, Y) indexes giving the diving points between
     * sub sequences. The first sub-sequence is contained in [X0, X1),
     * [Y0, Y1), the second in [X1, X2), [Y1, Y2) and so on. Note
     * that (X0, Y0) == (XOFF, YOFF) and
     * (X[NCHUNKS], Y[NCHUNKS]) == (XLIM, YLIM).
     *
     * This function assumes that the first lines of the specified portions
     * of the two files do not match, and likewise that the last lines do not
     * match. The caller must trim matching lines from the beginning and end
     * of the portions it is going to specify.
     */
    protected function diag($xoff, $xlim, $yoff, $ylim, $nchunks)
    {
        $flip = false;

        if ($xlim - $xoff > $ylim - $yoff) {
            // Things seems faster (I'm not sure I understand why)
            // when the shortest sequence in X.
            $flip = true;
            list ($xoff, $xlim, $yoff, $ylim) = [$yoff, $ylim, $xoff, $xlim];
        }

        if ($flip) {
            for ($i = $ylim - 1; $i >= $yoff; $i--) {
                $ymatches[$this->xv[$i]][] = $i;
            }
        } else {
            for ($i = $ylim - 1; $i >= $yoff; $i--) {
                $ymatches[$this->yv[$i]][] = $i;
            }
        }

        $this->lcs = 0;
        $this->seq[0] = $yoff - 1;
        $this->in_seq = [];
        $ymids[0] = [];

        $numer = $xlim - $xoff + $nchunks - 1;
        $x = $xoff;
        for ($chunk = 0; $chunk < $nchunks; $chunk++) {
            if ($chunk > 0) {
                for ($i = 0; $i <= $this->lcs; $i++) {
                    $ymids[$i][$chunk - 1] = $this->seq[$i];
                }
            }

            $x1 = $xoff + (int)(($numer + ($xlim - $xoff) * $chunk) / $nchunks);
            for (; $x < $x1; $x++) {
                $matches = $ymatches[$flip ? $this->yv[$x] : $this->xv[$x]];
                if (! $matches) {
                    continue;
                }
                reset($matches);
                foreach ($matches as $y) {
                    if (empty($this->in_seq[$y])) {
                        $k = $this->lcsPos($y);
                        self::USE_ASSERTS && assert($k > 0);
                        $ymids[$k] = $ymids[$k - 1];
                        break;
                    }
                }

                foreach ($matches as $y) {
                    if ($y > $this->seq[$k - 1]) {
                        self::USE_ASSERTS && assert($y < $this->seq[$k]);
                        // Optimization: this is a common case:
                        // next match is just replacing previous match.
                        $this->in_seq[$this->seq[$k]] = false;
                        $this->seq[$k] = $y;
                        $this->in_seq[$y] = 1;
                    } elseif (empty($this->in_seq[$y])) {
                        $k = $this->lcsPos($y);
                        self::USE_ASSERTS && assert($k > 0);
                        $ymids[$k] = $ymids[$k - 1];
                    }
                }
            }
        }

        $seps[] = $flip ? [$yoff, $xoff] : [$xoff, $yoff];
        $ymid = $ymids[$this->lcs];
        for ($n = 0; $n < $nchunks - 1; $n++) {
            $x1 = $xoff + (int)(($numer + ($xlim - $xoff) * $n) / $nchunks);
            $y1 = $ymid[$n] + 1;
            $seps[] = $flip ? [$y1, $x1] : [$x1, $y1];
        }
        $seps[] = $flip ? [$ylim, $xlim] : [$xlim, $ylim];

        return [$this->lcs, $seps];
    }

    /**
     * @param $ypos
     * @return mixed
     */
    protected function lcsPos($ypos)
    {
        $end = $this->lcs;
        if ($end == 0 || $ypos > $this->seq[$end]) {
            $this->seq[++$this->lcs] = $ypos;
            $this->in_seq[$ypos] = 1;
            return $this->lcs;
        }

        $beg = 1;
        while ($beg < $end) {
            $mid = (int)(($beg + $end) / 2);
            if ($ypos > $this->seq[$mid]) {
                $beg = $mid + 1;
            } else {
                $end = $mid;
            }
        }

        self::USE_ASSERTS && assert($ypos != $this->seq[$end]);

        $this->in_seq[$this->seq[$end]] = false;
        $this->seq[$end] = $ypos;
        $this->in_seq[$ypos] = 1;

        return $end;
    }

    /* Find LCS of two sequences.
     *
     * The results are recorded in the vectors $this->{x,y}changed[], by
     * storing a 1 in the element for each line that is an insertion
     * or deletion (ie. is not in the LCS).
     *
     * The subsequence of file 0 is [XOFF, XLIM) and likewise for file 1.
     *
     * Note that XLIM, YLIM are exclusive bounds.
     * All line numbers are origin-0 and discarded lines are not counted.
     */
    /**
     * @param $xoff
     * @param $xlim
     * @param $yoff
     * @param $ylim
     */
    protected function compareSeq($xoff, $xlim, $yoff, $ylim)
    {
        // Slide down the bottom initial diagonal.
        while ($xoff < $xlim && $yoff < $ylim && $this->xv[$xoff] == $this->yv[$yoff]) {
            ++$xoff;
            ++$yoff;
        }

        // Slide up the top initial diagonal.
        while ($xlim > $xoff && $ylim > $yoff && $this->xv[$xlim - 1] == $this->yv[$ylim - 1]) {
            --$xlim;
            --$ylim;
        }

        if ($xoff == $xlim || $yoff == $ylim) {
            $lcs = 0;
        } else {
            // This is ad hoc but seems to work well.
            //$nchunks = sqrt(min($xlim - $xoff, $ylim - $yoff) / 2.5);
            //$nchunks = max(2,min(8,(int)$nchunks));
            $nchunks = min(7, $xlim - $xoff, $ylim - $yoff) + 1;
            list ($lcs, $seps) = $this->diag($xoff, $xlim, $yoff, $ylim, $nchunks);
        }

        if ($lcs == 0) {
            // X and Y sequences have no common subsequence:
            // mark all changed.
            while ($yoff < $ylim) {
                $this->ychanged[$this->yind[$yoff++]] = 1;
            }

            while ($xoff < $xlim) {
                $this->xchanged[$this->xind[$xoff++]] = 1;
            }
        } else {
            // Use the partitions to split this problem into subproblems.
            reset($seps);
            $pt1 = $seps[0];
            while ($pt2 = next($seps)) {
                $this->compareSeq($pt1[0], $pt2[0], $pt1[1], $pt2[1]);
                $pt1 = $pt2;
            }
        }
    }

    /**
     * Adjust inserts/deletes of identical lines to join changes
     * as much as possible.
     *
     * We do something when a run of changed lines include a
     * line at one end and has an excluded, identical line at the other.
     * We are free to choose which identical line is included.
     * `compareseq' usually chooses the one at the beginning,
     * but usually it is cleaner to consider the following identical line
     * to be the "change".
     *
     * This is extracted verbatim from analyze.c (GNU diffutils-2.7).
     */
    protected function shiftBoundaries($lines, &$changed, $other_changed)
    {
        $i = 0;
        $j = 0;

        self::USE_ASSERTS && assert(count($lines) == count($changed));
        $len = count($lines);
        $other_len = count($other_changed);

        while (1) {
            /*
             * Scan forwards to find beginning of another run of changes.
             * Also keep track of the corresponding point in the other file.
             *
             * Throughout this code, $i and $j are adjusted together so that
             * the first $i elements of $changed and the first $j elements
             * of $other_changed both contain the same number of zeros
             * (unchanged lines).
             * Furthermore, $j is always kept so that $j == $other_len or
             * $other_changed[$j] == false.
             */
            while ($j < $other_len && $other_changed[$j]) {
                $j++;
            }

            while ($i < $len && ! $changed[$i]) {
                self::USE_ASSERTS && assert($j < $other_len && ! $other_changed[$j]);
                $i++;
                $j++;
                while ($j < $other_len && $other_changed[$j]) {
                    $j++;
                }
            }

            if ($i == $len) {
                break;
            }

            $start = $i;

            // Find the end of this run of changes.
            while (++$i < $len && $changed[$i]) {
                continue;
            }

            do {
                /*
                 * Record the length of this run of changes, so that
                 * we can later determine whether the run has grown.
                 */
                $runlength = $i - $start;

                /*
                 * Move the changed region back, so long as the
                 * previous unchanged line matches the last changed one.
                 * This merges with previous changed regions.
                 */
                while ($start > 0 && $lines[$start - 1] == $lines[$i - 1]) {
                    $changed[--$start] = 1;
                    $changed[--$i] = false;
                    while ($start > 0 && $changed[$start - 1]) {
                        $start--;
                    }

                    self::USE_ASSERTS && assert($j > 0);
                    while ($other_changed[--$j]) {
                        continue;
                    }

                    self::USE_ASSERTS && assert($j >= 0 && ! $other_changed[$j]);
                }

                /*
                 * Set CORRESPONDING to the end of the changed run, at the last
                 * point where it corresponds to a changed run in the other file.
                 * CORRESPONDING == LEN means no such point has been found.
                 */
                $corresponding = $j < $other_len ? $i : $len;

                /*
                 * Move the changed region forward, so long as the
                 * first changed line matches the following unchanged one.
                 * This merges with following changed regions.
                 * Do this second, so that if there are no merges,
                 * the changed region is moved forward as far as possible.
                 */
                while ($i < $len && $lines[$start] == $lines[$i]) {
                    $changed[$start++] = false;
                    $changed[$i++] = 1;
                    while ($i < $len && $changed[$i]) {
                        $i++;
                    }

                    self::USE_ASSERTS && assert($j < $other_len && ! $other_changed[$j]);
                    $j++;
                    if ($j < $other_len && $other_changed[$j]) {
                        $corresponding = $i;
                        while ($j < $other_len && $other_changed[$j]) {
                            $j++;
                        }
                    }
                }
            } while ($runlength != $i - $start);

            /*
             * If possible, move the fully-merged run of changes
             * back to a corresponding run in the other file.
             */
            while ($corresponding < $i) {
                $changed[--$start] = 1;
                $changed[--$i] = 0;
                self::USE_ASSERTS && assert($j > 0);
                while ($other_changed[--$j]) {
                    continue;
                }
                self::USE_ASSERTS && assert($j >= 0 && ! $other_changed[$j]);
            }
        }
    }
}
