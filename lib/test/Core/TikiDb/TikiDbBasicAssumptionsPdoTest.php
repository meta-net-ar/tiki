<?php

namespace Tiki\Test\TikiDb;

use PHPUnit\Framework\TestCase;
use TikiDb_Initializer;
use TikiLib;

class TikiDbBasicAssumptionsPdoTest extends TestCase
{
    protected const TABLE = 'TikiDbBasicAssumptionsPdoTestTable';
    protected const SLEEP = 0.001;

    /**
     * @var array
     */
    protected static $preferencesCopy;

    /**
     * @var \TikiDb
     */
    protected $db;

    public static function setUpBeforeClass(): void
    {
        self::savePreferences();

        global $prefs;

        // Enable logging for all requests, to make sure there is no impact in the base expectations
        // E.g. https://gitlab.com/tikiwiki/tiki/-/merge_requests/5879 broke the assumption that the last
        // returned id was from the query we executed
        $prefs['log_sql'] = 'y';
        $prefs['log_sql_perf_min'] = 0.0; // try to force to always log
    }

    public static function tearDownAfterClass(): void
    {
        self::restorePreferences();
    }

    protected function setUp(): void
    {
        $this->db = $this->getTikiDb();
        $this->tableCreate();
    }

    protected function tearDown(): void
    {
        $this->tableDrop();
    }

    //
    // Tests
    //

    public function testInsertAddsExactlyOneRow()
    {
        // generate a unique value. We will then be able to easily search for this data
        $uniqueName = $this->uniqueString(__FUNCTION__);

        $this->db->query('INSERT INTO `' . self::TABLE . '` (`name`, `sleep_val`)'
            . ' VALUES (?, SLEEP(' . self::SLEEP . '))', [$uniqueName]);

        TikiLib::events()->trigger('tiki.process.shutdown'); // trigger SQL log writer.

        // check that we have one row with the unique name
        $entries = $this->db->fetchAll('SELECT * FROM `' . self::TABLE . '` WHERE `name` = ?', [$uniqueName]);
        $this->assertCount(1, $entries, 'We expected exactly one row with `name` = ' . $uniqueName);

        // In this test we expect to have only one row in the Table also
        $count = $this->db->getOne('SELECT COUNT(*) as count from `' . self::TABLE . '`');
        $this->assertEquals(1, $count, 'We expected only one record in the table');
    }

    public function testInsertMultipleRowsWorksAsExpected()
    {
        $uniqueName = '';
        $rowCount = 3;
        for ($row = 1; $row <= $rowCount; $row++) {
            // generate a unique value. We will then be able to easily search for this data
            $uniqueName = $this->uniqueString(__FUNCTION__);

            $this->db->query(
                'INSERT INTO `' . self::TABLE . '` (`name`, `sleep_val`)'
                    . ' VALUES (?, SLEEP(' . self::SLEEP . '))',
                [$uniqueName]
            );
        }

        TikiLib::events()->trigger('tiki.process.shutdown'); // trigger SQL log writer.

        // check that we have one row with the unique name
        $entries = $this->db->fetchAll('SELECT * FROM `' . self::TABLE . '` WHERE `name` = ?', [$uniqueName]);
        $this->assertCount(1, $entries, 'We expected exactly one row with `name` = ' . $uniqueName);

        // In this test we expect to have $rowCount rows in the Table also
        $count = $this->db->getOne('SELECT COUNT(*) as count from `' . self::TABLE . '`');
        $this->assertEquals($rowCount, $count, 'We expected exactly ' . $rowCount . ' records in the table');
    }

    public function testLastInsertIdReturnsTheRightValue()
    {
        // generate a unique value. We will then be able to easily search for this data
        $uniqueName = $this->uniqueString(__FUNCTION__);

        $this->db->query('INSERT INTO `' . self::TABLE . '` (`name`, `sleep_val`)'
            . ' VALUES (?, SLEEP(' . self::SLEEP . '))', [$uniqueName]);

        $id = $this->db->lastInsertId();

        TikiLib::events()->trigger('tiki.process.shutdown'); // trigger SQL log writer.

        $this->assertIsNumeric($id, "lastInsertId didn't return an numeric value");

        // Fetch row using name and assert it looks ok and match the lastInsertId
        $rowId = $this->db->getOne('SELECT id FROM `' . self::TABLE . '` WHERE `name` = ?', [$uniqueName]);
        $this->assertEquals($rowId, $id, 'The row inserted has `id` = "' . $rowId . '" but lastInsertId() returned "' . $id . '"') ;

        // Fetch name by Id and make sure getOne will retrieve the right value
        $rowName = $this->db->getOne('SELECT `name` FROM `' . self::TABLE . '` WHERE `id` = ?', [$id]);
        $this->assertEquals($uniqueName, $rowName, 'The name of the row retrieved (' . $rowName . ') is different from the one saved (' . $uniqueName . ') when using lastInsertId() (' . $id . ')');
    }

    //
    // Support functions
    //

    /**
     * Attempts to generate a unique string to use in each test to identify specific rows of data
     *
     * @param $prefix prefix to add to the text
     * @return string
     */
    protected function uniqueString($prefix): string
    {
        return $prefix . " " . uniqid("", true) . " " . rand(0, PHP_INT_MAX);
    }

    /**
     * Retrieves the configuration from the local.php file
     * @return array
     */
    protected function getConfigFromLocal()
    {
        include TIKI_PATH . '/lib/test/local.php';

        return [
            'host' => $host_tiki,
            'user' => $user_tiki,
            'pass' => $pass_tiki,
            'dbs' => $dbs_tiki,
            'charset' => $client_charset,
        ];
    }

    /**
     * Retrieves a TikiDB object, Tiki\TikiDb\PdoDb
     */
    protected function getTikiDb()
    {
        $initializer = new TikiDb_Initializer();
        $initializer->setPreferredConnector('pdo');
        $db = $initializer->getConnection($this->getConfigFromLocal());

        return $db;
    }

    protected function tableCreate(): void
    {
        $this->tableDrop();
        $this->db->query('
            CREATE TABLE `' . self::TABLE . '` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `name` varchar(255) NOT NULL,
              `sleep_val` int DEFAULT 0,
              PRIMARY KEY (`id`)
            ) AUTO_INCREMENT=1;
        ');
    }

    protected function tableDrop(): void
    {
        $this->db->query('DROP TABLE IF EXISTS ' . self::TABLE);
    }

    protected static function savePreferences(): void
    {
        global $prefs;

        self::$preferencesCopy = $prefs;
    }

    protected static function restorePreferences(): void
    {
        global $prefs;
        $prefs = self::$preferencesCopy;
    }
}
