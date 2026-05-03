<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_Query_Facet_FieldAggregator extends Search_Query_Facet_Abstract implements Search_Query_Facet_Interface
{
    public const OPERATOR = 'or';

    private $data;

    public static function fromName($name)
    {
        return new self($name);
    }

    public function getData()
    {
        return $this->data;
    }

    public function getType()
    {
        return 'field_aggregator';
    }

    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    public function isBuiltFromIndex(): bool
    {
        return false;
    }

    public function getOperator()
    {
        return self::OPERATOR;
    }
}
