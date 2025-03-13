<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tracker\Tabular\Writer;

class TrackerWriter
{
    public function sendHeaders()
    {
    }

    public function write(\Tracker\Tabular\Source\SourceInterface $source)
    {
        $utilities = new \Services_Tracker_Utilities();
        $schema = $source->getSchema();
        $bulkImport = $schema->useBulkImport();

        if ($bulkImport) {
            global $prefs;
            $prefs['categories_cache_refresh_on_object_cat'] = 'n';
        }

        $iterate = function ($callback) use ($source, $schema, $bulkImport) {
            $columns = $schema->getColumns();

            $tx = \TikiDb::get()->begin();

            $lookup = $this->getItemIdLookup($schema);

            $result = [];

            /** @var \Tracker\Tabular\Source\CsvSourceEntry $entry */
            foreach ($source->getEntries() as $line => $entry) {
                $info = [
                    'itemId' => false,
                    'fields' => [],
                    'skip_sync' => $source instanceof \Tracker\Tabular\Source\ODBCSource,
                    'validate' => $source instanceof \Tracker\Tabular\Source\ODBCSource ? false : true, // ODBC sync needs saving no matter of validation errors
                ];

                foreach ($columns as $column) {
                    $entry->parseInto($info, $column);
                }

                $info['itemId'] = $lookup($info);

                if (! $schema->canImportUpdate() && $info['itemId']) {
                    continue;
                }

                if ($schema->ignoreImportBlanks()) {
                    $info['fields'] = array_filter($info['fields']);
                }

                if ($bulkImport) {
                    $info['bulk_import'] = true;
                }

                $result[] = $callback($line, $info, $columns);
            }

            $tx->commit();

            if (! $result) {
                return $result;
            }

            $resultError = array_column($result, 'error', 'line');
            $resultUpdated = array_column($result, 'update', 'line');
            $resultCreated = array_column($result, 'create', 'line');
            $result = array_merge($resultUpdated, $resultCreated);

            foreach ($resultError as $line => $error) {
                \Feedback::error(tr('Error importing record %0: %1', $line + 1, $error));
            }

            $feedback = [];
            if (! empty($resultCreated)) {
                $feedback[] = count(array_filter($resultCreated)) . ' ' . tr('new tracker(s) item(s) created');
            }
            if (! empty($resultUpdated)) {
                $feedback[] = count(array_filter($resultUpdated)) . ' ' . tr('tracker(s) item(s) updated');
            }
            if (! empty($feedback)) {
                \Feedback::success(implode('<br>', $feedback));
            }

            return $result;
        };

        if ($schema->isImportTransaction()) {
            $errors = $iterate(function ($line, $info, $columns) use ($utilities, $schema) {
                static $ids = [];
                if (! empty($info['itemId']) && in_array($info['itemId'], $ids)) {
                    return [tr('Line %0:', $line + 1) . ' ' . tr('duplicate entry')];
                }
                foreach ($columns as $column) {
                    if ($column->isUniqueKey()) {
                        $table = \TikiDb::get()->table('tiki_tracker_item_fields');
                        $definition = $schema->getDefinition();
                        $f = $definition->getFieldFromPermName($column->getField());
                        $fieldId = $f['fieldId'];
                        $exists = $table->fetchOne('itemId', [
                            'fieldId' => $fieldId,
                            'value' => $info['fields'][$column->getField()],
                        ]);
                        if ($exists) {
                            return [tr('Line %0:', $line + 1) . ' ' . tr('duplicate entry for unique column %0', $column->getLabel())];
                        }
                    }
                }
                $ids[] = $info['itemId'];
                return array_map(
                    function ($error) use ($line) {
                        return tr('Line %0:', $line + 1) . ' ' . $error;
                    },
                    $utilities->validateItem($schema->getDefinition(), $info)
                );
            });

            if (count($errors) > 0) {
                \Feedback::error([
                    'title' => tr('Import file contains errors. Please review and fix before importing.'),
                    'mes' => $errors
                ]);
                return false;
            }
        }

        $definition = $schema->getDefinition();

        $iterate(function ($line, $info, $columns) use ($utilities, $definition, $schema) {
            try {
                if (! isset($info['status'])) {
                    $info['status'] = '';
                }
                if ($info['itemId']) {
                    if ($schema->isSkipUnmodified()) {
                        $currentItem = $utilities->getItem($definition->getConfiguration('trackerId'), $info['itemId']);
                        if (isset($info['bulk_import'])) {
                            $currentItem['bulk_import'] = $info['bulk_import'];
                        }

                        $diff = array_diff_assoc(
                            array_filter($info, function ($item) {
                                return is_string($item);
                            }),
                            array_filter($currentItem, function ($item) {
                                return is_string($item);
                            })
                        );
                        if (! $diff) {
                            $diff = array_diff_assoc($info['fields'], $currentItem['fields']);
                            if (! $diff) {
                                return true;
                            }
                        }
                    }

                    $success = $utilities->updateItem($definition, $info);
                    $result['update'] = $success;
                } else {
                    $success = $utilities->insertItem($definition, $info);
                    $result['create'] = $success;
                }
                if (! empty($info['postprocess'])) {
                    foreach ((array) $info['postprocess'] as $postprocess) {
                        if (is_callable($postprocess)) {
                            $postprocess($success);
                        }
                    }
                }
            } catch (\Throwable $e) {
                $result['error'] = tr("%0 on line %1 of %2", $e->getMessage(), $e->getLine(), $e->getFile());
            }
            $result['line'] = $line;
            return $result;
        });

        if (method_exists($source, 'importSuccess')) {
            $source->importSuccess();
        }

        return true;
    }

    /**
     * Similar to write method but iterates through the source entries and deletes
     * matching tracker items by primary key.
     */
    public function delete(\Tracker\Tabular\Source\SourceInterface $source)
    {
        $utilities = new \Services_Tracker_Utilities();
        $schema = $source->getSchema();
        $columns = $schema->getColumns();

        $lookup = $this->getItemIdLookup($schema);

        $result = [];
        foreach ($source->getEntries() as $line => $entry) {
            $info = [
                'itemId' => false,
                'fields' => [],
            ];
            foreach ($columns as $column) {
                $entry->parseInto($info, $column);
            }
            $info['itemId'] = $lookup($info);

            if (! empty($info['itemId'])) {
                $utilities->removeItem($info['itemId']);
            }
        }

        return true;
    }

    private function getItemIdLookup($schema)
    {
        $pk = $schema->getPrimaryKey();
        if ($pk) {
            $pkField = $pk->getField();
        } else {
            $pkField = null;
        }

        if ($pkField == 'itemId') {
            return function ($info) {
                return $info['itemId'];
            };
        } else {
            return function ($info) use ($schema, $pkField) {
                $table = \TikiDb::get()->table('tiki_tracker_item_fields');
                if ($pkField) {
                    $fieldId = $this->getCachedFieldId($schema, $pkField);
                    return $table->fetchOne('itemId', [
                        'fieldId' => $fieldId,
                        'value' => $info['fields'][$pkField],
                    ]);
                } else {
                    $values = [];
                    foreach ($info['fields'] as $permName => $value) {
                        $values[$this->getCachedFieldId($schema, $permName)] = $value;
                    }
                    return \TikiLib::lib('trk')->get_item_by_field_values($values);
                }
            };
        }
    }

    private function getCachedFieldId($schema, $permName)
    {
        static $fieldCache = [];

        if (! isset($fieldCache[$permName])) {
            $definition = $schema->getDefinition();
            $f = $definition->getFieldFromPermName($permName);
            $fieldCache[$permName] = $f['fieldId'];
        }

        return $fieldCache[$permName];
    }
}
