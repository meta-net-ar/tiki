<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Process;

use Symfony\Component\Process\PhpExecutableFinder as SymfonyPhpExecutableFinder;
use Tiki\TikiInit;

class PhpExecutableFinder
{
    /**
     * @var int timeout in seconds waiting for php commands to execute, default 5 min (300s)
     */
    protected int $timeout = 300;

    /**
     * Represents versions of PHP (major.minor) that we will try to search.
     *
     * There is no real way to programmatically guess what versions of PHP were released.
     *
     * @see generatePossiblePhpCliNames
     */
    private const PHP_VERSIONS_TO_SEARCH = TIKI_PHP_CLI_VERSIONS_TO_SEARCH;
    /**
     * Static base list of PHP possible command names, additional PHP binary names will be generated dynamically
     *
     * @see generatePossiblePhpCliNames
     */
    protected const PHP_COMMAND_NAMES = [
        'php',
        'php-cli',
    ];

    /**
     * @var null|string|false Will hold the php bin detected
     */
    protected $phpCli = null;

    /**
     * @var ProcessFactory used when creating processes instead of calling new directly
     */
    protected ProcessFactory $processFactory;

    /**
     * @var SymfonyPhpExecutableFinder Used as one of the fallback options
     */
    protected SymfonyPhpExecutableFinder $symfonyPhpExecutableFinder;

    /**
     * Constructor allows injection of the classes used
     *
     * @param ProcessFactory|null             $processFactory
     * @param SymfonyPhpExecutableFinder|null $symfonyPhpExecutableFinder
     */
    public function __construct(?ProcessFactory $processFactory = null, ?SymfonyPhpExecutableFinder $symfonyPhpExecutableFinder = null)
    {
        if (is_null($processFactory)) {
            $processFactory = new ProcessFactory();
        }
        $this->processFactory = $processFactory;

        if (is_null($symfonyPhpExecutableFinder)) {
            $symfonyPhpExecutableFinder = new SymfonyPhpExecutableFinder();
        }
        $this->symfonyPhpExecutableFinder = $symfonyPhpExecutableFinder;
    }

    /**
     * Finds The PHP executable.
     *
     * @param string|null $version will be used to return the version of php cli selected
     * @param bool   $useCache Should cache the php cli result during the object lifecycle
     *
     * @return string|false The PHP executable path or false if it cannot be found
     */
    public function find(?string &$version = null, bool $useCache = true)
    {
        global $prefs;

        // optimization to avoid running detection, will be false|string after first call
        if ($useCache && (! is_null($this->phpCli))) {
            return $this->phpCli;
        }

        // default (not detected) will be false, will be set to the cli detected below
        $phpCli = false;

        $possibleCliList = $this->findAll(true);

        if (count($possibleCliList) > 0) {
            $possibleCli = reset($possibleCliList); //take the first one
            $phpCli = $possibleCli['command'];
            $version = $possibleCli['version'];
        }

        if ($useCache) {
            $this->phpCli = $phpCli;
        }

        return $phpCli;
    }

    public function findAll(bool $returnOnFirstMatch = false, bool $ignoreMinimalVersion = false): array
    {
        global $prefs;

        $phpCliList = [];

        // If path is set by the admin, do not try to guess, just use the pref value
        if (! empty($prefs['php_cli_path'])) {
            $phpCliList[$prefs['php_cli_path']] = [
                'command' => $prefs['php_cli_path'],
                'version' => $this->getPhpVersion($prefs['php_cli_path'])
            ];
            if ($returnOnFirstMatch) {
                return array_values($phpCliList);
            }
        }

        $command_locations = $this->generatePossiblePhpCliNames();

        // try to check the PHP binary path using operating system resolution mechanisms
        foreach ($command_locations as $cli) {
            if (TikiInit::isWindows()) {
                $process = $this->processFactory->create(['where', $cli . '.exe']);
            } else {
                $process = $this->processFactory->create([$cli, '--version']);
            }
            $process->setTimeout($this->timeout);
            $process->run();
            $output = $process->getOutput();
            if (! $output) {
                continue;
            }
            $version = $this->extractVersionFromString($output);
            if ((! $ignoreMinimalVersion) && (! $this->isVersionSupported($version))) {
                continue;
            }
            $phpCliList[$cli] = [
                'command' => $cli,
                'version' => $version
            ];
            if ($returnOnFirstMatch) {
                return array_values($phpCliList);
            }
        }

        // Fall back to path search
        if (! empty($_SERVER['PATH'])) {
            foreach (explode(PATH_SEPARATOR, $_SERVER['PATH']) as $path) {
                foreach (self::PHP_COMMAND_NAMES as $cli) {
                    $possibleCli = $path . DIRECTORY_SEPARATOR . $cli;
                    if (TikiInit::isWindows()) {
                        $possibleCli .= '.exe';
                    }
                    if (file_exists($possibleCli) && is_executable($possibleCli)) {
                        $version = $this->getPhpVersion($possibleCli);
                        if ((! $ignoreMinimalVersion) && (! $this->isVersionSupported($version))) {
                            continue;
                        }
                        $phpCliList[$possibleCli] = [
                            'command' => $possibleCli,
                            'version' => $version
                        ];
                        if ($returnOnFirstMatch) {
                            return array_values($phpCliList);
                        }
                    }
                }
            }
        }

        // Fall back to use Symfony Package since on some systems the shell path isn't the same as the webserver one
        $cli = $this->symfonyPhpExecutableFinder->find();
        $version = $this->getPhpVersion($cli);
        if ($ignoreMinimalVersion || $this->isVersionSupported($version)) {
            $phpCliList[$cli] = [
                'command' => $cli,
                'version' => $version
            ];
            if ($returnOnFirstMatch) {
                return array_values($phpCliList);
            }
        }

        return array_values($phpCliList);
    }

    /**
     * Check the version of the command line version of PHP
     *
     * @param $php
     * @return string
     */
    public function getPhpVersion($php = null): string
    {
        if (is_null($php)) {
            $php = $this->find();
        }

        $process = $this->processFactory->create([$php, '--version']);
        $process->run();
        return $this->extractVersionFromString($process->getOutput());
    }

    /**
     * Generates an array with possible php cli names to be used as part of the detection process
     * we will return:
     *
     * 1) Virtualmin specific cli names
     * 2) the static list from PHP_COMMAND_NAMES
     * 3) range versions based on $versionsToSearch (default: self::PHP_VERSIONS_TO_SEARCH)
     *
     * @param string[] $include what possible sources should be included virtualmin|base|versions
     * @param string[] $versionsToSearch optional list of versions to search, default self::PHP_VERSIONS_TO_SEARCH
     *
     * @return array
     */
    public function generatePossiblePhpCliNames(array $include = ['virtualmin', 'base', 'versions'], array|null $versionsToSearch = null): array
    {
        if (is_null($versionsToSearch)) {
            $versionsToSearch = self::PHP_VERSIONS_TO_SEARCH;
        }

        $commandNames = [];

        // add virtualmin per-domain locations first
        if (in_array('virtualmin', $include) && (! TikiInit::isWindows())) {
            $commandNames[] = rtrim(TIKI_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php';
            $commandNames[] = rtrim(TIKI_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php';
        }

        // the static list from PHP_COMMAND_NAMES
        if (in_array('base', $include)) {
            $commandNames = array_merge($commandNames, array_values(self::PHP_COMMAND_NAMES));
        }

        if (in_array('versions', $include)) {
            foreach ($versionsToSearch as $version) {
                list($major, $minor) = explode('.', $version);
                // generates 3 options per version: php82, php8.2 and php8.2-cli
                $commandNames[] = 'php' . $major . $minor;
                $commandNames[] = 'php' . $major . '.' . $minor;
                $commandNames[] = 'php' . $major . '.' . $minor . '-cli';
            }
        }

        return $commandNames;
    }

    /**
     * Return the value of TIKI_MIN_PHP_VERSION
     *
     * @return string
     */
    public function getMinimalVersionSupported(): string
    {
        return TIKI_MIN_PHP_VERSION;
    }

    /**
     * Check if a given version is supported
     *
     * @param $version
     *
     * @return bool
     */
    public function isVersionSupported($version): bool
    {
        return version_compare($version, TIKI_MIN_PHP_VERSION, '>=');
    }

    /**
     * In case we want to reset the cached value for PHP Cli during the object lifecycle
     *
     * @return void
     */
    public function resetCachedPhpCli()
    {
        $this->phpCli = null;
    }

    /**
     * @param string $text
     *
     * @return mixed|string
     */
    protected function extractVersionFromString(string $text): string
    {
        foreach (explode("\n", $text) as $line) {
            $parts = explode(' ', $line);
            if ($parts[0] === 'PHP') {
                if (preg_match('/([0-9]+\.[0-9]+\.[0-9]+)[^0-9]*/', $parts[1], $matches)) {
                    return (string)$matches[1];
                }
            }
        }

        return '';
    }
}
