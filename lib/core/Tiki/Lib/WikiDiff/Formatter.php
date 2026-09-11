<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\WikiDiff;

/**
 * A class to format a WikiDiff as HTML.
 *
 * Usage:
 *
 * $diff = Base($lines1, $lines2); // compute diff.
 *
 * $fmt = new Formatter;
 * echo $fmt->format($diff, $lines1); // Output HTMLified standard diff.
 *
 * or to output reverse diff (diff's that would take $lines2 to $lines1):
 *
 * $fmt = new Formatter('reversed');
 * echo $fmt->format($diff, $lines1);
 */
class Formatter
{
    public $context_lines;
    public $do_reverse_diff;
    public $context_prefix;
    public $deletes_prefix;
    public $adds_prefix;

    /**
     * @param bool $reverse
     */
    public function __construct($reverse = false)
    {
        $this->do_reverse_diff = $reverse;
        $this->context_lines = 0;
        $this->context_prefix = '&nbsp;&nbsp;';
        $this->deletes_prefix = '&lt;&nbsp;';
        $this->adds_prefix = '&gt;&nbsp;';
    }

    /**
     * @param $diff
     * @param $from_lines
     * @return string
     */
    public function format($diff, $from_lines)
    {
        $html = '<table style="background-color: black" ' .
            'cellspacing="2" cellpadding="2" border="0">';
        $html .= $this->doFormat($diff->edits, $from_lines);
        $html .= "</table>\n";

        return $html;
    }

    /**
     * @param $edits
     * @param $from_lines
     * @return string
     */
    protected function doFormat($edits, $from_lines)
    {
        $html = '';
        $x = 0;
        $y = 0;
        $xlim = count($from_lines);

        reset($edits);
        while ($edit = current($edits)) {
            if (! is_array($edit) && $edit >= 0) {
                // Edit op is a copy.
                $ncopy = $edit;
            } else {
                $ncopy = 0;
                if (empty($hunk)) {
                    // Start of an output hunk.
                    $xoff = max(0, $x - $this->context_lines);
                    $yoff = $xoff + $y - $x;
                    if ($xoff < $x) {
                        // Get leading context.
                        $context = [];
                        for ($i = $xoff; $i < $x; $i++) {
                            $context[] = $from_lines[$i];
                        }

                        $hunk['c'] = $context;
                    }
                }
                if (is_array($edit)) {
                    // Edit op is an add.
                    $y += count($edit);
                    $hunk[$this->do_reverse_diff ? 'd' : 'a'] = $edit;
                } else {
                    // Edit op is a delete
                    $deletes = [];
                    while ($edit++ < 0) {
                        $deletes[] = $from_lines[$x++];
                    }

                    $hunk[$this->do_reverse_diff ? 'a' : 'd'] = $deletes;
                }
            }

            $next = next($edits);
            if (! empty($hunk)) {
                if (! $next || $ncopy > 2 * $this->context_lines) {
                    // End of an output hunk.
                    $hunks[] = $hunk;
                    unset($hunk);

                    $xend = min($x + $this->context_lines, $xlim);
                    if ($x < $xend) {
                        // Get trailing context.
                        $context = [];
                        for ($i = $x; $i < $xend; $i++) {
                            $context[] = $from_lines[$i];
                        }

                        $hunks[] = ['c' => $context];
                    }

                    $xlen = $xend - $xoff;
                    $ylen = $xend + $y - $x - $yoff;
                    $xbeg = $xlen ? $xoff + 1 : $xoff;
                    $ybeg = $ylen ? $yoff + 1 : $yoff;

                    if ($this->do_reverse_diff) {
                        list ($xbeg, $xlen, $ybeg, $ylen) = [$ybeg, $ylen, $xbeg, $xlen];
                    }

                    $html .= $this->emitDiff($xbeg, $xlen, $ybeg, $ylen, $hunks);
                    unset($hunks);
                } elseif ($ncopy) {
                    $hunks[] = $hunk;

                    // Copy context.
                    $context = [];
                    for ($i = $x; $i < $x + $ncopy; $i++) {
                        $context[] = $from_lines[$i];
                    }

                    $hunk = ['c' => $context];
                }
            }

            $x += $ncopy;
            $y += $ncopy;
        }

        return $html;
    }

    /**
     * @param $lines
     * @param $prefix
     * @param $color
     * @return string
     */
    protected function emitLines($lines, $prefix, $color)
    {
        $html = '';
        reset($lines);
        foreach ($lines as $line) {
            $html .= "<tr style=\"background-color: $color\"><td><tt>$prefix</tt>";
            $html .= "<tt>$line</tt></td></tr>\n";
        }

        return $html;
    }

    /**
     * @param $xbeg
     * @param $xlen
     * @param $ybeg
     * @param $ylen
     * @param $hunks
     * @return string
     */
    protected function emitDiff($xbeg, $xlen, $ybeg, $ylen, $hunks)
    {
        $html = '<tr><td><table style="background-color: white"'
            . ' cellspacing="0" border="0" cellpadding="4">'
            . '<tr bgcolor="#cccccc"><td><tt>'
            . $this->diffHeader($xbeg, $xlen, $ybeg, $ylen)
            . "</tt></td></tr>\n<tr><td>\n"
            . '<table cellspacing="0" border="0" cellpadding="2">';

        $prefix = [
            'c' => $this->context_prefix
            ,
            'a' => $this->adds_prefix
            ,
            'd' => $this->deletes_prefix,
        ];
        $color = [
            'c' => '#ffffff'
            ,
            'a' => '#ccffcc'
            ,
            'd' => '#ffcccc',
        ];

        for (reset($hunks), $currenthunks = current($hunks); $hunk = $currenthunks; next($hunks)) {
            if (! empty($hunk['c'])) {
                $html .= $this->emitLines($hunk['c'], $this->context_prefix, '#ffffff');
            }
            if (! empty($hunk['d'])) {
                $html .= $this->emitLines($hunk['d'], $this->deletes_prefix, '#ffcccc');
            }
            if (! empty($hunk['a'])) {
                $html .= $this->emitLines($hunk['a'], $this->adds_prefix, '#ccffcc');
            }
        }

        $html .= "</table></td></tr></table></td></tr>\n";
        return $html;
    }

    /**
     * @param $xbeg
     * @param $xlen
     * @param $ybeg
     * @param $ylen
     * @return string
     */
    protected function diffHeader($xbeg, $xlen, $ybeg, $ylen)
    {
        $what = $xlen ? ($ylen ? 'c' : 'd') : 'a';
        $xlen = $xlen > 1 ? ',' . ($xbeg + $xlen - 1) : '';
        $ylen = $ylen > 1 ? ',' . ($ybeg + $ylen - 1) : '';

        return "$xbeg$xlen$what$ybeg$ylen";
    }
}
