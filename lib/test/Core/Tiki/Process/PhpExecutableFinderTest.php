<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Test\Tiki\Process;

use Tiki\Process\PhpExecutableFinder;
use PHPUnit\Framework\TestCase;
use Tiki\Process\Process;
use Tiki\Process\ProcessFactory;

class PhpExecutableFinderTest extends TestCase
{
    public function testFindWithPref()
    {
        global $prefs;
        $oldPrefValue = $prefs['php_cli_path'] ?? '';

        $prefs['php_cli_path'] = 'some/php/cli';

        $process = $this->createMock(Process::class);
        $process->expects($this->once())
            ->method('run');

        $process->expects($this->once())
            ->method('getOutput')
            ->willReturn('PHP 1.1.1 (cli) (built: Jan 21 2023 06:43:54) ( NTS )');

        $factory = $this->createMock(ProcessFactory::class);
        $factory->expects($this->once())
            ->method('create')
            ->willReturn($process);

        $finder = new PhpExecutableFinder($factory);
        $phpCli = $finder->find();

        $this->assertEquals('some/php/cli', $phpCli);

        $prefs['php_cli_path'] = $oldPrefValue;
    }

    public function testFind()
    {
        global $prefs;
        $oldPrefValue = $prefs['php_cli_path'] ?? '';

        $prefs['php_cli_path'] = '';

        $phpVersion = '9999.1.2'; // any version in the future should keep stop the test from breaking

        $process = $this->createMock(Process::class);
        $process->expects($this->once())
            ->method('run');

        $process->expects($this->once())
            ->method('getOutput')
            ->willReturn('PHP ' . $phpVersion . ' (cli) (built: Jan 21 2023 06:43:54) ( NTS )');

        $factory = $this->createMock(ProcessFactory::class);
        $factory->expects($this->once())
            ->method('create')
            ->willReturn($process);

        $finder = new PhpExecutableFinder($factory);
        $phpCli = $finder->find($version);

        $this->assertEquals(TIKI_PATH . 'bin' . DIRECTORY_SEPARATOR . 'php', $phpCli);
        $this->assertEquals($phpVersion, $version);

        $prefs['php_cli_path'] = $oldPrefValue;
    }

    public function testFindOldNew()
    {
        global $prefs;
        $oldPrefValue = $prefs['php_cli_path'] ?? '';

        $prefs['php_cli_path'] = '';

        $phpOldVersion = '1.2.3'; // any version old enough should work for the test
        $phpNewVersion = '9999.1.2'; // any version in the future should keep stop the test from breaking

        $processOld = $this->createMock(Process::class);
        $processOld->expects($this->once())
            ->method('run');

        $processOld->expects($this->once())
            ->method('getOutput')
            ->willReturn('PHP ' . $phpOldVersion . ' (cli) (built: Jan 21 2023 06:43:54) ( NTS )');

        $processNew = $this->createMock(Process::class);
        $processNew->expects($this->once())
            ->method('run');

        $processNew->expects($this->once())
            ->method('getOutput')
            ->willReturn('PHP ' . $phpNewVersion . ' (cli) (built: Jan 21 2023 06:43:54) ( NTS )');

        $factory = $this->createMock(ProcessFactory::class);
        $factory->expects($this->exactly(2))
            ->method('create')
            ->willReturn($processOld, $processNew);

        $finder = new PhpExecutableFinder($factory);
        $phpCli = $finder->find($version);

        $this->assertEquals(TIKI_PATH . '..' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php', $phpCli);
        $this->assertEquals($phpNewVersion, $version);

        $prefs['php_cli_path'] = $oldPrefValue;
    }

    /**
     * @param $commandOutput
     * @param $expectedVersion
     *
     * @return void
     *
     * @dataProvider dataForGetPhpVersion
     */
    public function testGetPhpVersion($commandOutput, $expectedVersion): void
    {
        $process = $this->createMock(Process::class);
        $process->expects($this->once())
            ->method('run');
        $process->expects($this->once())
            ->method('getOutput')
            ->willReturn($commandOutput);

        $factory = $this->createMock(ProcessFactory::class);
        $factory->expects($this->once())
            ->method('create')
            ->willReturn($process);

        $finder = new PhpExecutableFinder($factory);

        $version = $finder->getPhpVersion('php');

        $this->assertEquals($expectedVersion, $version);
    }

    /**
     * @param $commandOutput
     * @param $expectedVersion
     *
     * @return void
     *
     * @dataProvider dataForGetPhpVersion
     */
    public function testGetPhpVersionWithPhpFromFind($commandOutput, $expectedVersion): void
    {
        $process = $this->createMock(Process::class);
        $process->expects($this->once())
            ->method('run');
        $process->expects($this->once())
            ->method('getOutput')
            ->willReturn($commandOutput);

        $factory = $this->createMock(ProcessFactory::class);
        $factory->expects($this->once())
            ->method('create')
            ->willReturn($process);

        $finder = $this->getMockBuilder(PhpExecutableFinder::class)
            ->onlyMethods(['find'])
            ->setConstructorArgs([$factory])
            ->getMock();
        $finder->expects($this->once())
            ->method('find')
            ->willReturn('php');

        $version = $finder->getPhpVersion();

        $this->assertEquals($expectedVersion, $version);
    }

    public static function dataForGetPhpVersion(): array
    {
        return [
            [ // multiline input
                "PHP 7.4.33 (cli) (built: Jan 21 2023 06:43:54) ( NTS )\nCopyright (c) The PHP Group\nZend Engine v3.4.0, Copyright (c) Zend Technologies\n    with Zend OPcache v7.4.33, Copyright (c), by Zend Technologies",
                '7.4.33'
            ],
            [ // single line input
                'PHP 7.4.33 (cli) (built: Jan 21 2023 06:43:54) ( NTS )',
                '7.4.33'
            ],
            [ // extra stuff in the version
              'PHP 7.4.33-1+patch2 (cli) (built: Jan 21 2023 06:43:54) ( NTS )',
              '7.4.33'
            ],
            [ // Bad value (extra space at begin)
              ' PHP 7.4.33 (cli) (built: Jan 21 2023 06:43:54) ( NTS )',
              ''
            ],
            [ // Empty Output
              '',
              ''
            ],
            [ // No PHP Marker
              ' 7.4.33 (cli) (built: Jan 21 2023 06:43:54) ( NTS )',
              ''
            ],
            [ // No Numeric Version
              'PHP a.b.c (cli) (built: Jan 21 2023 06:43:54) ( NTS )',
              ''
            ],
        ];
    }

    /**
     * @dataProvider dataForGeneratePossiblePhpCliNames
     *
     * @param $include
     * @param $versions
     * @param $expected
     *
     * @return void
     */
    public function testGeneratePossiblePhpCliNames($include, $versions, $expected): void
    {
        $finder = new PhpExecutableFinder();
        $result = $finder->generatePossiblePhpCliNames($include, $versions);
        $this->assertEquals($expected, $result);
    }

    public static function dataForGeneratePossiblePhpCliNames(): array
    {
        return [
            [ // empty inputs, empty output
                [], ['8.1', '8.2'],
                []
            ],
            [ // virtualmin paths
                ['virtualmin'], ['8.1', '8.2'],
                [
                   TIKI_PATH . 'bin' . DIRECTORY_SEPARATOR . 'php',
                   TIKI_PATH . '..' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php',
                ],
            ],
            [ // base paths
                ['base'], ['8.1', '8.2'],
                [
                    'php', 'php-cli',
                ],
            ],
            [ // versions paths
                ['versions'], ['8.1', '8.2'],
                [
                    'php81', 'php8.1', 'php8.1-cli',
                    'php82', 'php8.2', 'php8.2-cli'
                ]
            ],
            [ // all 3 inputs
              ['virtualmin', 'base', 'versions'], ['8.1', '8.2'],
              [
                  TIKI_PATH . 'bin' . DIRECTORY_SEPARATOR . 'php',
                  TIKI_PATH . '..' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php',
                  'php', 'php-cli',
                  'php81', 'php8.1', 'php8.1-cli',
                  'php82', 'php8.2', 'php8.2-cli'
              ]
            ],
        ];
    }

    private function extractMajorMinorVersion($version): string
    {
        list($major, $minor) = explode('.', $version);
        return "{$major}.{$minor}";
    }

    public function testCliPhpversionIsConsistent()
    {

        $firstVersionKey = array_key_first(TIKI_PHP_CLI_VERSIONS_TO_SEARCH);
        $lastVersionKey = array_key_last(TIKI_PHP_CLI_VERSIONS_TO_SEARCH);

        $this->assertEquals(
            $this->extractMajorMinorVersion(TIKI_MIN_PHP_VERSION),
            $this->extractMajorMinorVersion(
                TIKI_PHP_CLI_VERSIONS_TO_SEARCH[$firstVersionKey]
            ),
            "The first version of the array must match TIKI_MIN_PHP_VERSION"
        );

        $this->assertEquals(
            $this->extractMajorMinorVersion(TIKI_MAX_SUPPORTED_PHP_VERSION),
            $this->extractMajorMinorVersion(
                TIKI_PHP_CLI_VERSIONS_TO_SEARCH[$lastVersionKey]
            ),
            "The last version to look for must match TIKI_MAX_SUPPORTED_PHP_VERSION"
        );
    }
}
