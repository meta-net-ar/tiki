<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tracker\Tabular\Writer;

class APIWriter
{
    private $config;

    public function __construct($config, $tabular_config)
    {
        $this->config = $config;
        $this->config['format'] = $tabular_config['format'];
    }

    public function write(\Tracker\Tabular\Source\SourceInterface $source, $action = null)
    {
        $schema = $source->getSchema();
        $schema = $schema->getPlainOutputSchema();
        $schema->validate();

        $columns = $schema->getColumns();

        $succeeded = $failed = $skipped = 0;
        $errors = [];

        $owner_columns = [];
        foreach ($schema->getDefinition()->getItemOwnerFields() as $ownerField) {
            $ownerField = $schema->getDefinition()->getField($ownerField);
            foreach ($columns as $column) {
                if ($ownerField && $column->getField() == $ownerField['permName']) {
                    $owner_columns[] = $column;
                }
            }
        }

        foreach ($source->getEntries() as $entry) {
            $row = [];

            $id = null;
            foreach ($columns as $column) {
                $row[$column->getLabel()] = $entry->render($column, false);
                if ($column->isPrimaryKey()) {
                    $id = $row[$column->getLabel()];
                }
            }

            foreach (explode('.', $this->config['modify_data_path']) as $field) {
                if ($field) {
                    $row = [$field => $row];
                }
            }

            $user = null;
            foreach ($owner_columns as $column) {
                $owners = $entry->raw($column);
                $owners = \TikiLib::lib('trk')->parse_user_field($owners);
                if ($owners) {
                    $user = $owners[0];
                    break;
                }
            }

            try {
                if ($action === 'delete') {
                    // DELETE endpoint
                    if (! empty($this->config['delete_url'])) {
                        $url = str_replace('#id', $id, $this->config['delete_url']);
                        $method = strtolower($this->config['delete_method'] ?? 'delete');
                        $formatted_row = $this->formatRow(@$this->config['delete_format'], $columns, $row);
                        $result = $this->sendApiRequest($url, $method, $formatted_row, $user);
                    } else {
                        $skipped++;
                        continue;
                    }
                } elseif ($id) {
                    // UPDATE endpoint
                    if (! empty($this->config['update_limit'])) {
                        parse_str($this->config['update_limit'], $params);
                        foreach ($params as $field => $value) {
                            if (! empty($value)) {
                                if (isset($row[$field]) && $row[$field] == $value) {
                                    continue;
                                }
                            } else {
                                if (empty($row[$field])) {
                                    continue;
                                }
                            }
                            $skipped++;
                            continue 2;
                        }
                    }
                    if (! empty($this->config['update_url'])) {
                        $url = str_replace('#id', $id, $this->config['update_url']);
                        $method = strtolower($this->config['update_method'] ?? 'patch');
                        $formatted_row = $this->formatRow(@$this->config['update_format'], $columns, $row);
                        $result = $this->sendApiRequest($url, $method, $formatted_row, $user);
                    } else {
                        $skipped++;
                        continue;
                    }
                } else {
                    // CREATE endpoint
                    if (! empty($this->config['create_url'])) {
                        $url = $this->config['create_url'];
                        $method = strtolower($this->config['create_method'] ?? 'post');
                        $formatted_row = $this->formatRow(@$this->config['create_format'], $columns, $row);
                        $result = $this->sendApiRequest($url, $method, $formatted_row, $user);
                    } else {
                        $skipped++;
                        continue;
                    }
                }
            } catch (\Services_Exception $e) {
                if (! in_array($e->getMessage(), $errors)) {
                    $errors[] = $e->getMessage();
                }
                $result = false;
            }

            if ($result && ! $id && method_exists($entry, 'backfillPK')) {
                foreach (explode('.', $this->config['modify_data_path']) as $field) {
                    if ($field !== '' && $result[$field]) {
                        $result = $result[$field];
                    }
                }
                $pk = null;
                foreach ($columns as $column) {
                    if ($column->isPrimaryKey()) {
                        $pk = $column->getField();
                        $id = $result[$column->getLabel()] ?? null;
                    }
                }
                if ($id && $pk) {
                    $entry->backfillPK($pk, $id);
                }
            }

            if ($result || is_array($result)) {
                $succeeded++;
            } else {
                $failed++;
            }
        }
        return compact('succeeded', 'failed', 'skipped', 'errors');
    }

    public function writeComment($comment, \Tracker\Tabular\Source\TrackerItemSource $source, $action = null)
    {
        $schema = $source->getSchema();
        $schema = $schema->getPlainOutputSchema();
        $schema->validate();

        $columns = $schema->getColumns();

        $owner_columns = [];
        foreach ($schema->getDefinition()->getItemOwnerFields() as $ownerField) {
            $ownerField = $schema->getDefinition()->getField($ownerField);
            foreach ($columns as $column) {
                if ($ownerField && $column->getField() == $ownerField['permName']) {
                    $owner_columns[] = $column;
                }
            }
        }

        $entry = $source->getEntries()->current();

        $row = [];
        foreach ($columns as $column) {
            $row[$column->getLabel()] = $entry->render($column, false);
        }

        $user = null;
        foreach ($owner_columns as $column) {
            $owners = $entry->raw($column);
            $owners = \TikiLib::lib('trk')->parse_user_field($owners);
            if ($owners) {
                $user = $owners[0];
                break;
            }
        }

        try {
            if ($action === 'delete') {
                // DELETE endpoint
                if (! empty($this->config['comment_delete_url'])) {
                    $client = new \Services_ApiClient($this->config['comment_delete_url'], false);
                    $client->setContextUser($user);
                    $method = strtolower($this->config['comment_delete_method'] ?? 'delete');
                    $formatted_parent = $this->formatRow(@$this->config['delete_format'], $columns, $row);
                    if (is_string($formatted_parent) && @json_decode($formatted_parent) !== null) {
                        $formatted_parent = json_decode($formatted_parent);
                    }
                    $comment['parent'] = $formatted_parent;
                    $client->$method('', json_encode($comment), 'application/json');
                }
            } elseif (! empty($comment['comment'])) {
                // UPDATE endpoint
                if (! empty($this->config['comment_update_url'])) {
                    $client = new \Services_ApiClient($this->config['comment_update_url'], false);
                    $client->setContextUser($user);
                    $method = strtolower($this->config['comment_update_method'] ?? 'patch');
                    $formatted_parent = $this->formatRow(@$this->config['update_format'], $columns, $row);
                    if (is_string($formatted_parent) && @json_decode($formatted_parent) !== null) {
                        $formatted_parent = json_decode($formatted_parent);
                    }
                    $comment['parent'] = $formatted_parent;
                    $client->$method('', json_encode($comment), 'application/json');
                }
            } else {
                // CREATE endpoint
                if (! empty($this->config['comment_create_url'])) {
                    $client = new \Services_ApiClient($this->config['comment_create_url'], false);
                    $client->setContextUser($user);
                    $method = strtolower($this->config['comment_create_method'] ?? 'post');
                    $formatted_parent = $this->formatRow(@$this->config['create_format'], $columns, $row);
                    if (is_string($formatted_parent) && @json_decode($formatted_parent) !== null) {
                        $formatted_parent = json_decode($formatted_parent);
                    }
                    $comment['parent'] = $formatted_parent;
                    $client->$method('', json_encode($comment), 'application/json');
                }
            }
            $result = true;
        } catch (\Services_Exception $e) {
            $result = false;
        }

        return $result;
    }

    private function sendApiRequest($url, $method, $formatted_row, $user)
    {
        global $url_host;
        $client = new \Services_ApiClient($url, false);
        $client->setContextUser($user);
        if (is_string($formatted_row) && @json_decode($formatted_row) !== null) {
            $content_type = 'application/json';
        } elseif (is_array($formatted_row) && $this->config['format'] == 'json') {
            $formatted_row = json_encode($formatted_row);
            $content_type = 'application/json';
        } else {
            $content_type = null;
        }
        if (preg_match('#api/tabulars/\d+/(import|delete)$#', $url)) {
            if (empty($this->config['format'])) {
                $content_type = 'text/csv';
            }
            return $client->$method('', ['tiki_skip_sync_url' => $url_host], null, [
                'file' => [
                    'filename' => 'import.' . ($content_type == 'text/csv' ? 'csv' : 'json'),
                    'filetype' => $content_type,
                    'content' => $formatted_row
                ],
            ]);
        } else {
            return $client->$method('', $formatted_row, $content_type);
        }
    }

    private function formatRow($format, $columns, $row)
    {
        if (! empty($format)) {
            $formatted_row = $format;
            if (@json_decode($format) !== null) {
                foreach ($columns as $column) {
                    $formatted_row = str_replace('%' . $column->getLabel() . '%', preg_replace(["/\r/", "/\n/"], ["", "\\n"], addslashes($row[$column->getLabel()])), $formatted_row);
                }
            } else {
                foreach ($columns as $column) {
                    $formatted_row = str_replace('%' . $column->getLabel() . '%', $row[$column->getLabel()], $formatted_row);
                }
            }
        } else {
            $formatted_row = $row;
        }
        return $formatted_row;
    }
}
