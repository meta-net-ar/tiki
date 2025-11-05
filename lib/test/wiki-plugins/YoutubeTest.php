<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
require_once(__DIR__ . '/../../wiki-plugins/wikiplugin_youtube.php');

class WikiPlugin_YoutubeTest extends PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider provider
     * @param $data
     * @param $expectedOutput
     * @param array $params
     */
    public function testWikiPluginCode($data, $expectedOutput, $params = []): void
    {
        $result = TikiLib::lib('parser')->invokePlugin('youtube', $data, $params);
        $this->assertEquals($expectedOutput, $result);
    }

    public function testWikiPluginCodeWithMissingMovieParam(): void
    {
        $data = '';
        $params = []; // No 'movie' parameter provided

        $result = TikiLib::lib('parser')->invokePlugin('youtube', $data, $params);
        $this->assertInstanceOf(WikiParser_PluginOutput::class, $result);
        $this->assertStringContainsString('Plugin argument(s) missing or invalid:<ul><li>movie</li></ul>', $result->toWiki());
    }

    public static function provider(): array
    {
        return [
            ['', '~np~<iframe src="//www.youtube.com/embed/bPHuY7QL568?" frameborder="0" width="425" height="350" allowfullscreen=""></iframe>~/np~', ['movie' => 'http://www.youtube.com/watch?v=bPHuY7QL568']],
            ['', '~np~<iframe src="//www.youtube.com/embed/deby_Yb1-ac?" frameborder="0" width="425" height="350" allowfullscreen=""></iframe>~/np~', ['movie' => 'https://www.youtube.com/watch?v=deby_Yb1-ac']],
            ['', '~np~<iframe src="//www.youtube.com/embed/deby_Yb1-ac?" frameborder="0" width="425" height="350" allowfullscreen=""></iframe>~/np~', ['movie' => 'https://youtu.be/deby_Yb1-ac']],
            ['', '~np~<iframe src="//www.youtube-nocookie.com/embed/deby_Yb1-ac?" frameborder="0" width="425" height="350" allowfullscreen=""></iframe>~/np~', ['movie' => 'https://youtu.be/deby_Yb1-ac', 'privacyEnhanced' => 'y']],
        ];
    }
}
