<?php

namespace Tiki\Lib\core\Tracker\Rule\Operator;

use Tiki\Lib\core\Tracker\Rule\Type\DateTime;
use Tiki\Lib\core\Tracker\Rule\Type\Nothing;
use Tiki\Lib\core\Tracker\Rule\Type\Text;

class TextIsNotEmpty extends Operator
{
    public function __construct()
    {
        parent::__construct(tr('is not empty'), Nothing::class, '.val()!==""', [Text::class, DateTime::class]);
    }
}
