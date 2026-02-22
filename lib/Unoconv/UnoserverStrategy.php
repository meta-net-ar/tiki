<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Unoconv;

use Tiki\Process\Process;
use Symfony\Component\Process\ExecutableFinder;

class UnoserverStrategy implements ConverterInterface
{
    protected static $unoServerBinary = null;

    public const DEFAULT_PORT = 2003;

    public const NAME = 'unoserver';

    public static function isLibraryAvailable()
    {
        if (! empty(self::$unoServerBinary)) {
            return true;
        }

        $executableFinder = new ExecutableFinder();
        $binary = $executableFinder->find('unoconvert');

        if (! empty($binary)) {
            self::$unoServerBinary = $binary;
            return true;
        }

        return false;
    }

    public function convertFile($sourcePath, $destinationPath, $format = 'pdf', $pageRange = null)
    {
        if (! file_exists($sourcePath)) {
            throw new \Exception(tr("Input file not found: %0", $sourcePath));
        }

        // Ensure binary is detected if not already cached
        if (empty(self::$unoServerBinary)) {
            if (! self::isLibraryAvailable()) {
                throw new \Exception(tr("Unoserver (unoconvert) binary not found."));
            }
        }

        $binary = self::$unoServerBinary;
        $cmd = [$binary, $sourcePath, $destinationPath];

        if (! empty($pageRange) && preg_match('/^\d+-\d+$/', $pageRange)) {
            $cmd[] = '-e';
            $cmd[] = 'PageRange=' . $pageRange;
        }

        $process = new Process($cmd);
        $process->run();

        if (! file_exists($destinationPath) || $process->getExitCode() !== 0) {
            $errorOutput = $process->getErrorOutput();
            $standardOutput = $process->getOutput();

            $errorMessage = "Unoserver conversion failed for $sourcePath\n";
            $errorMessage .= "Command: " . $process->getCommandLine() . "\n";
            $errorMessage .= "Exit code: " . $process->getExitCode() . "\n";

            if ($errorOutput) {
                $errorMessage .= "Error output: " . trim($errorOutput) . "\n";
            }
            if ($standardOutput) {
                $errorMessage .= "Standard output: " . trim($standardOutput);
            }

            throw new \Exception(tr($errorMessage));
        }

        return true;
    }

    /**
     * Check if the Unoserver daemon is running
     * @return bool True if unoserver is running on the configured port, False otherwise
     */
    public static function isServerRunning()
    {
        global $prefs;

        $port = $prefs['alchemy_unoserver_port'] ?: self::DEFAULT_PORT;

        // Suppress only connection warnings (expected when server is not running)
        $socket = @fsockopen('localhost', $port, $errno, $errstr, 2);

        if ($socket) {
            fclose($socket);
            return true;
        }

        return false;
    }
}
