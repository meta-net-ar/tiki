<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

class WikiPlugin_YoutubeTest extends PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        global $prefs;

        $prefs['http_header_referrer_policy_value'] = 'strict-origin-when-cross-origin';
    }

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
        if (strpos($result, 'alert') !== false || trim($result) === '~np~~/np~') {
            $this->assertIsString($result);
        } else {
            $this->assertStringContainsString('~np~', $result);
            if ($expectedSubstring !== '') {
                $this->assertStringContainsString($expectedSubstring, $result);
            }
            $this->assertStringContainsString('referrerpolicy="strict-origin-when-cross-origin"', $result);
        }
    }

    public function testWikiPluginCodeWithMissingMovieParam(): void
    {
        $data   = '';
        $params = ['movie' => ''];

        $result = TikiLib::lib('parser')->invokePlugin('youtube', $data, $params);
        if ($result instanceof WikiParser_PluginOutput) {
            $result = $result->toWiki();
        }

        $this->assertStringContainsString('alert', $result);
    }

    public static function provider(): array
    {
        return [
            // Standard watch?v= formats
            ['', 'youtube-nocookie.com/embed/bPHuY7QL568', ['movie' => 'http://www.youtube.com/watch?v=bPHuY7QL568']],
            ['', 'youtube-nocookie.com/embed/NdPpffwYGoM', ['movie' => 'https://www.youtube.com/watch?v=NdPpffwYGoM']],
            ['', 'youtube-nocookie.com/embed/WbTkF-N-lO0', ['movie' => 'https://www.youtube.com/watch?v=WbTkF-N-lO0']],

            // Privacy enhanced mode via parameter
            ['', 'youtube-nocookie.com/embed/4AcGoG9PChs', [
                'movie'           => 'https://www.youtube.com/watch?v=4AcGoG9PChs',
                'privacyEnhanced' => 'y',
            ]],
            ['', 'youtube-nocookie.com/embed/4AcGoG9PChs', [
                'movie'           => 'https://www.youtube.com/watch?v=4AcGoG9PChs',
                'privacyenhanced' => 'y',
            ]],
            ['', 'youtube.com/embed/4AcGoG9PChs', [
                'movie'           => 'https://www.youtube.com/watch?v=4AcGoG9PChs',
                'privacyEnhanced' => 'n',
            ]],
            ['', 'start=12&amp;end=34', [
                'movie' => 'https://www.youtube.com/watch?v=4AcGoG9PChs',
                'start' => 12,
                'end'   => 34,
            ]],

            // All supported YouTube URL formats
            ['', 'youtube-nocookie.com/embed/j4dMnAPZu70', ['movie' => 'https://youtu.be/j4dMnAPZu70']],
            ['', 'youtube-nocookie.com/embed/j4dMnAPZu70', ['movie' => 'https://youtube.com/v/j4dMnAPZu70']],
            ['', 'youtube-nocookie.com/embed/j4dMnAPZu70', ['movie' => 'https://youtube.com/e/j4dMnAPZu70']],
            ['', 'youtube-nocookie.com/embed/j4dMnAPZu70', ['movie' => 'https://youtube.com/watch?v=j4dMnAPZu70']],
            ['', 'youtube-nocookie.com/embed/j4dMnAPZu70', ['movie' => 'https://youtube.com/shorts/j4dMnAPZu70']],
            ['', 'youtube-nocookie.com/embed/j4dMnAPZu70', ['movie' => 'https://youtube.com/embed/j4dMnAPZu70']],
            ['', 'youtube-nocookie.com/embed/j4dMnAPZu70', ['movie' => 'https://youtube.com/live/j4dMnAPZu70']],

            // nocookie URL as input with privacyEnhanced=y → nocookie embed
            ['', 'youtube-nocookie.com/embed/j4dMnAPZu70', [
                'movie'           => 'https://youtube-nocookie.com/embed/j4dMnAPZu70',
                'privacyEnhanced' => 'y',
            ]],

            // nocookie URL as input without privacyEnhanced → nocookie embed by default
            ['', 'youtube-nocookie.com/embed/j4dMnAPZu70', [
                'movie' => 'https://youtube-nocookie.com/embed/j4dMnAPZu70',
            ]],
        ];
    }

    /**
     * @dataProvider youtubeIdProvider
     */
    public function testGetYoutubeId(string $url, string $expectedId): void
    {
        $result = getYoutubeId($url);
        $this->assertIsArray($result);
        $this->assertSame($expectedId, $result['id']);
    }

    public static function youtubeIdProvider(): array
    {
        return [
            'watch url'          => ['https://www.youtube.com/watch?v=j4dMnAPZu70',       'j4dMnAPZu70'],
            'shorts url'         => ['https://www.youtube.com/shorts/j4dMnAPZu70',         'j4dMnAPZu70'],
            'live url'           => ['https://www.youtube.com/live/j4dMnAPZu70',           'j4dMnAPZu70'],
            'embed url'          => ['https://www.youtube.com/embed/j4dMnAPZu70',          'j4dMnAPZu70'],
            'short url'          => ['https://youtu.be/j4dMnAPZu70',                       'j4dMnAPZu70'],
            'short url with www' => ['https://www.youtu.be/j4dMnAPZu70',                   'j4dMnAPZu70'],
            'nocookie embed'     => ['https://youtube-nocookie.com/embed/j4dMnAPZu70',     'j4dMnAPZu70'],
            'v url'              => ['https://youtube.com/v/j4dMnAPZu70',                  'j4dMnAPZu70'],
            'e url'              => ['https://youtube.com/e/j4dMnAPZu70',                  'j4dMnAPZu70'],
            'raw id'             => ['j4dMnAPZu70',                                        'j4dMnAPZu70'],
        ];
    }

    /**
     * @dataProvider invalidUrlProvider
     */
    public function testGetYoutubeIdWithInvalidUrl(string $url): void
    {
        $result = getYoutubeId($url);
        $this->assertFalse($result);
    }

    public static function invalidUrlProvider(): array
    {
        return [
            'vimeo url'       => ['https://vimeo.com/123456789'],
            'random url'      => ['https://example.com/video'],
            'empty string'    => [''],
            'watch without v' => ['https://www.youtube.com/watch'],
            'invalid chars'   => ['not a valid id !!'],
        ];
    }
}
