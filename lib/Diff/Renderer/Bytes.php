<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Diff\Renderer;

use Tiki\Lib\Diff\DiffUtils;

/**
 * Give back the kb change
 *
 */
class Bytes extends Base
{
    private $addBytes = 0;
    private $delBytes = 0;
    private $first;
    public function __construct($first = -1)
    {
        $this->first = $first;
    }

    protected function _startDiff()
    {
    }

    protected function _endDiff()
    {
        return 'add=' . $this->addBytes . '&amp;del=' . $this->delBytes;
    }

    protected function _blockHeader($xbeg, $xlen, $ybeg, $ylen)
    {
    }

    protected function _added($lines)
    {
        $this->addBytes += $this->_count($lines);
    }

    protected function _deleted($lines)
    {
        $this->delBytes += $this->_count($lines);
    }

    protected function _changed($orig, $final)
    {
        if ($this->first >= 0) { // stop recursion
            $this->addBytes += count($final);
            $this->delBytes += count($orig);
            return;
        }
        $change = DiffUtils::diffChar($orig, $final, 0, 'bytes');
        preg_match("/add=([0-9]*)&amp;del=([0-9]*)/", $change, $matches);
        $this->addBytes += $matches[1];
        $this->delBytes += $matches[2];
    }
    protected function _count($lines)
    {
        $bytes = 0;
        foreach ($lines as $line) {
            $bytes += strlen($line);
        }
        return $bytes;
    }
}
