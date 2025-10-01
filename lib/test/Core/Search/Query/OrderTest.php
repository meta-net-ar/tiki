<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_Query_OrderTest extends PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider sortMatches
     * @param $mode
     * @param $field
     * @param $order
     * @param $type
     */
    public function testParse($mode, $field, $order, $type)
    {
        $obtained = Search\Query\Order::parse($mode);
        $this->assertEquals([new Search\Query\Order($field, $type, $order)], $obtained->getParts());
    }

    public function testMultiParse()
    {
        $obtained = Search\Query\Order::parse("title_asc, object_id_ndesc");
        $this->assertEquals([
            new Search\Query\Order('title', 'text', 'asc'),
            new Search\Query\Order('object_id', 'numeric', 'desc'),
        ], $obtained->getParts());
    }

    public static function sortMatches()
    {
        return [
            ['', 'score', 'desc', 'numeric'],
            ['title', 'title', 'asc', 'text'],
            ['title_asc', 'title', 'asc', 'text'],
            ['title_desc', 'title', 'desc', 'text'],
            ['title_nasc', 'title', 'asc', 'numeric'],
            ['title_ndesc', 'title', 'desc', 'numeric'],
            ['modification_date_ndesc', 'modification_date', 'desc', 'numeric'],
        ];
    }
}
