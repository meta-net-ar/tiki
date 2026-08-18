<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Math\Formula;

class GraphFormulaException extends \Exception
{
    /** @var string */
    private $formula;

    public function __construct(string $formula, string $detail)
    {
        parent::__construct($detail);
        $this->formula = $formula;
    }

    public function getUserMessage(): string
    {
        return GraphFormulaHelper::invalidMessage($this->formula, $this->getMessage());
    }
}
