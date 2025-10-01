<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tracker\Tabular\Source;

class APISource implements SourceInterface
{
    public $mapping;
    protected $schema;
    protected $config;
    protected $placeholders;
    protected $item;

    public function __construct(\Tracker\Tabular\Schema $schema, array $api_config, array $placeholders)
    {
        $this->schema = $schema;
        $this->config = $api_config;
        $this->mapping = $api_config['list_mapping'] ?? [];
        if (is_string($this->mapping)) {
            $this->mapping = json_decode($this->mapping, true);
        }
        $this->placeholders = $placeholders;
        $this->item = [];
    }

    public function getEntries()
    {
        $list_url = $this->config['list_url'];
        $params = $this->config['list_parameters'] ?? null;
        $user_specific = null;
        foreach ($this->placeholders as $field => $value) {
            $list_url = str_replace('%' . $field . '%', $value, $list_url);
            if ($params) {
                $params = str_replace('%' . $field . '%', $value, $params);
            }
            foreach ($this->schema->getColumns() as $column) {
                if ($column->getLabel() == $field) {
                    $tracker_field = \TikiLib::lib('trk')->get_field_by_perm_name($column->getField());
                    if ($tracker_field && $tracker_field['type'] == 'u') {
                        $user_specific = $value;
                    }
                }
            }
        }
        $client = new \Services_ApiClient($list_url, false);
        $client->setContextUser($user_specific);
        $client->setFormat($this->schema->getFormat());
        $method = strtolower($this->config['list_method'] ?? 'get');
        if ($params) {
            $result = $client->$method('', $params);
        } else {
            $result = $client->$method();
        }

        $data_path = explode('.', $this->config['list_data_path']);
        foreach ($data_path as $key => $field) {
            unset($data_path[$key]);
            if ($field) {
                if (! isset($result[$field])) {
                    throw new \Exception(tr("Error parsing API results path %0 at field %1.", $this->config['list_data_path'], $field));
                }
                $result = $result[$field];
            } else {
                break;
            }
        }

        if (! is_array($result)) {
            throw new \Exception(tr("Error parsing API response: result is not an array."));
        }

        if ($this->schema->getFormat() == 'ndjson' && $this->mapping) {
            $common_item = [];
            $this->item = [];
            foreach ($this->mapping as $mapping_row) {
                if (! isset($mapping_row['struct']) || ! isset($mapping_row['repeat'])) {
                    throw new \Exception(tr('Error using data mapping structure for NDJSON format: missing struct or repeat keys.'));
                }
            }
            $mapping_level = 0;
            $dependencies = [];
            $max_level = count($this->mapping) - 1;
            while ($result) {
                $mapping_row = $this->mapping[$mapping_level];
                $row = array_shift($result);
                $num_passes = 0;
                do {
                    $mapped_row = true;
                    $common_item = array_merge($common_item, $this->item);
                    $this->item = [];
                    foreach ($mapping_row['struct'] as $remote => $local) {
                        $remote_value = $this->dotParser($row, $remote);
                        $regex = preg_replace("/%[^%]+%/", '(.*)', $local);
                        preg_match_all('/%([^%]+)%/', $local, $m);
                        $local_fields = $m[1];
                        if (preg_match('#' . str_replace('#', '\\#', $regex) . '#', $remote_value, $m)) {
                            foreach ($local_fields as $num => $local_field) {
                                if (str_contains($local_field, '-')) {
                                    list ($local_field, $dependency_field) = explode('-', $local_field);
                                    $local_column = $dependency_column = null;
                                    foreach ($this->schema->getColumns() as $column) {
                                        if ($column->getLabel() == $local_field) {
                                            $local_column = $column;
                                        }
                                        if ($column->getLabel() == $dependency_field) {
                                            $dependency_column = $column;
                                        }
                                    }
                                    $dependencies[spl_object_hash($local_column)] = spl_object_hash($dependency_column);
                                }
                                foreach ($this->schema->getColumns() as $column) {
                                    if ($column->getLabel() == $local_field) {
                                        $this->populateOne($column, $m[$num + 1]);
                                    }
                                }
                            }
                        } else {
                            if ($remote_value != $local) {
                                $mapped_row = false;
                            }
                        }
                    }
                    if (! $mapped_row) {
                        if (++$num_passes > $max_level) {
                            break;
                        }
                        $this->item = [];
                        if ($mapping_level < $max_level) {
                            $mapping_level++;
                        } else {
                            $mapping_level--;
                        }
                        $mapping_row = $this->mapping[$mapping_level];
                    }
                } while (! $mapped_row);
                if ($mapping_level == $max_level) {
                    $data = array_merge($common_item, $this->item);
                    foreach ($dependencies as $field => $dependency) {
                        if (! empty($data[$dependency])) {
                            unset($data[$field]);
                        }
                    }
                    $results[] = $data;
                    yield new JsonSourceEntry($data);
                    $this->item = [];
                }
            }
        } else {
            foreach ($result as $num => $row) {
                foreach ($data_path as $field) {
                    if (! isset($row[$field])) {
                        throw new \Exception(tr("Error parsing API results path %0 at field %1.", $this->config['list_data_path'], $field));
                    }
                    $row = $row[$field];
                }
                if ($data = $this->populateResult($row)) {
                    yield new JsonSourceEntry($data);
                }
            }
        }
    }

    public function getSchema()
    {
        return $this->schema;
    }

    protected function populateResult($row)
    {
        $this->item = [];
        if ($this->mapping) {
            $result = $this->populateFromMapping($row);
        } else {
            $result = $this->populateFromColumns($row);
        }
        if ($result) {
            return $this->item;
        } else {
            return false;
        }
    }

    protected function populateFromMapping($row)
    {
        foreach ($this->mapping as $remote => $local) {
            $remote_value = $this->dotParser($row, $remote);
            if (preg_match('/%([^%]+)%/', $local, $m)) {
                foreach ($this->schema->getColumns() as $column) {
                    if ($column->getLabel() == $m[1]) {
                        $this->populateOne($column, $remote_value);
                    }
                }
            } else {
                // static values not matching means we skip the whole record
                if ($remote_value != $local) {
                    return false;
                }
            }
        }
        return true;
    }

    protected function populateFromColumns($row)
    {
        $this->item = [];
        foreach ($this->schema->getColumns() as $column) {
            if (isset($row[$column->getField()])) {
                $this->populateOne($column, $row[$column->getField()]);
                continue;
            }
            if (isset($row[$column->getLabel()])) {
                $this->populateOne($column, $row[$column->getLabel()]);
                continue;
            }
            if (isset($this->placeholders[$column->getLabel()])) {
                $this->populateOne($column, $this->placeholders[$column->getLabel()]);
                continue;
            }
            if ($column->isPrimaryKey() && isset($row['id'])) {
                $this->populateOne($column, $row['id']);
                continue;
            }
            throw new \Exception(tr('Expected field "%0" not found in record %1.', $column->getField(), $num));
        }
        return true;
    }

    protected function populateOne($column, $value)
    {
        $this->item[spl_object_hash($column)] = $value;
    }

    protected function dotParser($row, $key)
    {
        $value = $row;
        foreach (explode('.', $key) as $part) {
            if (! isset($value[$part])) {
                return null;
            }
            $value = $value[$part];
        }
        return $value;
    }
}
