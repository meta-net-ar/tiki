<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Diff\Renderer;

use Tiki\Lib\Diff\DiffUtils;

/**
 * "Unified" diff renderer.
 *
 * This class renders the diff in classic "unified diff" format.
 *
 * $Horde: framework/Text_Diff/Diff/Renderer/unified.php,v 1.2 2004/01/09 21:46:30 chuck Exp $
 *
 * @package Text_Diff
 */
class Unified extends TikiRenderer
{
    public $table;

    public function __construct($context_lines = 4)
    {
        $this->leading_context_lines = $context_lines;
        $this->trailing_context_lines = $context_lines;
        $this->table = [];
    }

    protected function _startDiff()
    {
    }

    protected function _endDiff()
    {
        return $this->table;
    }

    protected function _blockHeader($xbeg, $xlen, $ybeg, $ylen)
    {
        if ($xlen != 1) {
            $l = $xbeg + $xlen - 1;
            $xbeg .= '-' . $l;
        }
        if ($ylen != 1) {
            $l = $ybeg + $ylen - 1;
            $ybeg .= '-' . $l;
        }
        $this->table[] = ['type' => "diffheader", 'old' => "$xbeg", 'new' => "$ybeg"];
    }

    protected function _context($lines)
    {
        $this->table[] = ['type' => "diffbody", 'data' => $lines];
    }
    protected function _added($lines)
    {
        $this->table[] = ['type' => "diffadded", 'data' => $lines];
    }

    protected function _deleted($lines)
    {
        $this->table[] = ['type' => "diffdeleted", 'data' => $lines];
    }

    protected function _changed($orig, $final)
    {
        $lines = DiffUtils::diffChar($orig, $final, 0);
        $this->_deleted([$lines[0]]);
        $this->_added([$lines[1]]);
    }
}
