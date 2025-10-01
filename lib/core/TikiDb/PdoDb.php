<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\TikiDb;

use Exception;
use PDO;
use TikiDb;
use TikiLib;
use Tiki\Profiling\DatabaseQueryLog;

class PdoDb extends TikiDb
{
    /**
     * @var array track queries that needs logging
     */
    protected static $queryLog = [];

    /**
     * @var bool control to avoid setting up the shutdown handler multiple times
     */
    protected static $queryLogHandlerInstalled = false;

    /** @var $db PDO */
    private $db;
    /** @var $rowCount int*/
    private $rowCount;

    /**
     * Tiki\TikiDb\PdoDb constructor.
     * @param PDO $db
     */
    public function __construct($db)
    {
        if (! $db) {
            throw new Exception('Invalid db object passed to TikiDB constructor');
        }

        $this->db = $db;
        $this->setServerType($db->getAttribute(PDO::ATTR_DRIVER_NAME));
    }

    public function getHandler()
    {
        return $this->db;
    }

    public function qstr($str)
    {
        if (is_null($str)) {
            return 'NULL';
        }
        return $this->db->quote($str);
    }

    private function doQuery($query, $values, $numrows, $offset, $fetch, array $options)
    {
        global $num_queries, $elapsed_in_db, $base_url, $prefs;
        $num_queries++;

        $numrows = (int)$numrows;
        $offset = (int)$offset;
        if ($query == null) {
            $query = $this->getQuery();
        }

        $this->convertQueryTablePrefixes($query);

        if ($offset != -1 && $numrows != -1) {
            $query .= " LIMIT $numrows OFFSET $offset";
        } elseif ($numrows != -1) {
            $query .= " LIMIT $numrows";
        }

        // change regular expression boundaries from Henry Spencer's implementation to
        // Internation Components for Unicode (ICU) used in mysql 8.0.4 and onwards
        // thanks to https://stackoverflow.com/a/59230861/2459703 for the help
        if (stripos($query, 'REGEXP') !== false) {
            $tikiDbPdoResult = $this->query("SHOW VARIABLES LIKE 'version'");
            $mysqlVersion = $tikiDbPdoResult->fetchRow();
            if (version_compare($mysqlVersion['Value'], '8.0.4') >= 0) {
                if ($values !== null) {
                    $values = str_replace(['[[:<:]]', '[[:>:]]'], '\\b', $values);
                }
                // TODO other exceptions as listed here maybe?
                // https://dev.mysql.com/doc/refman/8.0/en/regexp.html#regexp-compatibility
            }
        }

        if ($values) {
            if (! is_array($values)) {
                $values = [$values];
            }
            if (! array_filter(array_keys($values), 'is_string')) {
                $values = array_values($values);
            }
        }

        $starttime = $this->startTimer();
        $logHandle = DatabaseQueryLog::logStart($query, $values, $options[self::QUERY_OPTION_LOG_GROUP] ?? 'Ungrouped');

        $result = false;
        if ($values) {
            if (@ $pq = $this->db->prepare($query)) {
                $result = $pq->execute($values);
                $this->rowCount = $pq->rowCount();
            }
        } else {
            $result = $this->db->query($query);
            $this->rowCount = is_object($result) && get_class($result) === 'PDOStatement' ? $result->rowCount() : 0;
        }

        DatabaseQueryLog::logEnd($logHandle);
        $queryElapsed = $this->stopTimer($starttime);

        if (isset($prefs['log_sql'], $prefs['log_sql_perf_min']) && $prefs['log_sql'] === 'y' && $queryElapsed > (float)$prefs['log_sql_perf_min']) {
            $tracer = $base_url . '/' . htmlspecialchars($_SERVER['PHP_SELF']);
            $this->pdoLogSQL($query, $queryElapsed, $tracer, $values);
        }

        if ($result === false) {
            if (! $values || ! $pq) { // Query preparation or query failed
                $tmp = $this->db->errorInfo();
            } else { // Prepared query failed to execute
                $tmp = $pq->errorInfo();
                $pq->closeCursor();
            }
            $this->setErrorMessage($tmp[2]);
            $this->setErrorNo($tmp[1]);
            return false;
        } else {
            $this->setErrorMessage("");
            $this->setErrorNo(0);
            if ($fetch) {
                if (($values && ! $pq->columnCount()) || (! $values && ! $result->columnCount())) {
                    return []; // Return empty result set for statements of manipulation
                } elseif (! $values) {
                    return $result->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    return $pq->fetchAll(PDO::FETCH_ASSOC);
                }
            } else {
                if (! $values) {
                    return $result;
                } else {
                    return $pq;
                }
            }
        }
    }

    public function fetchAll($query = null, $values = null, $numrows = -1, $offset = -1, $reporterrors = parent::ERR_DIRECT, array $options = []): array|false
    {
        $result = $this->doQuery($query, $values, $numrows, $offset, true, $options);
        if (! is_array($result)) {
            $this->handleQueryError($query, $values, $result, $reporterrors);
        }

        return $result;
    }

    public function query($query = null, $values = null, $numrows = -1, $offset = -1, $reporterrors = self::ERR_DIRECT, array $options = [])
    {
        $result = $this->doQuery($query, $values, $numrows, $offset, true, $options);
        if ($result === false) {
            $this->handleQueryError($query, $values, $result, $reporterrors);
        }
        return new PdoResult($result, $this->rowCount);
    }

    public function scrollableQuery($query = null, $values = null, $numrows = -1, $offset = -1, $reporterrors = self::ERR_DIRECT, array $options = [])
    {
        $result = $this->doQuery($query, $values, $numrows, $offset, false, $options);
        if ($result === false) {
            $this->handleQueryError($query, $values, $result, $reporterrors);
        }
        return new PdoResult($result, $this->rowCount);
    }

    public function lastInsertId()
    {
        return $this->db->lastInsertId();
    }

    /**
     * Logging SQL queries to database:
     * @param $query The executed SQL query
     * @param $duration The query execution time in ms
     * @param $tracer The filename of the currently executing script
     * @param $params The query params
     */
    public function pdoLogSQL($query, $duration, $tracer, $params = [])
    {
        if (! $query) {
            return;
        }

        self::$queryLog[] = [
            $query,
            $duration,
            json_encode($params),
            $tracer,
            date('Y-m-d H:i:s')
        ];

        // Defer inserting into the DB until tiki.process.shutdown not to cause issues with
        // lastInsertedId reporting SQL log inserted ids instead of the main object lastInsertedId.
        //
        // Also, this limits performance impact during the request, deferring DB writing to after the
        // response is sent back.

        if (self::$queryLogHandlerInstalled) {
            return; // Only register the handler once
        }

        $db = $this->db;
        TikiLib::lib('events')->bind('tiki.process.shutdown', function () use ($db) {
            $logQuery = "INSERT INTO tiki_sql_query_logs (`sql_query`, `query_duration`, `query_params`, `tracer`, `executed_at`) VALUES (?, ?, ?, ?, ?)";
            $logStmt = $db->prepare($logQuery);

            while ($params = array_shift(self::$queryLog)) {
                $logStmt->execute($params);
            }
        });

        self::$queryLogHandlerInstalled = true;
    }
}
