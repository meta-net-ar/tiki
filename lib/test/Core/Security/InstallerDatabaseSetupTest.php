<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\Core\Security;

require_once TIKI_PATH . 'installer/installlib.php';

/**
 * @group unit
 */
class InstallerDatabaseSetupTest extends \TikiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \Feedback::clear();
    }

    public function testInstallerIdentifierValidationRejectsSqlMetacharacters(): void
    {
        $this->assertTrue(installer_is_valid_mysql_identifier('tiki-prod_29'));
        $this->assertFalse(installer_is_valid_mysql_identifier("attacker`@`%` IDENTIFIED BY 'pwn3d';--"));
        $this->assertSame('`tiki``prod`', installer_quote_mysql_identifier('tiki`prod'));
    }

    public function testCreateTikiDbUserRejectsUnsafeUserNames(): void
    {
        $db = new class {
            public bool $called = false;

            public function qstr($str)
            {
                return "'" . str_replace(['\\', "'"], ['\\\\', "\\'"], $str) . "'";
            }

            public function queryError($sql, &$error, $values = null)
            {
                $this->called = true;
                $error = '';

                return true;
            }
        };

        $result = \createTikiDBUser($db, 'db.example', "attacker`@`%` IDENTIFIED BY 'pwn3d';--", 'ignored', 'tiki');

        $this->assertFalse($result);
        $this->assertFalse($db->called);
        $this->assertNotEmpty(\Feedback::errorMessages());
    }

    public function testCreateTikiDbUserBuildsSafelyQuotedGrantStatement(): void
    {
        $db = new class {
            public string $sql = '';

            public function qstr($str)
            {
                return "'" . str_replace(['\\', "'"], ['\\\\', "\\'"], $str) . "'";
            }

            public function queryError($sql, &$error, $values = null)
            {
                $this->sql = $sql;
                $error = '';

                return true;
            }
        };

        $result = \createTikiDBUser($db, 'db.example', 'tiki-prod_user', "o'hara", 'tiki-prod');

        $this->assertTrue($result);
        $this->assertSame(
            "GRANT ALL PRIVILEGES ON `tiki-prod`.* TO 'tiki-prod_user'@'%' IDENTIFIED BY 'o\\'hara';",
            $db->sql
        );
    }
}
