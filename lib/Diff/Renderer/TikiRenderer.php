<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//this script may only be included - so its better to die if called directly.
namespace Tiki\Lib\Diff\Renderer;

use Tiki\Lib\Diff\Op\Copy;

if (str_contains($_SERVER["SCRIPT_NAME"], basename(__FILE__))) {
    header("location: index.php");
    exit;
}

/* @brief modif tiki for the renderer lib   */
class TikiRenderer extends Base
{
    protected function _lines($lines, $prefix = '', $suffix = '', $type = '')
    {
        //ADD $suffix
        foreach ($lines as $line) {
            echo "$prefix$line$suffix\n";
        }
    }
    public function render($diff, $singleEdit = false)
    {
        $x0 = $y0 = 0;
        $xi = $yi = 1;
        $block = false;
        $context = [];

        $nlead = $this->leading_context_lines;
        $ntrail = $this->trailing_context_lines;

        $this->_startDiff();

        if (! $singleEdit) {
            $diff = $diff->getDiff();
        }

        foreach ($diff as $edit) {
            if (is_a($edit, Copy::class)) {
                if (is_array($block)) {
                    if (count($edit->orig) <= $nlead + $ntrail) {
                        $block[] = $edit;
                    } else {
                        if ($ntrail) {
                            $context = array_slice($edit->orig, 0, $ntrail);
                            $block[] = new Copy($context);
                        }
                        $this->_block($x0, $ntrail + $xi - $x0, $y0, $ntrail + $yi - $y0, $block);
                        $block = false;
                    }
                }
                $context = $edit->orig;
            } else {
                if (! is_array($block)) {
                    //BUG if compare on all the length: $context = array_slice($context, count($context) - $nlead);
                    $context = array_slice($context, -$nlead, $nlead);
                    $x0 = $xi - count($context);
                    $y0 = $yi - count($context);
                    $block = [];
                    if ($context) {
                        $block[] = new Copy($context);
                    }
                }
                $block[] = $edit;
            }

            if ($edit->orig) {
                $xi += count($edit->orig);
            }
            if ($edit->final) {
                $yi += count($edit->final);
            }
        }

        if (is_array($block)) {
            $this->_block($x0, $xi - $x0, $y0, $yi - $y0, $block);
        }

        return $this->_endDiff();
    }
}
