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
     */
    public function testWikiPluginCode($data, $expectedSubstring, $params = []): void
    {
        $result = TikiLib::lib('parser')->invokePlugin('youtube', $data, $params);

        if ($result instanceof WikiParser_PluginOutput) {
            $result = $result->toWiki();
        }

        $this->assertIsString($result);
        $this->assertStringContainsString('~np~', $result);
    }

    public function testWikiPluginCodeWithMissingMovieParam(): void
    {
        $data = '';
        $params = [];

        $result = TikiLib::lib('parser')->invokePlugin('youtube', $data, $params);

        if ($result instanceof WikiParser_PluginOutput) {
            $result = $result->toWiki();
        }

        $this->assertStringContainsString('movie', $result);
    }

    public static function provider(): array
    {
        return [
            ['', 'youtube.com/embed/bPHuY7QL568', [
                'movie' => 'http://www.youtube.com/watch?v=bPHuY7QL568'
            ]],

            ['', 'youtube.com/embed/NdPpffwYGoM', [
                'movie' => 'https://www.youtube.com/watch?v=NdPpffwYGoM'
            ]],

            ['', 'youtube.com/embed/WbTkF-N-lO0', [
                'movie' => 'https://www.youtube.com/watch?v=WbTkF-N-lO0'
            ]],

            ['', 'youtube-nocookie.com/embed/4AcGoG9PChs', [
                'movie' => 'https://www.youtube.com/watch?v=4AcGoG9PChs',
                'privacyEnhanced' => 'y'
            ]],
        ];
    }
}
