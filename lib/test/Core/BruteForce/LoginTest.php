<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Core\BruteForce;

use PHPUnit\Framework\TestCase;
use Tiki\BruteForce\BruteForce;
use TikiDb;

class LoginTest extends TestCase
{
    private $bruteForce;
    private $bruteForceTable;

    protected function setUp(): void
    {
        $this->bruteForce = new BruteForce();
        $this->bruteForceTable = TikiDb::get()->table('tiki_bruteforce_attempts');
    }

    protected function tearDown(): void
    {
        $properties = ['user' => 'testuser', 'ip' => '1.2.3.4'];
        foreach ($properties as $key => $value) {
            $propertyJson = json_encode([$key => $value]);
            $propertyHash = hash('sha256', $propertyJson);

            $keys = [
                'operation' => 'login',
                'properties_hash' => $propertyHash,
            ];

            $this->bruteForceTable->deleteMultiple($keys);
        }
    }

    public function testLoginAttemptInsert(): void
    {
        $properties = ['user' => 'testuser', 'ip' => '1.2.3.4'];
        $this->bruteForce->attempt('login', $properties);

        $record = $this->bruteForceTable->fetchRow(
            ['attempt_count'],
            ['operation' => 'login', 'properties' => json_encode(['user' => 'testuser'])]
        );

        $this->assertNotFalse($record);
        $this->assertEquals(1, $record['attempt_count']);
    }

    public function testLoginAttemptUpdate(): void
    {
        $properties = ['user' => 'testuser', 'ip' => '1.2.3.4'];
        $this->bruteForce->attempt('login', $properties);
        $this->bruteForce->attempt('login', $properties);

        $record = $this->bruteForceTable->fetchRow(
            ['attempt_count'],
            ['operation' => 'login', 'properties' => json_encode(['user' => 'testuser'])]
        );

        $this->assertNotFalse($record);
        $this->assertEquals(2, $record['attempt_count']);
    }

    public function testIsLoginAllowed(): void
    {
        $properties = ['user' => 'testuser'];

        $this->bruteForce->attempt('login', $properties);

        $record = $this->bruteForceTable->fetchRow(
            ['id', 'attempt_count', 'attempt_time'],
            ['operation' => 'login', 'properties' => json_encode(['user' => 'testuser'])]
        );

        $this->assertNotFalse($record);

        $this->bruteForceTable->update(['attempt_time' => time() - 10], ['id' => $record['id']]);

        $this->assertTrue($this->bruteForce->isOperationAllowed('login', $properties));
    }

    public function testIsLoginNotAllowed(): void
    {
        $properties = ['ip' => '1.2.3.4'];

        $this->bruteForce->attempt('login', $properties);

        $record = $this->bruteForceTable->fetchRow(
            ['id', 'attempt_count', 'attempt_time'],
            ['operation' => 'login', 'properties' => json_encode(['ip' => '1.2.3.4'])]
        );

        $this->bruteForceTable->update(['attempt_time' => time()], ['id' => $record['id']]);

        $this->assertFalse($this->bruteForce->isOperationAllowed('login', $properties));
    }

    public function testLoginOperationNotAllowedDueToForgetTime(): void
    {
        global $prefs;

        $properties = ['user' => 'testuser'];

        $forgetTime = time() - (intval($prefs['bruteforce_forget_time']) * 60);

        $this->bruteForce->attempt('login', $properties);

        $record = $this->bruteForceTable->fetchRow(
            ['id', 'attempt_count', 'attempt_time'],
            ['operation' => 'login', 'properties' => json_encode(['user' => 'testuser'])]
        );

        $this->bruteForceTable->update(['attempt_time' => $forgetTime - 10], ['id' => $record['id']]);

        $this->assertTrue($this->bruteForce->isOperationAllowed('login', $properties));
    }

    public function testLoginSuccess(): void
    {
        $properties = ['user' => 'testuser', 'ip' => '1.2.3.4'];
        $this->bruteForce->attempt('login', $properties);

        $this->bruteForce->success('login', $properties);

        $record = $this->bruteForceTable->fetchRow(
            ['attempt_count'],
            ['operation' => 'login', 'properties' => json_encode(['user' => 'testuser'])]
        );

        $this->assertFalse($record);
    }
}
