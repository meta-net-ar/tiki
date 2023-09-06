<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Manticore;

use Feedback;
use PDO;
use PDOException;

class PdoClient
{
    protected const QUERY_RETRIES = 1;

    protected $dsn;
    protected $port;
    protected $pdo;

    public function __construct($dsn, $port = 9306)
    {
        $this->dsn = $dsn;
        $this->port = $port ?? 9306;
        $this->connect();
    }

    public static function distributedIndexName()
    {
        global $prefs;

        if (empty($prefs['federated_manticore_index_prefix'])) {
            $prefix = 'tiki_';
        } else {
            $prefix = $prefs['federated_manticore_index_prefix'];
        }

        return $prefix . 'distributed';
    }

    public function getStatus()
    {
        $status = ['status' => 0];
        $result = $this->pdo->query('SHOW STATUS');
        while ($row = $result->fetch()) {
            $status[$row['Counter']] = $row['Value'];
        }
        return $status;
    }

    public function getVersion()
    {
        $status = $this->getStatus();
        return $status['version'] ?? 0;
    }

    public function getIndexStatus($index = '')
    {
        try {
            $stmt = $this->pdo->query("SHOW INDEX $index STATUS");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getIndicesByPrefix($prefix, $type = 'rt')
    {
        $results = [];
        $stmt = $this->pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$prefix . '%']);
        foreach ($stmt->fetchAll() as $row) {
            if (! empty($type) && $row['Type'] != $type) {
                continue;
            }
            $results[] = $row['Index'];
        }
        return $results;
    }

    public function createIndex($index, $definition, $settings = [], $silent = false)
    {
        $cols = [];
        foreach ($definition as $field => $opts) {
            $def = '`' . $field . '` ' . $opts['type'];
            if (! empty($opts['options'])) {
                $def .= ' ' . implode(' ', $opts['options']);
            }
            $cols[] = $def;
        }
        $sql = "CREATE TABLE $index (" . implode(',', $cols) . ")";
        foreach ($settings as $key => $val) {
            $sql .= ' ' . $key . '=' . "'" . $val . "'";
        }
        $stmt = $this->pdo->prepare($sql);
        $this->executeWithRetry($stmt);
    }

    public function recreateDistributedIndex(array $names)
    {
        $this->deleteIndex(self::distributedIndexName());

        $list = [];
        foreach ($names as $key => $name) {
            $def = $this->parseDistributedIndexDefinition($name);
            $list[] = $def['type'] . '=?';
            $names[$key] = preg_replace('/\|\d+:/', ':', $name);
        }

        $stmt = $this->pdo->prepare("CREATE TABLE " . self::distributedIndexName() . " type='distributed' " . join(' ', $list));
        $this->executeWithRetry($stmt, array_values($names));
    }

    public function parseDistributedIndexDefinition($name)
    {
        if (strstr($name, ':')) {
            $parts = explode(':', $name);
            $host = trim($parts[0]);
            if (count($parts) > 2) {
                $ports = trim($parts[1]);
                $ports = explode('|', $ports);
            } else {
                $ports = [];
            }
            $index = array_pop($parts);
            if (count($ports) === 1) {
                // table definition might come from DESC distributed table syntax which includes only one port, get the other one from extwiki table
                $extwikis = \TikiLib::lib('admin')->list_extwiki(0, -1, 'extwikiId', '');
                foreach ($extwikis['data'] as $extwiki) {
                    if (preg_match('/' . $host . ':' . $ports[0] . '\|(\d+):/', $extwiki['indexname'], $m)) {
                        $ports[1] = $m[1];
                    }
                }
            }
            return [
                'type' => 'agent',
                'host' => $host,
                'port_agent' => $ports[0] ?? null,
                'port_sql' => $ports[1] ?? null,
                'index' => $index,
            ];
        } else {
            return [
                'type' => 'local',
                'index' => $name,
            ];
        }
    }

    public function possibleFacetFields($table)
    {
        if ($table == self::distributedIndexName()) {
            $stmt = $this->pdo->query("DESC $table");
            $result = $stmt->fetchAll();
            $tables = [];
            foreach ($result as $row) {
                $tables[] = $row['Agent'];
            }
        } else {
            $tables[] = $table;
        }
        $fields = [];
        foreach ($tables as $table) {
            $def = $this->parseDistributedIndexDefinition($table);
            if ($def['type'] == 'agent') {
                // remote agent definition
                $client = new PdoClient($def['host'], $def['port_sql']);
            } else {
                // local index
                $client = $this;
            }
            $fields[$table] = $client->describe($def['index']);
        }
        $common = [];
        $result = array_shift($fields);
        if (! $result) {
            $result = [];
        }
        foreach ($result as $field => $opts) {
            foreach ($fields as $table => $tableFields) {
                if (! isset($tableFields[$field])) {
                    continue 2;
                }
                if ($opts['types'] != $tableFields[$field]['types']) {
                    continue 2;
                }
                if (strstr($table, ':') && ($field == 'deep_categories' || $field == 'categories')) {
                    continue 2;
                }
            }
            $common[] = $field;
        }
        return $common;
    }

    public function deleteIndex($index)
    {
        try {
            $stmt = $this->pdo->query("DESC " . self::distributedIndexName());
            $result = $stmt->fetchAll();
            foreach ($result as $row) {
                $def = $this->parseDistributedIndexDefinition($row['Agent']);
                if ($def['type'] == 'local' && $def['index'] == $index) {
                    Feedback::error(tr("Failed deleting Manticore index %0 as it is currently used in a distributed index.", $index));
                    return false;
                }
            }
        } catch (PDOException $e) {
            // no distributed index, continue normally
        }
        try {
            $stmt = $this->pdo->prepare("DROP TABLE IF EXISTS $index");
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            Feedback::error(tr("Failed deleting Manticore index %0: ", $index) . $e->getMessage());
            return false;
        }
    }

    public function describe($index)
    {
        try {
            $stmt = $this->pdo->query("DESC $index");
            $result = $stmt->fetchAll();
        } catch (PDOException $e) {
            // describe might be used to check if index exists, so suppress not found error here
            return [];
        }
        $mapping = [];
        foreach ($result as $row) {
            if (! isset($mapping[$row['Field']])) {
                $mapping[$row['Field']] = [
                    'types' => [],
                    'options' => [],
                ];
            }
            $mapping[$row['Field']]['types'][] = $row['Type'];
            $mapping[$row['Field']]['options'] = array_merge($mapping[$row['Field']]['options'], explode(' ', $row['Properties']));
        }
        return $mapping;
    }

    public function alter($index, $operation, $field, $type)
    {
        if ($operation == 'drop') {
            $sql = "ALTER TABLE $index DROP COLUMN `$field`";
        } else {
            $sql = "ALTER TABLE $index ADD COLUMN `$field` " . $type['type'];
            if (! empty($type['options'])) {
                $sql .= ' ' . implode(' ', $type['options']);
            }
        }
        $stmt = $this->pdo->prepare($sql);
        $this->executeWithRetry($stmt);
    }

    public function index($index, array $data)
    {
        $array_fields = [];
        $array_values = [];
        foreach ($data as $key => $val) {
            if (is_array($val)) {
                $array_fields[] = $key;
                $array_values[] = '(' . implode(',', $val) . ')';
                unset($data[$key]);
            }
        }
        $keys = implode(', ', array_keys($data));
        if ($keys && $array_fields) {
            $keys .= ', ' . implode(', ', $array_fields);
        }
        $values = implode(',', array_fill(0, count($data), '?'));
        if ($values && $array_values) {
            $values .= ', ' . implode(', ', $array_values);
        }
        $stmt = $this->pdo->prepare("INSERT INTO $index (" . $keys . ') VALUES (' . $values . ')');
        $this->executeWithRetry($stmt, array_values($data));
    }

    public function unindex($index, $type, $id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM $index WHERE object_type = :object_type AND object_id = :object_id");
        $stmt->execute(['object_type' => $type, 'object_id' => $id]);
    }

    public function document($index, $type, $id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM $index WHERE object_type = :object_type AND object_id = :object_id");
        $stmt->execute(['object_type' => $type, 'object_id' => $id]);
        return new ResultSet($stmt->fetch());
    }

    public function optimize($index)
    {
        $stmt = $this->pdo->prepare("OPTIMIZE INDEX $index");
        $stmt->execute();
    }

    public function percolate($index, $document)
    {
        try {
            $stmt = $this->pdo->prepare("CALL PQ(?, ?, 1 as query)");
            $stmt->execute([$index . 'pq', json_encode($document)]);
            $results = $stmt->fetchAll();
            return array_map(function ($item) {
                return $item['tags'];
            }, $results);
        } catch (PDOException $e) {
            throw new Exception($e);
        }
    }

    public function unstoreQuery($index, $name)
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$index}pq WHERE tags = :tags");
        $stmt->execute(['tags' => $name]);
    }

    public function fetchAll($query)
    {
        $stmt = $this->pdo->query($query);
        return $stmt->fetchAll();
    }

    public function fetchAllRowsets($query, $retry = true)
    {
        $stmt = $this->pdo->query($query);
        $result = [$stmt->fetchAll()];
        while ($stmt->nextRowset()) {
            $result[] = $stmt->fetchAll();
        }
        if ($retry) {
            $stmt = $this->pdo->query('show warnings');
            foreach ($stmt->fetchAll() as $row) {
                if (! empty($row['Message']) && preg_match('/agent.*unknown local table/', $row['Message'])) {
                    // remote agent rebuild has invalidated this distributed index, need recreate the distributed index with fresh index table names
                    \TikiLib::lib('federatedsearch')->recreateDistributedIndex($this);
                    return $this->fetchAllRowsets($query, false);
                }
            }
        }
        return $result;
    }

    public function quote($string)
    {
        return $this->pdo->quote($string);
    }

    protected function connect()
    {
        $dsn = rtrim($this->dsn, '/');
        $parsed = parse_url($dsn);
        if ($parsed === false) {
            throw new Exception(tr("Malformed Manticore connection url: %0", $this->dsn));
        }
        if (empty($parsed['host']) && ! empty($parsed['path'])) {
            $parsed['host'] = $parsed['path'];
        }

        $dsn = "mysql:host=" . $parsed['host'] . ";port=" . $this->port;

        try {
            $this->pdo = new PDO($dsn);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception(tr("Error connecting to Manticore service: %0", $e->getMessage()));
        }
    }

    protected function executeWithRetry($stmt, $params = [], $tries = 0)
    {
        try {
            $stmt->execute($params);
        } catch (PDOException $e) {
            if (strstr($e->getMessage(), "server has gone away")) {
                $this->connect();
                if ($tries < self::QUERY_RETRIES) {
                    $this->executeWithRetry($stmt, $params, $tries + 1);
                }
            } else {
                throw new Exception($e->getMessage());
            }
        }
    }
}
