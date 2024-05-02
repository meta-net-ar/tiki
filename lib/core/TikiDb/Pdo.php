<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Class TikiDb_Pdo_Result
 *
 * Returns result set along with affected rows
 */
class TikiDb_Pdo_Result
{
    /** @var array */
    public $result;
    /** @var int */
    public $numrows;

    /**
     * TikiDb_Pdo_Result constructor.
     * @param $result
     * @param $rowCount
     */
    public function __construct($result, $rowCount)
    {
        $this->result = &$result;
        $this->numrows = is_numeric($rowCount) ? $rowCount : count($this->result);
    }

    /** @return array */
    public function fetchRow()
    {
        return is_array($this->result) ? array_shift($this->result) : 0;
    }

    /** @return int */
    public function numRows()
    {
        return (int) $this->numrows;
    }
}

class TikiDb_Pdo extends TikiDb
{
    /** @var $db PDO */
    private $db;
    /** @var $rowCount int*/
    private $rowCount;

    /**
     * TikiDb_Pdo constructor.
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

    private function doQuery($query, $values = null, $numrows = -1, $offset = -1)
    {
        global $num_queries;
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

        $starttime = $this->startTimer();

        $result = false;
        if ($values) {
            if (@ $pq = $this->db->prepare($query)) {
                if (! is_array($values)) {
                    $values = [$values];
                }
                if (! array_filter(array_keys($values), 'is_string')) {
                    $values = array_values($values);
                }
                $result = $pq->execute($values);
                $this->rowCount = $pq->rowCount();
            }
        } else {
            $result = $this->db->query($query);
            $this->rowCount = is_object($result) && get_class($result) === 'PDOStatement' ? $result->rowCount() : 0;
        }

        $this->stopTimer($starttime);

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
            if (($values && ! $pq->columnCount()) || (! $values && ! $result->columnCount())) {
                return []; // Return empty result set for statements of manipulation
            } elseif (! $values) {
                return $result->fetchAll(PDO::FETCH_ASSOC);
            } else {
                return $pq->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    }

    public function fetchAll($query = null, $values = null, $numrows = -1, $offset = -1, $reporterrors = parent::ERR_DIRECT): array|false
    {
        $result = $this->doQuery($query, $values, $numrows, $offset);
        if (! is_array($result)) {
            $this->handleQueryError($query, $values, $result, $reporterrors);
        }

        return $result;
    }

    public function query($query = null, $values = null, $numrows = -1, $offset = -1, $reporterrors = self::ERR_DIRECT)
    {
        $result = $this->doQuery($query, $values, $numrows, $offset);
        if ($result === false) {
            $this->handleQueryError($query, $values, $result, $reporterrors);
        }
        return new TikiDb_Pdo_Result($result, $this->rowCount);
    }

    public function lastInsertId()
    {
        return $this->db->lastInsertId();
    }
}
