<?php

namespace Tiki\Lib\core\Tracker\Rule\Action;

use Tiki\Lib\core\Tracker\Rule\Type\Field;
use Tiki\Lib\core\Tracker\Rule\Type\Nothing;

class NotEditable extends Action
{
    public function __construct()
    {
        parent::__construct(tr('Not Editable'), Nothing::class, '.actionEditable(false);', [Field::class]);
    }
}
