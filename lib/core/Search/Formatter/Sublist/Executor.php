<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Search\Formatter\Sublist;

use ArrayObject;
use Exception;
use Search_Formatter;
use Search_Formatter_Plugin_ArrayTemplate;
use Search_Formatter_Plugin_Interface;
use Search_Formatter_Plugin_WikiTemplate;
use Search_Formatter_ValueFormatter;
use Search_Query;
use Search_Query_WikiBuilder;
use Search_ResultSet;
use TikiLib;

class Executor
{
    private $record;
    private $searchFormatter;
    private $data;
    private $root_data;

    private $formatterPlugins = [];
    private $reverseMapping = [];

    public function __construct(Record $record, Search_Formatter $sf)
    {
        $this->record = $record;
        $this->searchFormatter = $sf;
    }

    public function runOnDataset(&$data, &$root_data)
    {
        $this->data = &$data;
        $this->root_data = &$root_data;
        $this->initPlugins();
        $body = $this->replacePlaceholders();
        $result = $this->performSearch($body);
        $this->formatResult($result);
        $this->processSublists();
    }

    protected function initPlugins()
    {
        foreach ($this->record->getFormats() as $format) {
            $plugin = new Search_Formatter_Plugin_WikiTemplate($format['body']);
            $plugin->setRaw(! empty($format['arguments']['mode']) && $format['arguments']['mode'] == 'raw');
            $this->formatterPlugins[$format['arguments']['name']] = $plugin;
        }
        if ($this->searchFormatter->plugin instanceof Search_Formatter_Plugin_ArrayTemplate && $this->record->isMultiple()) {
            throw new Exception('Using SUBLIST with multiple results in an array context is not supported.');
        }
    }

    /** @array typically a raw array or a Search_Formatter_Transform_DynamicLoaderWrapper */
    private static function checkFieldIsAvailable(string $fieldName, array|\ArrayObject $record)
    {
        $arrayObject = is_array($record) ? new ArrayObject($record) : $record;
        $retVal = $arrayObject->offsetExists($fieldName);
        if (! $retVal) {
            $availableFields = array_keys($arrayObject->getArrayCopy());
            sort($availableFields);
            $msg = tr('Field %0 not found.  The fields available in the current context are: %1', $fieldName, implode(', ', $availableFields));
            throw new Exception($msg);
        }
        return $retVal;
    }
    protected function replacePlaceholders()
    {
        $body = $this->record->getBody();
        foreach ($this->record->getFilters() as $filter) {
            $match = $filter['match'];
            $arguments = $filter['arguments'];
            if (! isset($arguments['field'])) {
                // TODO: consider other filter types as range searches
                throw new Exception(tr('Filter blocks inside sublist sections in PluginList need field reference.'));
            }
            foreach ($arguments as $name => $value) {
                // Sublist special processing will only happen if parent or root is provided
                if (preg_match('/\$(parent|root)\.(.*?)\|?(object_ids|multivalue)?\$/', $value, $m)) {
                    $placeholder = $m[0];
                    $type = $m[1]; // parent or root
                    $field = $m[2];
                    $modifier = $m[3] ?? null;
                    $values = [];
                    $valueExtractor = function ($val) use ($placeholder, $value, $modifier, &$values) {
                        if ($modifier == 'object_ids') {
                            $val = array_map(function ($id) {
                                list($type, $id) = explode(':', $id);
                                return trim($id);
                            }, explode("\n", $val));
                            $values = array_merge($values, $val);
                            return $val;
                        } elseif ($modifier == 'multivalue') {
                            $val = str_replace($placeholder, $val, $value);
                            $values[] = $val;
                            $converter = TikiLib::lib('unifiedsearch')->getIndex()->getTypeFactory()->multivalue($val);
                            return $converter->getValue();
                        } else {
                            $val = str_replace($placeholder, $val, $value);
                            $values[] = $val;
                            return $val;
                        }
                    };
                    if ($type == 'parent') {
                        foreach ($this->data as $i => $row) {
                            if ($this->record->getParent() && $this->record->getParent()->isMultiple()) {
                                // parent sublists might have entries with multiple records per key
                                foreach ($row as $j => $subrow) {
                                    if (self::checkFieldIsAvailable($field, $subrow)) {
                                        $this->reverseMapping[$i][$j][] = [
                                            'value' => $valueExtractor($subrow[$field]),
                                            'target_field' => $arguments['field'],
                                        ];
                                    }
                                }
                            } else {
                                if (self::checkFieldIsAvailable($field, $row)) {
                                    $this->reverseMapping[$i][0][] = [
                                        'value' => $valueExtractor($row[$field]),
                                        'target_field' => $arguments['field'],
                                    ];
                                }
                            }
                        }
                    } elseif ($type == 'root') {
                        foreach ($this->root_data as $i => $row) {
                            if (self::checkFieldIsAvailable($field, $row)) {
                                $this->reverseMapping[$i][0][] = [
                                    'value' => $valueExtractor($row[$field]),
                                    'target_field' => $arguments['field'],
                                ];
                            }
                        }
                    } else {
                        throw new Exception("This should not be possible");
                    }
                    $values = array_unique($values);
                    $arguments[$name] = implode(' OR ', $values);
                    if ($name == 'exact') {
                        $arguments['content'] = $arguments['exact'];
                        unset($arguments['exact']);
                    }
                    if (empty($values)) {
                        $replacement = str_replace($placeholder, '', (string)$match);
                    } else {
                        $replacement = $match->buildPluginString('filter', $arguments, $match->getBody());
                    }
                    $body = str_replace((string)$match, $replacement, $body);
                }
            }
        }
        return $body;
    }

    protected function performSearch(string $body)
    {
        $matches = (new Parser())->getMatches($body);

        $query = new Search_Query();
        TikiLib::lib('unifiedsearch')->initQuery($query);

        $builder = new Search_Query_WikiBuilder($query);
        $builder->enableAggregate();
        $builder->skipPagination();
        $builder->apply($matches);

        // try to retrieve as many as possible from the sublist subqueries
        $query->setRange(0, 9999);

        $index = TikiLib::lib('unifiedsearch')->getIndex();
        return $query->search($index);
    }

    protected function formatResult($result)
    {
        $key = $this->record->getKey();

        foreach ($result as $entry) {
            $subrow = [];
            foreach ($this->formatterPlugins as $name => $plugin) {
                $defaultValues = $plugin->getFields();
                $fields = array_keys($defaultValues);
                $row = [];
                foreach ($fields as $f) {
                    if (self::checkFieldIsAvailable($f, $entry)) {
                        $row[$f] = $entry[$f];
                    }
                }
                $subInput = new Search_Formatter_ValueFormatter(array_merge($defaultValues, $row));
                $subrow[$name] = $this->searchFormatter->render($plugin, Search_ResultSet::create([$plugin->prepareEntry($subInput)]), $this->searchFormatter->plugin->getFormat());
                if ($this->searchFormatter->plugin->getFormat() == Search_Formatter_Plugin_Interface::FORMAT_HTML && $plugin->getFormat() == Search_Formatter_Plugin_Interface::FORMAT_WIKI) {
                    $subrow[$name] = preg_replace('/^~\/np~|~np~$/', '', $subrow[$name]);
                    $subrow[$name] = TikiLib::lib('parser')->parse_data($subrow[$name]);
                }
            }
            if (count($this->formatterPlugins) == 0) {
                $subrow = $entry;
            }
            foreach ($this->reverseMapping as $i => $mappings) {
                foreach ($mappings as $j => $mapping) {
                    $count = 0;
                    foreach ($mapping as $map) {
                        // TODO: use separate class to handle mapping, checks which records they relate to and distinction between one-to-one and one-to-many relations
                        if (isset($entry[$map['target_field']])) {
                            if (is_array($entry[$map['target_field']])) {
                                if (is_array($map['value']) && array_intersect($map['value'], $entry[$map['target_field']])) {
                                    $count++;
                                }
                                if (! is_array($map['value']) && in_array($map['value'], $entry[$map['target_field']])) {
                                    $count++;
                                }
                            } else {
                                if (is_array($map['value']) && in_array($entry[$map['target_field']], $map['value'])) {
                                    $count++;
                                }
                                if (! is_array($map['value']) && $entry[$map['target_field']] == $map['value']) {
                                    $count++;
                                }
                            }
                        }
                    }
                    if ($count == count($mapping)) {
                        if ($this->record->getParent() && $this->record->getParent()->isMultiple()) {
                            if ($this->record->isMultiple()) {
                                // might be ArrayObject (Search_Formatter_Transform_DynamicLoaderWrapper) where array auto-creation doesn't work
                                $arr = $this->data[$i][$j][$key] ?? [];
                                $arr[] = $subrow;
                                $this->data[$i][$j][$key] = $arr;
                            } else {
                                $this->data[$i][$j][$key] = $subrow;
                            }
                        } else {
                            if ($this->record->isMultiple()) {
                                $arr = $this->data[$i][$key] ?? [];
                                $arr[] = $subrow;
                                $this->data[$i][$key] = $arr;
                            } else {
                                $this->data[$i][$key] = $subrow;
                            }
                        }
                    }
                }
            }
        }

        foreach ($this->data as $i => $row) {
            if (empty($row)) {
                continue;
            }
            if ($this->record->getParent() && $this->record->getParent()->isMultiple()) {
                foreach ($row as $j => $_) {
                    if (! isset($this->data[$i][$j][$key])) {
                        $this->data[$i][$j][$key] = [];
                    }
                    if ($this->record->isRequired() && empty($this->data[$i][$j][$key])) {
                        unset($this->data[$i][$j]);
                    }
                }
            } else {
                if (! isset($this->data[$i][$key])) {
                    $this->data[$i][$key] = [];
                }
                if ($this->record->isRequired() && empty($this->data[$i][$key])) {
                    unset($this->data[$i]);
                }
            }
        }
    }

    protected function processSublists()
    {
        $key = $this->record->getKey();
        if ($sublists = $this->record->getSublists()) {
            // prepare this sublist's entries for nested ones
            $subdata = [];
            foreach ($this->data as $i => $row) {
                if ($this->record->getParent() && $this->record->getParent()->isMultiple()) {
                    foreach ($row as $j => $_) {
                        $subdata[] = &$this->data[$i][$j][$key];
                    }
                } else {
                    $subdata[] = &$this->data[$i][$key];
                }
            }
            foreach ($sublists as $sublist) {
                $sublist->executeOverDataset($subdata, $this->root_data, $this->searchFormatter);
            }
        }
    }
}
