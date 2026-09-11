<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Diff\Op;

/**
 * @package Text_Diff
 * @author  Geoffrey T. Dairiki <dairiki@dairiki.org>
 * @access  private
 */
class Add extends Base
{
    public function __construct($lines)
    {
        $this->final = $lines;
        $this->orig = false;
    }

    public function &reverse()
    {
        return new Delete($this->final);
    }
}
