<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tracker\Tabular;

class ODBCManager
{
    public $orig_handler;
    private $config;
    private $errors;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function getSchema()
    {
        $result = [];
        $this->handleErrors();
        $conn = $this->getConnection();
        $columns = odbc_columns($conn, $this->getCatalog(), '%', $this->config['table'], '%');
        while ($row = odbc_fetch_array($columns)) {
            $result[] = $row;
        }
        $this->stopErrorHandler();
        return $result;
    }

    public function fetch($row, $pk = null, $id = null)
    {
        $this->handleErrors();
        $row = $this->fillFieldsFromConfig($row);
        $conn = $this->getConnection();
        if ($pk) {
            $sql = "SELECT * FROM {$this->config['table']} WHERE \"{$pk}\" = ?";
            $rs = odbc_prepare($conn, $sql);
            odbc_execute($rs, [$id]);
            $result = odbc_fetch_array($rs);
        } else {
            $sql = "SELECT * FROM {$this->config['table']} WHERE " . implode(' AND ', array_map(function ($k, $v) {
                return empty($v) ? "\"{$k} IS NULL\"" : "\"{$k}\" = ?";
            }, array_keys($row), $row));
            $rs = odbc_prepare($conn, $sql);
            $params = array_filter(array_values($row));
            odbc_execute($rs, $params);
            $result = odbc_fetch_array($rs);
        }
        $result = $this->reverseMapFieldsFromConfig($result);
        $this->stopErrorHandler();
        return $result;
    }

    public function iterate($fields, $modifiedField = null, $lastImport = null)
    {
        $this->handleErrors();
        $conn = $this->getConnection();
        $select = implode('", "', $fields);
        $sql = "SELECT \"{$select}\" FROM {$this->config['table']} WHERE 1=1";
        $bind = [];
        if ($modifiedField && $lastImport) {
            $sql .= " AND \"{$modifiedField}\" >= ?";
            $bind[] = $lastImport;
        }
        if (! empty($this->config['permanent_values'])) {
            foreach ($this->config['permanent_values'] as $field => $value) {
                $sql .= " AND \"$field\" = ?";
                $bind[] = $value;
            }
        }
        if ($bind) {
            $rs = odbc_prepare($conn, $sql);
            odbc_execute($rs, $bind);
        } else {
            $rs = odbc_exec($conn, $sql);
        }
        while ($row = odbc_fetch_array($rs)) {
            $row = $this->reverseMapFieldsFromConfig($row);
            yield $row;
        }
        $this->stopErrorHandler();
    }

    public function replace($pk, $id, $row, $fullRow = null)
    {
        $this->handleErrors();
        $conn = $this->getConnection();
        if ($id) {
            $sql = "SELECT \"{$pk}\" FROM {$this->config['table']} WHERE \"{$pk}\" = ?";
            $rs = odbc_prepare($conn, $sql);
            odbc_execute($rs, [$id]);
            $exists = odbc_num_rows($rs) > 0;
        } else {
            $exists = false;
            $id = null;
        }
        if ($exists) {
            $row = $this->fillFieldsFromConfig($row);
            foreach (array_chunk($row, 50, true) as $chunk) {
                $sql = "UPDATE {$this->config['table']} SET " . implode(', ', array_map(function ($k) {
                    return "\"{$k}\" = ?";
                }, array_keys($chunk))) . " WHERE \"{$pk}\" = ?";
                $rs = odbc_prepare($conn, $sql);
                $params = array_map(function ($v) {
                    return empty($v) && $v !== '0' ? null : $v;
                }, array_values($chunk));
                $params[] = $id;
                odbc_execute($rs, $params);
                if (odbc_error()) {
                    $this->errors[] = tr("Error updating remote item: %0", odbc_errormsg());
                }
            }
            $sql = "SELECT * FROM {$this->config['table']} WHERE \"{$pk}\" = ?";
            $rs = odbc_prepare($conn, $sql);
            odbc_execute($rs, [$id]);
            $result = odbc_fetch_array($rs);
            $result = ['is_new' => false, 'entry' => $result];
        } else {
            if ($fullRow) {
                $row = $fullRow;
            }
            $row = $this->fillFieldsFromConfig($row);
            $row = array_filter($row, function ($val) {
                if (is_bool($val) || is_int($val) || is_float($val)) {
                    return true;
                }
                if (! empty($val) || $val === "0") {
                    return true;
                }
                return false;
            });
            $sql = "INSERT INTO {$this->config['table']}(\"" . implode('", "', array_keys($row)) . "\") VALUES (" . implode(", ", array_fill(0, count(array_keys($row)), '?')) . ")";
            $rs = odbc_prepare($conn, $sql);
            odbc_execute($rs, array_values($row));
            if (odbc_error()) {
                $this->errors[] = tr("Error inserting remote item: %0", odbc_errormsg());
            }
            $sql = "SELECT * FROM {$this->config['table']} WHERE \"{$pk}\" = @@IDENTITY";
            $rs = odbc_prepare($conn, $sql);
            odbc_execute($rs, []);
            $result = odbc_fetch_array($rs);
            $result = ['is_new' => true, 'entry' => $result];
        }
        $result['entry'] = $this->reverseMapFieldsFromConfig($result['entry']);
        $this->stopErrorHandler();
        return $result;
    }

    public function replaceWithoutPK($existing, $row)
    {
        if (empty($existing)) {
            return $row;
        }
        $this->handleErrors();
        $row = $this->fillFieldsFromConfig($row);
        $conn = $this->getConnection();
        foreach (array_chunk($row, 50, true) as $chunk) {
            $sql = "UPDATE {$this->config['table']} SET " . implode(', ', array_map(function ($k) {
                return "\"{$k}\" = ?";
            }, array_keys($chunk))) . " WHERE " . implode(' AND ', array_map(function ($k, $v) {
                return empty($v) ? "\"{$k} IS NULL\"" : "\"{$k}\" = ?";
            }, array_keys($existing), $existing));
            $rs = odbc_prepare($conn, $sql);
            $params = array_map(function ($v) {
                return empty($v) && $v !== '0' ? null : $v;
            }, array_values($chunk));
            $params = array_merge($params, array_filter(array_values($existing)));
            odbc_execute($rs, $params);
            if (odbc_error()) {
                $this->errors[] = tr("Error updating remote item: %0", odbc_errormsg());
            }
        }
        foreach ($row as $k => $v) {
            $existing[$k] = $v;
        }
        $sql = "SELECT * FROM {$this->config['table']} WHERE " . implode(' AND ', array_map(function ($k, $v) {
            return empty($v) ? "\"{$k} IS NULL\"" : "\"{$k}\" = ?";
        }, array_keys($existing), $existing));
        $rs = odbc_prepare($conn, $sql);
        $params = array_filter(array_values($existing));
        odbc_execute($rs, $params);
        $result = odbc_fetch_array($rs);
        $result = $this->reverseMapFieldsFromConfig($result);
        $result = ['is_new' => false, 'entry' => $result];
        $this->stopErrorHandler();
        return $result;
    }

    public function delete($pk, $id)
    {
        $this->handleErrors();
        $conn = $this->getConnection();
        $sql = "DELETE FROM {$this->config['table']} WHERE \"{$pk}\" = ?";
        $rs = odbc_prepare($conn, $sql);
        odbc_execute($rs, [$id]);
        if (odbc_error()) {
            $this->errors[] = tr("Error deleting remote item: %0", odbc_errormsg());
        }
        $this->stopErrorHandler();
    }

    public function valueExists($field, $value)
    {
        $this->handleErrors();
        $conn = $this->getConnection();
        $sql = "SELECT \"{$field}\" FROM {$this->config['table']} WHERE \"{$field}\" = ?";
        $rs = odbc_prepare($conn, $sql);
        odbc_execute($rs, [$value]);
        $exists = odbc_num_rows($rs) > 0;
        $this->stopErrorHandler();
        return $exists;
    }

    public function nextValue($field)
    {
        $this->handleErrors();
        $conn = $this->getConnection();
        $sql = "SELECT MAX(CAST(\"$field\" AS INT)) as last from {$this->config['table']} WHERE ISNUMERIC(\"$field\") = 1";
        $rs = odbc_prepare($conn, $sql);
        odbc_execute($rs, []);
        $result = odbc_fetch_array($rs);
        $this->stopErrorHandler();
        if (empty($result['last'])) {
            return 1;
        } else {
            return $result['last'] + 1;
        }
    }

    private function getConnection()
    {
        $conn = odbc_connect($this->config['dsn'], $this->config['user'], $this->config['password']);
        if (stristr($this->config['dsn'], 'mysql') || stristr($this->config['dsn'], 'mariadb')) {
            odbc_exec($conn, "SET sql_mode = 'ANSI_QUOTES'");
        }
        return $conn;
    }

    private function getCatalog()
    {
        if (preg_match('/Database=(.*);/i', $this->config['dsn'], $m)) {
            return $m[1];
        } else {
            return '';
        }
    }

    private function handleErrors()
    {
        $this->orig_handler = set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            $this->errors[] = $errstr;
        });
    }

    private function stopErrorHandler()
    {
        set_error_handler($this->orig_handler);
        if ($this->errors) {
            throw new \Exception(implode(' ', $this->errors));
        }
    }

    private function fillFieldsFromConfig(array $row): array
    {
        if (! empty($this->config['permanent_values'])) {
            foreach ($this->config['permanent_values'] as $field => $value) {
                $row[$field] = $value;
            }
        }
        if (! empty($this->config['value_mappings'])) {
            foreach ($this->config['value_mappings'] as $field => $mapping) {
                if (! isset($row[$field])) {
                    continue;
                }
                foreach ($mapping as $remote => $local) {
                    if ($row[$field] === $local) {
                        $row[$field] = $remote;
                        break;
                    }
                }
            }
        }
        return $row;
    }

    private function reverseMapFieldsFromConfig(array $row): array
    {
        if (! empty($this->config['value_mappings'])) {
            foreach ($this->config['value_mappings'] as $field => $mapping) {
                if (! isset($row[$field])) {
                    continue;
                }
                foreach ($mapping as $remote => $local) {
                    if ($row[$field] === $remote) {
                        $row[$field] = $local;
                        break;
                    }
                }
            }
        }
        return $row;
    }
}
