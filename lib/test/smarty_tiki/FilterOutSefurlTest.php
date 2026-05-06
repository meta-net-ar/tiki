<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\SmartyTiki;

use TestableTikiLib;
use TikiLib;
use TikiTestCase;

require_once(__DIR__ . '/../../../tiki-sefurl.php');

class FilterOutSefurlTest extends TikiTestCase
{
    /**
     * @var TestableTikiLib|null
     */
    private $overrideLibs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->overrideLibs = new TestableTikiLib();

        $trklib = $this->createMock(get_class(TikiLib::lib('trk')));
        $trklib->method('get_title_sefurl')
            ->with('123')
            ->willReturn('Example Tracker Item');

        $tikilib = $this->createMock(get_class(TikiLib::lib('tiki')));

        $this->overrideLibs->overrideLibs([
            'trk' => $trklib,
            'tiki' => $tikilib,
        ]);

        $GLOBALS['prefs'] = array_merge($GLOBALS['prefs'] ?? [], [
            'feature_sefurl' => 'y',
            'feature_sefurl_title_trackeritem' => 'y',
            'feature_sefurl_tracker_prefixalias' => 'n',
            'tracker_prefixalias_on_links' => 'n',
            'feature_sefurl_paths' => [],
        ]);
        $GLOBALS['base_url'] = 'https://example.org';
        $GLOBALS['in_installer'] = null;
        $GLOBALS['sefurl_regex_out'] = [];
    }

    protected function tearDown(): void
    {
        $this->overrideLibs = null;
        unset($GLOBALS['sefurl_regex_out'], $GLOBALS['in_installer'], $GLOBALS['base_url']);
        parent::tearDown();
    }

    /**
     * @dataProvider trackerItemUrlsProvider
     */
    public function testTrackerItemUrlsAppendTitles(string $input): void
    {
        $this->assertSame($input . '-Example Tracker Item', filter_out_sefurl($input, 'trackeritem'));
    }

    public static function trackerItemUrlsProvider(): array
    {
        return [
            'query string url' => ['tiki-view_tracker_item.php?itemId=123'],
            'short sefurl' => ['item123'],
        ];
    }
}
