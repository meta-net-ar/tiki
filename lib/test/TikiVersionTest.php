<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

class TikiVersionTest extends PHPUnit\Framework\TestCase
{
    /**
     * Helper method to create and configure the mock TWVersion object.
     * @param array $ltsEolDates The specific EOL dates for this test scenario.
     */
    private function setupMockTWV(array $ltsEolDates = []): void
    {
        global $TWV;
        $mockTWV = $this->createMock(TWVersion::class);
        $mockTWV->method('getLtsEolDates')->willReturn($ltsEolDates);

        $mockTWV->method('getExtendedSupportProviders')->willReturn([
            ['name' => tra('Official Service Providers'), 'url' => 'https://tiki.org/Extended-Security-Maintenance'],
        ]);

        $TWV = $mockTWV;
    }
    /**
    * Set up a default mock for the global $TWV variable before each test.
    */
    protected function setUp(): void
    {
        $this->setupMockTWV();
    }

    public static function versions()
    {
        return [
            ['9.0', new Tiki_Version_Version(9, 0)],
            ['9.1', new Tiki_Version_Version(9, 1)],
            ['9.1beta2', new Tiki_Version_Version(9, 1, null, 'beta', 2)],
            ['1.9.12.1beta2', new Tiki_Version_Version(1, 9, '12.1', 'beta', 2)],
            ['9.0pre', new Tiki_Version_Version(9, 0, null, 'pre')],
        ];
    }

    /**
     * @dataProvider versions
     * @param $string
     * @param $version
     */
    public function testParseVersions($string, $version)
    {
        $this->assertEquals($version, Tiki_Version_Version::get($string));
    }

    /**
     * @dataProvider versions
     * @param $string
     * @param $version
     */
    public function testWriteVersions($string, $version)
    {
        $this->assertEquals($string, (string) $version);
    }

    public function testVerifyLatestVersion()
    {
        $checker = new Tiki_Version_Checker();
        $checker->setCycle('regular');
        $checker->setVersion('9.0');

        $out = '';
        $response = $checker->check(
            function ($url) use (&$out) {
                $out = $url;
                return <<<O
                        9.0
                        8.4
                        6.7
                        O;
            }
        );

        $this->assertEquals('https://tiki.org/regular.cycle', $out);
        $this->assertEquals([], $response);
    }

    public function testVerifyPastSupportedVersion()
    {
        $this->setupMockTWV(['8' => 'some future date']);

        $checker = new Tiki_Version_Checker();
        $checker->setCycle('regular');
        $checker->setVersion('8.4');

        $out = '';
        $response = $checker->check(
            function ($url) use (&$out) {
                $out = $url;
                return <<<O
                        9.0
                        8.4
                        6.7
                        O;
            }
        );

        $this->assertEquals('https://tiki.org/regular.cycle', $out);
        $this->assertEquals([new Tiki_Version_Upgrade('8.4', '9.0', 'note')], $response);
    }

    public function testVerifyMinorUpdate()
    {
        $checker = new Tiki_Version_Checker();
        $checker->setCycle('regular');
        $checker->setVersion('8.2');

        $out = '';
        $response = $checker->check(
            function ($url) use (&$out) {
                $out = $url;
                return <<<O
                        9.0
                        8.4
                        6.7
                        O;
            }
        );

        $this->assertEquals('https://tiki.org/regular.cycle', $out);
        $this->assertCount(2, $response);

        $expected = [
            new Tiki_Version_Upgrade('8.2', '8.4', 'error'),
            new Tiki_Version_Upgrade('8.4', '9.0', 'note')
        ];

        // Assert that the response from the checker matches our expected results.
        foreach ($expected as $expectedUpgrade) {
            $this->assertContainsEquals($expectedUpgrade, $response);
        }
    }

    public function testVerifyUpgradePrerelease()
    {
        $checker = new Tiki_Version_Checker();
        $checker->setCycle('regular');
        $checker->setVersion('8.4beta3');

        $out = '';
        $response = $checker->check(
            function ($url) use (&$out) {
                $out = $url;
                return <<<O
                        9.0
                        8.4
                        6.7
                        O;
            }
        );

        $this->assertEquals('https://tiki.org/regular.cycle', $out);
        $this->assertCount(2, $response);

        $expected = [
            new Tiki_Version_Upgrade('8.4beta3', '8.4', 'error'),
            new Tiki_Version_Upgrade('8.4', '9.0', 'note')
        ];
        foreach ($expected as $expectedUpgrade) {
            $this->assertContainsEquals($expectedUpgrade, $response);
        }
    }

    public function testUpgradeFromUnsupportedVersion()
    {
        $checker = new Tiki_Version_Checker();
        $checker->setCycle('regular');
        $checker->setVersion('4.3');

        $out = '';
        $response = $checker->check(
            function ($url) use (&$out) {
                $out = $url;
                return <<<O
                        8.4
                        9.0
                        6.7
                        O;
            }
        );

        $this->assertEquals('https://tiki.org/regular.cycle', $out);
        $this->assertEquals([new Tiki_Version_Upgrade('4.3', '9.0', 'error')], $response);
    }

    public function testCurrentVersionMoreRecent()
    {
        $checker = new Tiki_Version_Checker();
        $checker->setCycle('regular');
        $checker->setVersion('10.0');

        $out = '';
        $response = $checker->check(
            function ($url) use (&$out) {
                $out = $url;
                return <<<O
                        8.4
                        9.0
                        6.7
                        O;
            }
        );

        $this->assertEquals('https://tiki.org/regular.cycle', $out);
        $this->assertEquals([], $response);
    }

    public function testCurrentGitVersionDoesNotTriggerReleasedUpgradeNotice()
    {
        $checker = new Tiki_Version_Checker();
        $checker->setCycle('regular');
        $checker->setVersion('30.1vcs');

        $response = $checker->check(
            function () {
                return <<<O
                        30.1
                        30.0
                        29.0
                        O;
            }
        );

        $this->assertSame([], $response);
    }

    public function testGitVersionTriggersNoteForDifferentBaseVersion()
    {
        $checker = new Tiki_Version_Checker();
        $checker->setCycle('regular');
        $checker->setVersion('30.0vcs');

        $response = $checker->check(
            function () {
                return '30.1';
            }
        );

        $this->assertCount(1, $response);
        $this->assertSame('note', $response[0]->getType());
    }

    public function testTrunkBranchShowsDevelopmentVersionWarningForVcsVersion()
    {
        global $TWV;
        $TWV = new TWVersion();
        $TWV->branch = 'trunk';

        $checker = new Tiki_Version_Checker();
        $checker->setCycle('regular');
        $checker->setVersion('30.1vcs');

        $response = $checker->check(
            function () {
                return <<<O
                        30.1
                        30.0
                        29.0
                        O;
            }
        );

        $this->assertCount(1, $response);
        $this->assertSame('note', $response[0]->getType());
        $this->assertStringContainsString('development version', $response[0]->getMessage());
    }

    /**
     * @dataProvider upgradeMessages
     */
    public function testObtainMessages(string $expectedMessage, Tiki_Version_Upgrade $upgrade, array $ltsMockData = [])
    {
        $this->setupMockTWV($ltsMockData);

        $this->assertEquals($expectedMessage, $upgrade->getMessage());
    }

    /**
     * Casting to string must match getMessage(), so objects reaching a template still render.
     *
     * @dataProvider upgradeMessages
     */
    public function testUpgradeCastsToItsMessage(string $expectedMessage, Tiki_Version_Upgrade $upgrade, array $ltsMockData = []): void
    {
        $this->setupMockTWV($ltsMockData);

        $this->assertEquals($expectedMessage, (string) $upgrade);
    }

    public static function upgradeMessages()
    {
        $providerLink = '<a href="https://tiki.org/Extended-Security-Maintenance" target="_blank" class="alert-link">' . tra("Official Service Providers") . '</a>';
        $providerListItems = '<li>' . $providerLink . '</li>';
        $fullProviderMessage = tr('For organizations requiring Extended Security Maintenance, professional services are available from the following providers: <ul>%0</ul>', $providerListItems);

        return [
            'Required MINOR upgrade' => [
                '<strong>' . tr('Version %0 is no longer supported.', '8.2') . '</strong>'
                . ' ' . tr('A minor update to %0 is strongly recommended.', '8.4')
                . ' ' . $fullProviderMessage,
                new Tiki_Version_Upgrade('8.2', '8.4', 'error'),
            ],
            'Required MAJOR upgrade' => [
                '<strong>' . tr('Version %0 is no longer supported.', '4.3') . '</strong>'
                . ' ' . tr('A major upgrade to %0 is strongly recommended.', '9.0')
                . ' ' . $fullProviderMessage,
                new Tiki_Version_Upgrade('4.3', '9.0', 'error'),
            ],
            'Optional MAJOR upgrade (LTS)' => [
                tr('Version %0 is still supported%2. However, an upgrade to %1 is available.', '24.2 LTS', '27.0', ' ' . tr('(End of Life: %0)', '2027-03-31')),
                new Tiki_Version_Upgrade('24.2', '27.0', 'note'),
                ['24' => '2027-03-31'] // Mock data needed for this test case
            ],
            'Optional MAJOR upgrade (non-LTS)' => [
                tr('Version %0 is still supported%2. However, a major upgrade to %1 is available.', '9.0', '10.0', ' (' . tr('at least until Tiki %0.1 is released', '10') . ')'),
                new Tiki_Version_Upgrade('9.0', '10.0', 'note'),
                [] // No mock data needed here
            ],
            'Development version upgrade available' => [
                tr('You are using a development version: %0. This version is intended for testing and development purposes. For stability, consider switching to the latest stable release: %1.', '9.0beta2', '9.0'),
                new Tiki_Version_Upgrade('9.0beta2', '9.0', 'note'),
                []
            ],
            'Development version newer than stable' => [
                tr('You are using a development version: %0. This version is intended for testing and development purposes. The latest stable release (%1) is older than your current version, so downgrading is not recommended.', '10.0vcs', '9.0'),
                new Tiki_Version_Upgrade('10.0vcs', '9.0', 'note'),
                []
            ],
        ];
    }

    public function testIsStable()
    {
        $this->assertTrue(Tiki_Version_Version::get('27.1')->isStable());
        $this->assertFalse(Tiki_Version_Version::get('27.1vcs')->isStable());
        $this->assertFalse(Tiki_Version_Version::get('28.0beta')->isStable());
        $this->assertFalse(Tiki_Version_Version::get('27.0rc')->isStable());
        $this->assertFalse(Tiki_Version_Version::get('27.0pre')->isStable());
        $this->assertTrue(Tiki_Version_Version::get('25.5')->isStable());
    }

    public function testUnstableVersionComparison()
    {
        // Upgrade: unstable to stable (e.g., beta → final)
        $unstable = Tiki_Version_Version::get('27.0beta');
        $stable = Tiki_Version_Version::get('27.0');
        $this->assertTrue($stable->isStableUpgradeTo($unstable));
        $this->assertFalse($unstable->isStableUpgradeTo($stable));

        // Downgrade: unstable version is newer than last stable
        $unstable = Tiki_Version_Version::get('29.0vcs');
        $stable = Tiki_Version_Version::get('28.4');
        $this->assertFalse($unstable->isStableUpgradeTo($stable));
        $this->assertFalse($stable->isStableUpgradeTo($unstable));
    }

    public function testVerifyApproachingEolNotification()
    {
        global $prefs;

        $prefs['feature_eol_date_notifier'] = 'y';
        $fiveMonthsFromNow = date('Y-m-d', strtotime('+5 months'));
        $this->setupMockTWV(['24' => $fiveMonthsFromNow]);

        $checker = new Tiki_Version_Checker();
        $checker->setCycle('regular');
        $checker->setVersion('24.5');

        $out = '';
        $response = $checker->check(
            function ($url) use (&$out) {
                $out = $url;
                return <<<O
                        27.0
                        24.5
                        21.0
                        O;
            }
        );
        $this->assertEquals('https://tiki.org/regular.cycle', $out);
        $this->assertCount(2, $response);

        $expected = [
            new Tiki_Version_Upgrade('24.5', '27.0', 'note'),
            new Tiki_Version_Upgrade('24.5', null, 'warning')
        ];

        foreach ($expected as $expectedUpgrade) {
            $this->assertContainsEquals($expectedUpgrade, $response);
        }

        unset($prefs['feature_eol_date_notifier']);
    }

    public function testEolNotificationWithFeatureDisabled()
    {
        global $prefs;

        $prefs['feature_eol_date_notifier'] = 'n';
        $fiveMonthsFromNow = date('Y-m-d', strtotime('+5 months'));
        $this->setupMockTWV(['24' => $fiveMonthsFromNow]);

        $checker = new Tiki_Version_Checker();
        $checker->setCycle('regular');
        $checker->setVersion('24.5');

        $response = $checker->check(
            function ($url) use (&$out) {
                $out = $url;
                return  <<<O
                        27.0
                        24.5
                        21.0
                        O;
            }
        );

        $this->assertEquals('https://tiki.org/regular.cycle', $out);
        // Should only return upgrade messages, no EoL warning
        $this->assertCount(1, $response);
        $this->assertNotEquals('warning', $response[0]->getType());

        unset($prefs['feature_eol_date_notifier']);
    }

    public function testEolNotificationWithEolFarAway()
    {
        global $prefs;

        $prefs['feature_eol_date_notifier'] = 'y';
        $sevenMonthsFromNow = date('Y-m-d', strtotime('+7 months'));
        $this->setupMockTWV(['21' => $sevenMonthsFromNow]);

        $checker = new Tiki_Version_Checker();
        $checker->setCycle('regular');
        $checker->setVersion('21.1');

        $response = $checker->check(
            function ($url) {
                return <<<O
                24.0
                21.1
                18.5
                O;
            }
        );

        // Should not trigger EoL warning when EoL is more than 6 months away
        $hasEolWarning = false;
        foreach ($response as $upgrade) {
            if ($upgrade->getType() === 'warning') {
                $hasEolWarning = true;
                break;
            }
        }
        $this->assertFalse($hasEolWarning);

        unset($prefs['feature_eol_date_notifier']);
    }

    public function testMessageTypeClassification()
    {
        // Test required upgrade type
        $upgradeRequired = new Tiki_Version_Upgrade('8.2', '8.4', 'error');
        $this->assertEquals('error', $upgradeRequired->getType());

        // Test EoL warning type
        $upgradeEol = new Tiki_Version_Upgrade('21.1', null, 'warning');
        $this->assertEquals('warning', $upgradeEol->getType());

        // Test optional upgrade type
        $upgradeOptional = new Tiki_Version_Upgrade('8.4', '9.0', 'note');
        $this->assertEquals('note', $upgradeOptional->getType());
    }

    public function testEolMessageContent()
    {
        $eolDate = '2027-03-31';
        $this->setupMockTWV(['24' => $eolDate]);
        $upgrade = new Tiki_Version_Upgrade('24.5', null, 'warning');

        $message = $upgrade->getMessage();
        $this->assertStringContainsString('approaching its End of Life', $message);
        $this->assertStringContainsString($eolDate, $message);
        $this->assertStringContainsString('planning an upgrade is recommended', $message);
    }
}
