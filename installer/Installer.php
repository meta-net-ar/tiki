<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Installer;

use LogicException;
use Tiki\TikiInit;
use Tiki_Profile;
use TikiDb_Bridge;
use SplSubject;
use SplObjectStorage;
use SplObserver;
use TWVersion;
use Exception;

/**
 * @see Patch
 */
class Installer extends TikiDb_Bridge implements SplSubject
{
    public static $instance = null; // Singleton instance
    private $observers;

    public $scripts = [];
    public $executed = [];

    private string $currentPatchName = '';

    public $queries = [
        'currentStmt' => '',
        'currentFile' => '',
        'executed' => 0,
        'total' => 0,
        'files' => [], //path of the files executed
        'successful' => [],
        'failed' => [],
        'warnings' => []
    ];

    public $useInnoDB = true;
    public $autoRegister = false;

    private function __construct()
    {
        $this->observers = new SplObjectStorage();
        $this->buildPatchList();
        $this->buildScriptList();
    }

    /**
     * Get the instance (creating one if necessary)
     * @return Installer
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function cleanInstall()
    {
        if ($image = $this->getBaseImage()) {
            $this->runFile($image, true, true);
            $this->buildPatchList();
            $this->buildScriptList();
        } else {
            // No image specified, standard install
            $this->runFile(__DIR__ . '/../' . TIKI_BASE_SQL_SCHEMA_PATH . '/tiki.sql');
            if ($this->isMySQLFulltextSearchSupported()) {
                $this->runFile(__DIR__ . '/../' . TIKI_BASE_SQL_SCHEMA_PATH . '/tiki_fulltext_indexes.sql', true, true);
            }
            if ($this->useInnoDB) {
                $this->runFile(__DIR__ . '/../' . TIKI_BASE_SQL_SCHEMA_PATH . '/tiki_innodb.sql', true, true);
            } else {
                $this->runFile(__DIR__ . '/../' . TIKI_BASE_SQL_SCHEMA_PATH . '/tiki_myisam.sql', true, true);
            }
            $this->buildPatchList();
            $this->buildScriptList();

            // Base SQL file contains the distribution tiki patches up to this point
            foreach (Patch::getPatches([Patch::NOT_APPLIED]) as $patchName => $patch) {
                if (preg_match('/_tiki$/', $patchName)) {
                    $patch->record();
                }
            }
        }

        $this->update();
    }

    public function update()
    {
        // Mark InnoDB usage for updates
        if (strcasecmp($this->getCurrentEngine(), "InnoDB") == 0) {
            $this->useInnoDB = true;
        } else {
            $this->useInnoDB = false;
        }

        if (! $this->tableExists('tiki_schema')) {
            // DB too old to handle auto update

            if (file_exists(__DIR__ . '/../' . TIKI_BASE_SQL_SCHEMA_PATH . '/custom_upgrade.sql')) {
                $this->runFile(__DIR__ . '/../' . TIKI_BASE_SQL_SCHEMA_PATH . '/custom_upgrade.sql');
            } else {
                // If 1.9
                if (! $this->tableExists('tiki_minichat')) {
                    $this->runFile(__DIR__ . '/../' . TIKI_BASE_SQL_SCHEMA_PATH . '/tiki_1.9to2.0.sql');
                }

                $this->runFile(__DIR__ . '/../' . TIKI_BASE_SQL_SCHEMA_PATH . '/tiki_2.0to3.0.sql');
            }
        }

        $this->assureDefaultCharSetIsAlignedWithTikiSchema();
        $this->assureInnoDdTableRowFormatIsDynamicOrBetter();

        foreach (Patch::getPatches([Patch::NOT_APPLIED]) as $patchName => $patch) {
            try {
                $this->installPatch($patchName);
            } catch (MySQLWarningException $e) {
                throw $e;
            } catch (Exception $e) {
                if ($e->getCode() != 2) {
                    throw $e;
                } else {
                    return false;
                }
            }
        }

        foreach ($this->scripts as $script) {
            $this->runScript($script);
        }

        return true;
    }

    /**
     * @param $patch
     * @param $force true if the patch should be applied even if already marked as applied
     * @throws Exception Code 1 if unknown patch, 2 if application attempt fails, 3 if patch was already installed and $force is false
     */
    public function installPatch($patch, $force = false)
    {
        $this->currentPatchName = (string) $patch;
        if (! $force && isset(Patch::$list[$patch]) && Patch::$list[$patch]->isApplied()) {
            throw new Exception('Patch already applied', 3);
        }

        $schema = __DIR__ . "/schema/$patch.sql";
        $script = __DIR__ . "/schema/$patch.php";
        $profile = __DIR__ . "/schema/$patch.yml";

        $pre = "pre_$patch";
        $post = "post_$patch";
        $standalone = "upgrade_$patch";

        if (file_exists($script)) {
            require $script;
            $status = true;
        }

        global $dbs_tiki;
        $local_php = TikiInit::getCredentialsFile();
        if (is_readable($local_php)) {
            require($local_php);
            unset($host_tiki, $user_tiki, $pass_tiki);
        }

        if (function_exists($standalone)) {
            $status = $standalone($this);
            if (is_null($status)) {
                $status = true;
            }
        } else {
            if (function_exists($pre)) {
                $pre($this);
            }

            if (file_exists($profile)) {
                $status = $this->applyProfile($profile);
            } else {
                try {
                    $status = $this->runFile($schema);
                } catch (MySQLWarningException $e) {
                    throw $e;
                }
            }

            if (function_exists($post)) {
                $post($this);
            }
        }
        if (! isset($status)) {
            if (array_key_exists($patch, Patch::$list)) {
                throw new LogicException('Patch not found');
            } else {
                throw new Exception('No such patch', 1);
            }
        } elseif (! $status) {
            Patch::$list[$patch]->setStatus(Patch::FAILED);
            throw new Exception('Patch application failed', 2);
        } else {
            Patch::$list[$patch]->record();
        }
        $this->currentPatchName = '';
    }

    /**
     * @param $script
     */
    public function runScript($script)
    {
        $file = __DIR__ . "/script/$script.php";

        if (file_exists($file)) {
            require $file;
        }

        if (function_exists($script)) {
            $script($this);
        }

        $this->executed[] = $script;
    }


    private function applyProfile($profileFile)
    {
        // By the time a profile install is requested, the installation should be functional enough to work
        require_once('tiki-setup.php');
        $directory = dirname($profileFile);
        $profile = substr(basename($profileFile), 0, -4);

        $profile = Tiki_Profile::fromFile($directory, $profile);

        $tx = $this->begin();

        $installer = new \Tiki_Profile_Installer();
        $ret = $installer->install($profile);

        $tx->commit();

        return $ret;
    }

    /**
     * @param $file
     * @return bool Did running the schema file succeed without error
     */
    public function runFile($file, $convertFormat = true, $throwOnError = false): bool
    {
        if (! is_file($file) || ! $command = file_get_contents($file)) {
            throw new Exception('Fatal: Cannot open ' . $file);
        }
        $statements = self::splitSqlStatements($command);

        $this->queries['currentFile'] = basename($file);
        $this->queries['files'][] = $file;
        $this->queries['total'] += count($statements);

        $status = true;
        foreach ($statements as $statement) {
            if ($this->useInnoDB && $convertFormat) {
                // Convert all MyISAM statments to InnoDB
                $statement = str_ireplace("MyISAM", "InnoDB", $statement);
            }

            if ($this->query($statement, [], -1, -1, true, $file) === false) {
                $status = false;
                if ($throwOnError) {
                    throw new Exception("Failed running query $statement from file $file");
                }
            } else {
                $this->queries['executed'] += 1;
            }

            $this->queries['currentStmt'] = $statement;
            $this->notify();
        }

        $this->queries['currentFile'] = '';
        $this->queries['currentStmt'] = '';

        return $status;
    }

    /**
     * @param null $query
     * @param array $values
     * @param $numrows
     * @param $offset
     * @param bool $reporterrors
     * @param string $patch
     * @param bool $countQueries
     * @return Tiki\TikiDb\PdoResult or false if the query failed
     */
    public function query($query = null, $values = null, $numrows = -1, $offset = -1, $reporterrors = true, $patch = '', $countQueries = true)
    {
        $error = '';
        $result = $this->queryError($query, $error, $values);

        if (stripos(trim($query), 'SHOW WARNINGS') === 0) {
            return $result;
        }

        $isCI = ! empty($_ENV['TIKI_CATCH_UPDATE_WARNINGS']) || getenv('TIKI_CATCH_UPDATE_WARNINGS');
        $warnings = [];
        $warnResult = self::get()->query('SHOW WARNINGS', reporterrors: \TikiDb::ERR_NONE);

        if ($warnResult) {
            while ($row = $warnResult->fetchRow()) {
                if ($row['Level'] === 'Warning' || $row['Level'] === 'Error') {
                    $code = (int) $row['Code'];
                    // Historical patches are before 2026-04-17. Base schema (empty name) is also historical.
                    $isHistorical = empty($this->currentPatchName) || substr($this->currentPatchName, 0, 8) < '20260417';

                    if (($code === 1681 || $code === 124) && $isHistorical) {
                        continue;
                    }

                    $warnings[] = $row['Level'] . ' ' . $row['Code'] . ': ' . $row['Message'];
                }
            }
        }

        if ($result && empty($error)) {
            if ($isCI && ! empty($warnings)) {
                throw new MySQLWarningException("MySQL Warning(s) caught during upgrade in query:\n$query\nWarnings:\n" . implode("\n", $warnings));
            }

            if (! empty($warnings)) {
                $this->queries['warnings'][] = ['query' => $query, 'warnings' => $warnings];
            }

            if ($countQueries) {
                $this->queries['successful'][] = $query;
            }
            return $result;
        } else {
            if ($isCI && ! empty($warnings)) {
                throw new MySQLWarningException("MySQL Error/Warning(s) caught during upgrade in query:\n$query\nDetails:\n" . implode("\n", $warnings));
            }

            if ($countQueries) {
                $this->queries['failed'][] = [$query, $error, substr(basename($patch), 0, -4)];
            }
            return false;
        }
    }

    /**
     * @throws Exception In case of filesystem access issue
     */
    public function buildPatchList()
    {
        $patches = [];
        foreach (['sql', 'yml', 'php' /* "php" for standalone PHP scripts */] as $extension) {
            $files = glob(__DIR__ . '/schema/*_*.' . $extension); // glob() does not portably support brace expansion, hence the loop
            if ($files === false) {
                throw new Exception('Failed to scan patches');
            }
            foreach ($files as $file) {
                $filename = basename($file);
                $patches[] = substr($filename, 0, -4);
            }
        }
        $patches = array_unique($patches);

        $installed = [];
        if ($this->tableExists('tiki_schema')) {
            $installed = $this->table('tiki_schema')->fetchColumn('patch_name', []);
        }

        if (empty($installed)) {
            // Erase initial error
            $this->queries['failed'] = [];
        }

        Patch::$list = [];
        sort($patches);
        foreach ($patches as $patchName) {
            if (in_array($patchName, $installed)) {
                $status = Patch::ALREADY_APPLIED;
            } else {
                $status = Patch::NOT_APPLIED;
            }
            $patch = new Patch($patchName, $status);
            $patch->optional = substr($patchName, 0, 8) == 'optional'; // Ignore patches starting with "optional". These patches have drawbacks and should be manually run by informed administrators.
            Patch::$list[$patchName] = $patch;
        }
    }


    public function buildScriptList()
    {
        $files = glob(__DIR__ . '/script/*.php');
        if (empty($files)) {
            return;
        }
        foreach ($files as $file) {
            if (basename($file) === "index.php") {
                continue;
            }
            $filename = basename($file);
            $this->scripts[] = substr($filename, 0, -4);
        }
    }

    /**
     * @param $tableName
     * @return bool
     */
    public function tableExists($tableName)
    {
        $list = $this->listTables();
        return in_array($tableName, $list);
    }

    public function isInstalled()
    {
        return $this->tableExists('tiki_preferences');
    }

    /**
     * @return bool
     */
    public function requiresUpdate()
    {
        return count(Patch::getPatches([Patch::NOT_APPLIED])) > 0;
    }

    /**
     * @return string[]
     */
    public function missingPatches()
    {
        $patchDir = TIKI_UPGRADE_SQL_SCHEMA_PATH . '/';
        $patches = Patch::getPatches([Patch::NOT_APPLIED]);
        $patchesFilesNames = [];
        foreach ($patches as $key => $patch) {
            if (file_exists($patchDir . $key . ".sql")) {
                $patchesFilesNames[] = $key . ".sql";
            } elseif (file_exists($patchDir . $key . ".yml")) {
                $patchesFilesNames[] = $key . ".yml";
            } else {
                $patchesFilesNames[] = $key . ".php";
            }
        }
        return $patchesFilesNames;
    }

    public function checkInstallerLocked()
    {
        $iniFile = __DIR__ . '/../db/lock';


        if (! is_readable($iniFile)) {
            return 1;
        }
    }
    private function getBaseImage()
    {
        return (new BaseImageResolver(__DIR__ . '/..'))->resolve();
    }

    /**
     * Use this if the default for a preference changes to preserve the old default behaviour on upgrades
     *
     * @param string $prefName
     * @param mixed $oldDefault
     */
    public function preservePreferenceDefault(string $prefName, mixed $oldDefault): void
    {

        if ($this->tableExists('tiki_preferences')) {
            $tiki_preferences = $this->table('tiki_preferences');
            $hasValue = $tiki_preferences->fetchCount(['name' => $prefName]);

            if (empty($hasValue)) { // old value not in database so was on default value
                $tiki_preferences->insert(['name' => $prefName, 'value' => $oldDefault]);
            }
        }
    }

    public function attach(SplObserver $observable): void
    {
        if (method_exists($observable, 'update')) {
            $this->observers->attach($observable);
        } else {
            throw new Exception('Observable should implement `update` method');
        }
    }

    public function detach(SplObserver $observable): void
    {
        $this->observers->detach($observable);
    }

    public function notify(): void
    {
        foreach ($this->observers as $observer) {
            $observer->update($this);
        }
    }

    /**
     * Compares the charset encoding of the database with the one from tiki_schema, column patch_name (used as reference)
     *
     * If they are different, attempts to update teh default charset and collation from the database to
     * match the one from tiki_schema, as it should be the reference for the encoding of that tiki database.
     * The key case for both charset not to match is when the tiki db was restored to a new db but the encoding
     * of that new db was not set to the right values. That will then cause that new tables won't be created with
     * the right encoding (aligned with the rest of the tiki tables)
     */
    protected function assureDefaultCharSetIsAlignedWithTikiSchema()
    {
        $databaseInfoResult = $this->query(
            'SELECT SCHEMA_NAME, DEFAULT_CHARACTER_SET_NAME, DEFAULT_COLLATION_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = DATABASE()',
            countQueries: false
        );
        if (! $databaseInfoResult || ! $databaseInfo = $databaseInfoResult->fetchRow()) {
            return;
        }

        $tableInfoResult = $this->query(
            'SELECT TABLE_SCHEMA, CHARACTER_SET_NAME, COLLATION_NAME from INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "tiki_schema" AND COLUMN_NAME="patch_name"',
            countQueries: false
        );
        if (! $tableInfoResult || ! $tableInfo = $tableInfoResult->fetchRow()) {
            return;
        }

        if (! $databaseInfo || ! $tableInfo) { // if we cant retrieve the info we can not do anything
            return;
        }

        if (
            $databaseInfo['DEFAULT_CHARACTER_SET_NAME'] === $tableInfo['CHARACTER_SET_NAME']
            && $databaseInfo['DEFAULT_COLLATION_NAME'] === $tableInfo['COLLATION_NAME']
        ) {
            // all OK, charset and collation are aligned
            return;
        }

        // Info is not aligned, forcing to align the default values for the database with tiki_schema
        // Someone may have restored the db without setting the right default values for teh database for instance.
        switch ($tableInfo['CHARACTER_SET_NAME']) {
            case 'utf8':
                $this->query(
                    'ALTER DATABASE `' . $tableInfo['TABLE_SCHEMA'] . '` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci'
                );
                break;
            case 'utf8mb4':
                $this->query(
                    'ALTER DATABASE `' . $tableInfo['TABLE_SCHEMA'] . '` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
                );
                break;
            default:
                // we will only attempt to align for some char sets, other configuration needs to be done manually
                break;
        }
    }

    /**
     * In some cases, databases/tables may have been upgraded from old versions and still using
     * The format Compact or Redundant. This will lead to errors "Row size too large" on some tables
     * like tiki_tracker_fields that have a large number of text/blob columns.
     *
     * This code attempts to update the row format to DYNAMIC if they still use one of the older format.
     * If running a newer format, leave untouched to make it future-proof.
     *
     * @return void
     */
    protected function assureInnoDdTableRowFormatIsDynamicOrBetter(): void
    {
        $tableList = $this->fetchAll(
            'SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE'
            . ' TABLE_SCHEMA = DATABASE()'
            . ' AND ENGINE="InnoDB"'
            . ' AND ROW_FORMAT IN ("Compact", "Redundant")'
        );

        if (empty($tableList)) {
            return;
        }

        foreach ($tableList as $tableListEntry) {
            $this->query(
                'ALTER TABLE `' . $tableListEntry['TABLE_NAME'] . '` ROW_FORMAT=DYNAMIC'
            );
        }
    }
}
