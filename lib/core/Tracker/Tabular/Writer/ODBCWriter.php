<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tracker\Tabular\Writer;

use Tracker\Tabular\Source\ODBCSourceEntry;

class ODBCWriter
{
    private $odbc_manager;

    public function __construct($config)
    {
        $this->odbc_manager = new \Tracker\Tabular\ODBCManager($config);
    }

    public function write(\Tracker\Tabular\Source\SourceInterface $source)
    {
        $schema = $source->getSchema();
        $schema->validate();

        $columns = $schema->getColumns();
        foreach ($source->getEntries() as $entry) {
            $row = [];
            $pk = null;
            $id = null;
            foreach ($columns as $column) {
                $rendered = $entry->render($column, true);
                if (is_array($rendered)) {
                    foreach ($column->getRemoteFields() as $key => $remoteField) {
                        if (isset($rendered[$key]) && ! empty($remoteField)) {
                            $row[$remoteField] = $rendered[$key];
                        }
                    }
                } elseif ($column->getRemoteField()) {
                    $row[$column->getRemoteField()] = $rendered;
                }
                if ($column->isPrimaryKey()) {
                    $pk = $column->getRemoteField();
                    $id = $row[$pk];
                    if ($schema->isPrimaryKeyAutoIncrement()) {
                        unset($row[$pk]);
                    }
                }
            }
            $this->odbc_manager->replace($pk, $id, $row);
        }
    }

    /**
     * Called after trackeritem save event, this method updates remote data source with local changes
     */
    public function sync(\Tracker\Tabular\Schema $schema, int $item_id, array $old_values, array $new_values, &$is_new)
    {
        $schema->validate();
        $columns = $schema->getColumns();

        // prepare the remote entry to replace - send only the following:
        // - changed values
        // - fields that do not store value in Tiki db like ItemsList (they might have changed as well)
        // - schema primary key (needed for remote updates but usually does not change locally, e.g. AutoIncrement)
        $entry = [];
        $fullEntry = [];
        $pk = $schema->getPrimaryKey();
        if ($pk) {
            $pk = $pk->getField();
        }
        foreach ($new_values as $permName => $value) {
            $fullEntry[$permName] = $value;
            if (! isset($old_values[$permName]) || $value != $old_values[$permName] || $permName == $pk) {
                $entry[$permName] = $value;
            } else {
                $field = $schema->getDefinition()->getFieldFromPermname($permName);
                if ($field && $field['type'] == 'l') {
                    $entry[$permName] = $value;
                }
            }
        }

        $row = [];
        $fullRow = [];
        $pk = null;
        $id = null;
        foreach ($columns as $column) {
            $this->renderMultiple($column, $fullEntry[$column->getField()], ['itemId' => $item_id], $fullRow);
            if (! isset($entry[$column->getField()]) && ! $column->isPrimaryKey()) {
                continue;
            }
            $this->renderMultiple($column, $entry[$column->getField()], ['itemId' => $item_id], $row);
            if ($column->isPrimaryKey()) {
                $pk = $column->getRemoteField();
                $id = $row[$pk];
                if ($schema->isPrimaryKeyAutoIncrement()) {
                    unset($row[$pk]);
                    unset($fullRow[$pk]);
                }
            }
        }

        // let the odbc manager create the record remotely even if autoincrement value has been assigned locally
        // we need this to prevent overwrites of remote data when it wasn't synced with local data
        if (empty($old_values)) {
            $id = null;
        }

        if ($pk && ! $id) {
            foreach ($columns as $column) {
                if ($column->isUniqueKey() && isset($fullRow[$column->getRemoteField()])) {
                    // for single mapped remote fields marked as unique, check uniqeness remotely and if not, get next unique value
                    // reason: local data might not be fully synced from remote data at the time new entries are inserted
                    if ($this->odbc_manager->valueExists($column->getRemoteField(), $fullRow[$column->getRemoteField()])) {
                        $fullRow[$column->getRemoteField()] = $this->odbc_manager->nextValue($column->getRemoteFIeld());
                        if (isset($row[$column->getRemoteField()])) {
                            $row[$column->getRemoteField()] = $fullRow[$column->getRemoteField()];
                        }
                    }
                }
            }
        }

        if ($pk) {
            $result = $this->odbc_manager->replace($pk, $id, $row, $fullRow);
        } else {
            $existing = [];
            foreach ($columns as $column) {
                if (isset($old_values[$column->getField()])) {
                    $this->renderMultiple($column, $old_values[$column->getField()], ['itemId' => $item_id], $existing);
                }
            }
            $result = $this->odbc_manager->replaceWithoutPK($existing, $row);
        }
        $is_new = $result['is_new'];

        // map back the remote values to local field values
        $entry = new ODBCSourceEntry($result['entry']);
        $mapped = [];
        foreach ($columns as $column) {
            $permName = $column->getField();
            $info = [];
            $entry->parseInto($info, $column);
            if (isset($info['fields'][$permName]) && ! is_null($info['fields'][$permName])) {
                $mapped[$permName] = $info['fields'][$permName];
            }
        }
        return $mapped;
    }

    public function compareRemote(\Tracker\Tabular\Schema $schema, int $item_id, array $item)
    {
        $schema->validate();
        $columns = $schema->getColumns();

        $row = [];
        $pk = null;
        $id = null;
        foreach ($columns as $column) {
            if (! isset($item[$column->getField()]) && ! $column->isPrimaryKey()) {
                continue;
            }
            $this->renderMultiple($column, $item[$column->getField()], ['itemId' => $item_id], $row);
            if ($column->isPrimaryKey()) {
                $pk = $column->getRemoteField();
                $id = $row[$pk];
                if ($schema->isPrimaryKeyAutoIncrement()) {
                    unset($row[$pk]);
                }
            }
        }

        $result = $this->odbc_manager->fetch($row, $pk, $id);

        // map back the remote values to local field values
        $entry = new ODBCSourceEntry($result);
        $mapped = [];
        foreach ($columns as $column) {
            $permName = $column->getField();
            $info = [];
            $entry->parseInto($info, $column);
            if (isset($info['fields'][$permName]) && ! is_null($info['fields'][$permName])) {
                $mapped[$permName] = $info['fields'][$permName];
            }
        }

        $diff = [];
        foreach ($mapped as $field => $value) {
            if (! isset($item[$field])) {
                continue;
            }
            $local = $item[$field];
            if (is_array($local) && ! is_array($value)) {
                $local = implode(',', $local);
            }
            if ($local == $value) {
                continue;
            }
            $diff[$field] = $value;
        }

        return $diff;
    }

    public function delete($pk, $id)
    {
        return $this->odbc_manager->delete($pk, $id);
    }

    protected function renderMultiple($column, $value, $extra, &$row)
    {
        $extra['allow_multiple'] = true;
        $rendered = $column->render($value, $extra);
        if (is_array($rendered)) {
            foreach ($column->getRemoteFields() as $key => $remoteField) {
                if (isset($rendered[$key])) {
                    $row[$remoteField] = $rendered[$key];
                }
            }
        } else {
            $row[$column->getRemoteField()] = $rendered;
        }
    }
}
