<?php

namespace Tiki\Lib\core\Tracker\Rule\Type;

use Tiki\Lib\core\Tracker\Rule\Operator;
use Tiki\Lib\core\Tracker\Rule\Action;

class Text extends Type
{
    public function __construct()
    {
        parent::__construct('text');
    }
}
