<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Tests\Version;

use PHPUnit\Framework\TestCase;
use Tiki_Version_Upgrade;
use Tiki_Version_Utils;
use TikiLib;
use TWVersion;

class UtilsTest extends TestCase
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

    public function testCheckUpdatesForVersionReturnsUpgradeObjects(): void
    {
        $upgrades = Tiki_Version_Utils::checkUpdatesForVersion('24.0');

        $this->assertNotEmpty($upgrades);
        $this->assertContainsOnlyInstancesOf(Tiki_Version_Upgrade::class, $upgrades);
    }

    /**
     * Callers rendering the result in a template get text, since the upgrade
     * objects themselves cannot be printed.
     */
    public function testGetUpgradeMessagesReturnsTextOnly(): void
    {
        $upgrades = Tiki_Version_Utils::checkUpdatesForVersion('24.0');
        $messages = Tiki_Version_Utils::getUpgradeMessages('24.0');

        $this->assertCount(count($upgrades), $messages);
        foreach ($messages as $index => $message) {
            $this->assertIsString($message);
            $this->assertSame($upgrades[$index]->getMessage(), $message);
        }
    }
}
