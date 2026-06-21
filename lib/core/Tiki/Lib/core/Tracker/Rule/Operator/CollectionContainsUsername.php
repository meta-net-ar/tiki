<?php

namespace Tiki\Lib\core\Tracker\Rule\Operator;

use Tiki\Lib\core\Tracker\Rule\Type\Collection;
use Tiki\Lib\core\Tracker\Rule\Type\Nothing;

class CollectionContainsUsername extends Operator
{
    public function __construct()
    {
        parent::__construct(
            tr('contains username'),
            Nothing::class,
            '.collectionContains(jqueryTiki.username)',
            [Collection::class]
        );
    }
}
