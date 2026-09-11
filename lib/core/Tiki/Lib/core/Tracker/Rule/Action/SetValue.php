<?php

namespace Tiki\Lib\core\Tracker\Rule\Action;

use Tiki\Lib\core\Tracker\Rule\Type\Field;
use Tiki\Lib\core\Tracker\Rule\Type\Text;

class SetValue extends Action
{
    public function __construct()
    {
        parent::__construct(tr('Set value'), Text::class, '.setValue("%argument%")', [Field::class]);
    }
}
