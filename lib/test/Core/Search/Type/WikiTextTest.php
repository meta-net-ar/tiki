<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_Type_WikiTextTest extends PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider mapping
     * @param $letter
     * @param $string
     */
    public function testWordsNotBroken($html, $expectedPlaintext)
    {
        $plaintext = Search_Type_WikiText::stripTagsMaintainWords($html);

        $this->assertEquals($expectedPlaintext, $plaintext);
    }

    public static function mapping()
    {
        return [
            'Check ^plain text is not somehow mangled' => ['firstword secondword', 'firstword secondword'],
            'Check normal inline tags' => ['firstword <em>secondword</em>', 'firstword secondword'],
            'Check that inline tags don\'t break words' => ['firstword second<em>word</em>', 'firstword secondword'],
            'Check that normal tags don\'t merge words' => ['<table><tr><td>firstword</td><td>secondword</td></tr></table>', 'firstword secondword'],
            'Check that br tags don\'t merge words' => ['firstword<br/>secondword', 'firstword secondword']
        ];
    }
}
