<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Test\Core\TikiDb;

use PHPUnit\Framework\TestCase;
use TikiDb_Initializer;
use Tiki\TikiDb\PdoDb;

class PdoDbTransactionTest extends TestCase
{
    protected const TABLE = 'test_items';

    /**
     * @var PdoDb
     */
    protected $db;

    protected function setUp(): void
    {
        $this->db = $this->getTikiDb();
        $this->tableCreate();
    }

    protected function tearDown(): void
    {
        $this->tableDrop();
    }

    public function testCommitTransaction()
    {
        $this->db->beginTransaction();
        $this->db->query("INSERT INTO `" . self::TABLE . "` (name) VALUES (?)", ['Apple']);
        $this->db->commit();

        $result = $this->db->fetchAll("SELECT * FROM `" . self::TABLE . "`");
        $this->assertCount(1, $result);
        $this->assertEquals('Apple', $result[0]['name']);
    }

    public function testRollbackTransaction()
    {
        $this->db->beginTransaction();
        $this->db->query("INSERT INTO `" . self::TABLE . "` (name) VALUES (?)", ['Banana']);
        $this->db->rollback();

        $result = $this->db->fetchAll("SELECT * FROM `" . self::TABLE . "`");
        $this->assertCount(0, $result);
    }

    public function testCommitWithoutTransactionReturnsNull()
    {
        $this->assertNull($this->db->commit());
    }

    public function testRollbackWithoutTransactionReturnsNull()
    {
        $this->assertNull($this->db->rollback());
    }

    protected function tableCreate(): void
    {
        $this->tableDrop();
        $this->db->query("CREATE TABLE `" . self::TABLE . "` (`id` INT NOT NULL AUTO_INCREMENT, `name` VARCHAR(255) NOT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB;");
    }

    protected function tableDrop(): void
    {
        $this->db->query("DROP TABLE IF EXISTS `" . self::TABLE . "`");
    }

    protected function getTikiDb(): PdoDb
    {
        include TIKI_PATH . '/lib/test/local.php';

        $initializer = new TikiDb_Initializer();
        $initializer->setPreferredConnector('pdo');

        return $initializer->getConnection([
            'host' => $host_tiki,
            'user' => $user_tiki,
            'pass' => $pass_tiki,
            'dbs'  => $dbs_tiki,
            'charset' => $client_charset,
        ]);
    }
}
