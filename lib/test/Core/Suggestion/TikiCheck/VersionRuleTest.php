<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Tests\Suggestion\TikiCheck;

use PHPUnit\Framework\TestCase;
use Tiki\Suggestion\TikiCheck\Version;
use TikiLib;
use TWVersion;

class VersionRuleTest extends TestCase
{
    private string $cycleUrl;

    protected function setUp(): void
    {
        global $TWV;
        $TWV = new TWVersion();

        $this->cycleUrl = 'https://tiki.org/' . TikiLib::lib('tiki')->get_preference('tiki_release_cycle') . '.cycle';
        TikiLib::lib('cache')->cacheItem($this->cycleUrl, "99.0\n99.1\n", 'http');
    }

    protected function tearDown(): void
    {
        TikiLib::lib('cache')->invalidate($this->cycleUrl, 'http');
    }

    /**
     * Suggestions are rendered as plain feedback messages, so the rule must not
     * leak the Tiki_Version_Upgrade objects the version check returns.
     */
    public function testParserReturnsMessageStringsOnly(): void
    {
        $messages = (new Version())->parser();

        $this->assertNotEmpty($messages);
        foreach ($messages as $message) {
            $this->assertIsString($message);
            $this->assertNotSame('', $message);
        }
    }
}
