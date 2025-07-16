<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/*
 * Created on Jan 30, 2009
 *
 * Parent class of all test cases. For some reason PHPUnit doesn't deal
 * well with globals, so $backupGlobals should be set to false.
 * Use this class to set other PHPUnit variables, as needed.
 *
 */

//require_once (version_compare(PHPUnit_Runner_Version::id(), '3.5.0', '>=')) ? 'PHPUnit/Autoload.php' : 'PHPUnit/Framework.php';

use PHPUnit\Framework\TestCase;

abstract class TikiTestCase extends TestCase
{
    protected $backupGlobals = false;

    /**
     * @var string[]
     */
    private $warnings = [];

    protected function ensureDefaultGalleryExists()
    {
        $filegallib = TikiLib::lib('filegal');
        $info = $filegallib->get_file_gallery_info(1);
        if (! $info) {
            if (! isset($GLOBALS['user'])) {
                $GLOBALS['user'] = '';
            }
            $galleryId = $filegallib->replace_file_gallery(
                [
                    'name' => 'Default Gallery',
                    'description' => 'Default Gallery',
                ]
            );
            $filegallib->query("UPDATE tiki_file_galleries SET galleryId = 1 WHERE galleryId     = ?", [$galleryId]);
        }
    }

    protected function setPageRegex()
    {
        global $page_regex;
        // we must set the page regex, otherwise the links get not parsed
        // taken from: 'lib/setup/wiki.php' with  $prefs['wiki_page_regex'] == 'full'
        $page_regex = '([A-Za-z0-9_]|[\x80-\xFF])([\.: A-Za-z0-9_\-]|[\x80-\xFF])*([A-Za-z0-9_]|[\x80-\xFF])';
    }

    protected function assertThrowableMessage(
        string $message,
        callable $callback,
        ...$args
    ): void {
        try {
            $callback(...$args);
        } catch (Throwable $e) {
            $this->assertEquals($message, $e->getMessage());
        }
    }

    /**
     * @internal This method is not covered by the backward compatibility promise for PHPUnit
     */
    public function addWarning(string $warning): void
    {
        $this->warnings[] = $warning;
    }

    /**
     * Asserts that a specific user-triggered error with a given message is thrown.
     *
     * @param string   $message  The expected error message.
     * @param callable $callback The callable that is expected to trigger the error.
     * @param mixed    ...$args  Arguments to pass to the callable.
     *
     * @throws ErrorException
     */
    protected function assertTriggeredError(string $message, callable $callback, ...$args): void
    {
        // This error handler is to convert E_USER_NOTICE into an exception.
        $errorHandler = function ($severity, $errMessage, $file, $line) use ($message) {
            if ($severity & (E_USER_NOTICE | E_USER_WARNING | E_USER_ERROR | E_USER_DEPRECATED)) {
                if (! str_contains($errMessage, $message)) {
                    throw new \PHPUnit\Framework\ExpectationFailedException(
                        sprintf(
                            'Failed asserting that error message "%s" matches expected "%s".',
                            $errMessage,
                            $message
                        )
                    );
                }
                throw new \ErrorException($errMessage, 0, $severity, $file, $line);
            }
        };

        // Temporarily set the custom error handler.
        set_error_handler($errorHandler);

        try {
            $callback(...$args);

            // Assertion fails if code completes without throwing an exception,
            throw new \PHPUnit\Framework\ExpectationFailedException(
                'Expected an error to be triggered, but none was.'
            );
        } catch (\ErrorException $e) {
            // Assertion passes if we catch the expected ErrorException.
            $this->assertStringContainsString($message, $e->getMessage());
        } finally {
            restore_error_handler();
        }
    }
}
