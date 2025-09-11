<?php

declare(strict_types=1);

namespace Tiki\Tests\Core;

use Tiki\SortModeValidator;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../core/SortModeValidator.php';

class SortModeValidatorTest extends TestCase
{
    protected function setUp(): void
    {
        global $db;

        // Fake DB used by validateAgainstTables()
        $db = new class {
            public function getColumnNamesForTable(array $tables): array
            {
                return ['name', 'created'];
            }
        };
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['db']);
    }

    /* --------------------------------------------------
     * validateAgainstTables()
     * -------------------------------------------------- */

    public function testValidateAgainstTablesReturnsRequestedWhenValid(): void
    {
        $error = null;

        $result = SortModeValidator::validateAgainstTables(
            'name_asc',
            ['users'],
            $error
        );

        $this->assertSame('name_asc', $result);
        $this->assertNull($error);
    }

    public function testValidateAgainstTablesReturnsNullWhenInvalid(): void
    {
        $error = null;

        $result = SortModeValidator::validateAgainstTables(
            'invalid_desc',
            ['users'],
            $error
        );

        $this->assertNull($result);
        $this->assertSame('Invalid sort column.', $error);
    }

    public function testValidateAgainstTablesReturnsNullWhenEmpty(): void
    {
        $error = null;

        $result = SortModeValidator::validateAgainstTables(
            null,
            ['users'],
            $error
        );

        $this->assertNull($result);
        $this->assertNull($error);
    }

    /* --------------------------------------------------
     * validateAgainstWhitelist()
     * -------------------------------------------------- */

    public function testValidateAgainstWhitelistReturnsRequestedWhenValid(): void
    {
        $error = null;

        $result = SortModeValidator::validateAgainstWhitelist(
            'created_desc',
            ['name', 'created'],
            $error
        );

        $this->assertSame('created_desc', $result);
        $this->assertNull($error);
    }

    public function testValidateAgainstWhitelistReturnsNullWhenInvalid(): void
    {
        $error = null;

        $result = SortModeValidator::validateAgainstWhitelist(
            'unknown_asc',
            ['name', 'created'],
            $error
        );

        $this->assertNull($result);
        $this->assertSame('Invalid sort column.', $error);
    }

    public function testValidateAgainstWhitelistReturnsNullWhenEmpty(): void
    {
        $error = null;

        $result = SortModeValidator::validateAgainstWhitelist(
            '',
            ['name', 'created'],
            $error
        );

        $this->assertNull($result);
        $this->assertNull($error);
    }
}
