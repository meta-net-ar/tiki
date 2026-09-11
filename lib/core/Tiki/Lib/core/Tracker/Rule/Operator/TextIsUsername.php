<?php

namespace Tiki\Lib\core\Tracker\Rule\Operator;

use Tiki\Lib\core\Tracker\Rule\Type\Nothing;
use Tiki\Lib\core\Tracker\Rule\Type\Text;

class TextIsUsername extends Operator
{
    public function __construct()
    {
        parent::__construct(tr('is username'), Nothing::class, '.val()===jqueryTiki.username', [Text::class]);
    }
}
