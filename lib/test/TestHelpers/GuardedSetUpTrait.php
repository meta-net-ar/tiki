<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\TestHelpers;

/**
 * Surfaces failures from static fixture setup as real test failures.
 *
 * PHPUnit reports an exception thrown inside setUpBeforeClass() as an opaque
 * class-level error. Wrapping the setup body with guardStaticSetUp() and
 * calling abortIfStaticSetUpFailed() from setUp() instead fails each test
 * with the original message and stack trace.
 *
 * Usage:
 *
 *     public static function setUpBeforeClass(): void
 *     {
 *         self::guardStaticSetUp(function () {
 *             // fixture creation that may throw
 *         });
 *     }
 *
 *     protected function setUp(): void
 *     {
 *         parent::setUp();
 *         $this->abortIfStaticSetUpFailed();
 *     }
 */
trait GuardedSetUpTrait
{
    /** @var \Throwable|null */
    protected static $setUpException = null;

    protected static function guardStaticSetUp(callable $setUp): void
    {
        self::$setUpException = null;
        try {
            $setUp();
        } catch (\Throwable $e) {
            self::$setUpException = $e;
        }
    }

    protected function abortIfStaticSetUpFailed(): void
    {
        if (self::$setUpException) {
            $this->fail(
                'Exception during static setup: ' . self::$setUpException->getMessage()
                . "\n" . self::$setUpException->getTraceAsString()
            );
        }
    }
}
