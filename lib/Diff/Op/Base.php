<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Diff\Op;

use BadMethodCallException;

/**
 * @package Text_Diff
 * @author  Geoffrey T. Dairiki <dairiki@dairiki.org>
 * @access  private
 */
class Base
{
    public $orig;
    public $final;

    public function reverse()
    {
        throw new BadMethodCallException(tr("Abstract method call. %0 not defined in %1", __FUNCTION__ . '()', get_class($this)));
    }

    public function norig()
    {
        return $this->orig ? count($this->orig) : 0;
    }

    public function nfinal()
    {
        return $this->final ? count($this->final) : 0;
    }
}
