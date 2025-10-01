<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tracker\Filter\Collection;
use Tracker\Tabular\Source\ODBCSource;
use Tracker\Tabular\Source\APISource;
use Tracker\Tabular\Source\CsvSource;
use Tracker\Tabular\Schema;
use Tracker\Tabular\Source\PaginatedQuerySource;
use Tracker\Tabular\Source\QuerySource;
use Tracker\Tabular\Source\TrackerSourceEntry;
use Tracker\Tabular\Writer\APIWriter;
use Tracker\Tabular\Writer\HtmlWriter;
use Tracker\Tabular\Writer\ODBCWriter;
use Tracker\Tabular\Writer\TrackerWriter;

class Services_Tracker_TabularController
{
    public function setUp()
    {
        Services_Exception_Disabled::check('tracker_tabular_enabled');
    }

    /**
     * Returns the section for use with certain features like banning
     * @return string
     */
    public function getSection()
    {
        return 'trackers';
    }

    public function action_manage($input)
    {
        Services_Exception_Denied::checkGlobal('tiki_p_tabular_admin');

        $lib = TikiLib::lib('tabular');

        return [
            'title' => tr('Import-Export Formats'),
            'formatList' => $lib->getList(),
        ];
    }

    public function action_delete($input)
    {
        $tabularId = $input->tabularId->int();

        Services_Exception_Denied::checkObject('tiki_p_tabular_admin', 'tabular', $tabularId);

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $lib = TikiLib::lib('tabular');
            $result = $lib->remove($tabularId);

            if ($result->numRows() > 0) {
                Feedback::success(tr('Import-Export Tracker was deleted successfully.'));
            }
        }

        return [
            'title' => tr('Remove Format'),
            'tabularId' => $tabularId,
        ];
    }

    public function action_create($input)
    {
        Services_Exception_Denied::checkGlobal('tiki_p_tabular_admin');

        $format = [];
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $format["name"] = $input->name->text();
            $format["prefill"] = $input->prefill->text();
            if (empty($input->trackerId->int()) || empty($input->name->text())) {
                Feedback::error(tr('Both Tracker ID and Name are required.'));
            } else {
                $lib = TikiLib::lib('tabular');

                $format["trackerId"] = $input->trackerId->int();
                $tabularId = $lib->create($format["name"], $format["trackerId"], $input->use_odbc->int() ? $input->odbc->none() : []);

                $forward = [
                    'controller' => 'tabular',
                    'action' => 'edit',
                    'tabularId' => $tabularId
                ];

                if (! empty($input->prefill->text())) {
                    $forward['prefill'] = true;
                }

                if (! empty($input->prefill_odbc->text())) {
                    $forward['prefill_odbc'] = true;
                }

                return ['FORWARD' => $forward];
            }
        }

        return [
            'title' => tr('Create Import-Export Format'),
            'format' => $format,
            'has_odbc' => function_exists('odbc_connect'),
        ];
    }

    public function action_edit($input)
    {
        $lib = TikiLib::lib('tabular');
        $info = $lib->getInfo($input->tabularId->int());
        $prefill = $input->prefill->bool();
        $prefill_odbc = $input->prefill_odbc->bool();
        $trackerId = $info['trackerId'];

        Services_Exception_Denied::checkObject('tiki_p_tabular_admin', 'tabular', $info['tabularId']);

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $info['format_descriptor'] = json_decode($input->fields->none(), true);
            $info['filter_descriptor'] = json_decode($input->filters->none(), true);
            $schema = $this->getSchema($info);

            // FIXME : Blocks save and back does not restore changes, ajax validation required
            // $schema->validate();

            $config = ! empty($input->config->none()) ? $input->config->none() : [];
            $odbc_config = $input->use_odbc->int() ? $input->odbc->none() : [];
            $api_config = $input->use_api->int() ? $input->api->none() : [];
            $result = $lib->update($info['tabularId'], $input->name->text(), $schema->getFormatDescriptor(), $schema->getFilterDescriptor(), $config, $odbc_config, $api_config);

            if ($result->numRows() > 0) {
                Feedback::success(tr('Import-Export Tracker was updated successfully.'));
            }

            return [
                'FORWARD' => [
                    'controller' => 'tabular',
                    'action' => 'edit',
                    'tabularId' => $info['tabularId']
                ],
            ];
        }

        $schema = $this->getSchema($info, $prefill, $prefill_odbc);

        $encodings = mb_list_encodings();

        return [
            'title' => tr('Edit Format: %0', $info['name']),
            'tabularId' => $info['tabularId'],
            'trackerId' => $info['trackerId'],
            'name' => $info['name'],
            'config' => $info['config'],
            'odbc_config' => $info['odbc_config'],
            'api_config' => $info['api_config'],
            'columns' => $schema->getColumns(),
            'filters' => $schema->getFilterCollection()->getFilters(),
            'schema' => $schema,
            'filterCollection' => $schema->getFilterCollection(),
            'has_odbc' => function_exists('odbc_connect'),
            'encodings' => $encodings,

        ];
    }

    public function updateColumns($trackerId, $oldName, $newName, $oldPermName, $newPermName)
    {
        $lib = TikiLib::lib('tabular');
        $formats = $lib->getList(['trackerId' => $trackerId]);

        foreach ($formats as $format) {
            $info = $lib->getInfo($format['tabularId']);
            $formatDescriptor = $info['format_descriptor'];
            $filterDescriptor = $info['filter_descriptor'];

            foreach ($formatDescriptor as $key => $field) {
                if ($field['label'] == $oldName) {
                    $formatDescriptor[$key]['label'] = $newName;
                }
                if ($field['field'] == $oldPermName) {
                    $formatDescriptor[$key]['field'] = $newPermName;
                }
            }

            foreach ($filterDescriptor as $key => $field) {
                if ($field['label'] == $oldName) {
                    $filterDescriptor[$key]['label'] = $newName;
                }
                if ($field['field'] == $oldPermName) {
                    $filterDescriptor[$key]['field'] = $newPermName;
                }
            }

            $lib->update($format['tabularId'], $info['name'], $formatDescriptor, $filterDescriptor, $info['config'], $info['odbc_config'], $info['api_config']);
        }
    }

    /**
     * Copy one format to another new one
     *
     * @param JitFilter $input
     *
     * @return array
     * @throws Services_Exception_Denied
     * @throws Services_Exception_NotFound
     */
    public function action_duplicate($input)
    {
        $lib = TikiLib::lib('tabular');
        $info = $lib->getInfo($input->tabularId->int());

        if (! $info) {
            throw new Services_Exception_NotFound(tr('Format %0 not found', $input->tabularId->int()));
        }

        Services_Exception_Denied::checkObject('tiki_p_tabular_admin', 'tabular', $info['tabularId']);

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $schema = $this->getSchema($info);

            $tabularId = $lib->create($input->name->text(), $info['trackerId']);

            $lib->update($tabularId, $input->name->text(), $schema->getFormatDescriptor(), $schema->getFilterDescriptor(), $info['config'], $info['odbc_config'], $info['api_config']);

            return Services_Utilities::refresh();
        }

        return [
            'title' => tr('Duplicate Format: %0', $info['name']),
            'tabularId' => $info['tabularId'],
            'name' => tr('%0 copy', $info['name']),
        ];
    }

    public function action_select($input)
    {
        $permName = $input->permName->word();
        $trackerId = $input->trackerId->int();

        $tracker = \Tracker_Definition::get($trackerId);

        if (! $tracker) {
            throw new Services_Exception_NotFound();
        }

        Services_Exception_Denied::checkObject('tiki_p_view_trackers', 'tracker', $trackerId);

        $schema = new Schema($tracker);
        $local = $schema->getFieldSchema($permName);

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $column = $schema->addColumn($permName, $input->mode->text());

            $return = [
                'field' => $column->getField(),
                'mode' => $column->getMode(),
                'label' => $column->getLabel(),
                'isReadOnly' => $column->isReadOnly(),
                'isPrimary' => $column->isPrimaryKey(),
            ];
            if ($input->offsetExists('columnIndex')) {
                $return['columnIndex'] = $input->columnIndex->int();
            }

            return $return;
        }

        $return = [
            'title' => tr('Fields in %0', $tracker->getConfiguration('name')),
            'trackerId' => $trackerId,
            'permName' => $permName,
            'schema' => $local,
        ];
        if ($input->offsetExists('columnIndex')) {
            $return['columnIndex'] = $input->columnIndex->int();
        }
        if ($input->offsetExists('mode')) {
            $return['mode'] = $input->mode->text();
        }
        return $return;
    }

    public function action_select_filter($input)
    {
        $permName = $input->permName->word();
        $trackerId = $input->trackerId->int();

        $tracker = \Tracker_Definition::get($trackerId);

        if (! $tracker) {
            throw new Services_Exception_NotFound();
        }

        Services_Exception_Denied::checkObject('tiki_p_view_trackers', 'tracker', $trackerId);

        $schema = new Collection($tracker);
        $local = $schema->getFieldCollection($permName);

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $column = $schema->addFilter($permName, $input->mode->text());
            return [
                'field' => $column->getField(),
                'mode' => $column->getMode(),
                'label' => $column->getLabel(),
            ];
        }

        return [
            'title' => tr('Fields in %0', $tracker->getConfiguration('name')),
            'trackerId' => $trackerId,
            'permName' => $permName,
            'collection' => $local,
        ];
    }

    public function action_choose_applied_value($input)
    {
        $tabularId = $input->tabularId->int();

        $info = TikiLib::lib('tabular')->getInfo($tabularId);
        $trackerId = $info['trackerId'];

        Services_Exception_Denied::checkObject('tiki_p_view_trackers', 'tracker', $trackerId);

        $schema = $this->getSchema($info);
        $collection = $schema->getFilterCollection();
        $filter = $collection->getFilters()[$input->filterIndex->int()] ?? null;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if ($filter) {
                $filter->applyInput($input);
                return $filter->jsonSerialize();
            } else {
                return [];
            }
        }

        return [
            'title' => tr('Choose applied value'),
            'tabularId' => $tabularId,
            'filterIndex' => $input->filterIndex->int(),
            'filter' => $filter,
        ];
    }

    public function action_export_full_csv($input)
    {
        $lib = TikiLib::lib('tabular');
        $info = $lib->getInfo($input->tabularId->int());

        Services_Exception_Denied::checkObject('tiki_p_tabular_export', 'tabular', $info['tabularId']);

        $schema = $this->getSchema($info);
        $schema->validate();

        $source = $schema->getDefaultFilterSource();

        if ($info['odbc_config']) {
            $writer = new ODBCWriter($info['odbc_config']);
            $writer->write($source);

            Feedback::success(tr('Your export was completed successfully.'));
            return [
                'FORWARD' => [
                    'controller' => 'tabular',
                    'action' => 'manage',
                ],
            ];
        } elseif ($info['api_config']) {
            $writer = new APIWriter($info['api_config'], $info['config']);
            $result = $writer->write($source);

            Feedback::success(tr('Your export was completed. %0 item(s) succeeded, %1 item(s) failed and %2 items skipped.', $result['succeeded'], $result['failed'], $result['skipped']));
            foreach ($result['errors'] as $error) {
                Feedback::error($error);
            }
            return [
                'FORWARD' => [
                    'controller' => 'tabular',
                    'action' => 'manage',
                ],
            ];
        } else {
            $name = TikiLib::lib('tiki')->remove_non_word_characters_and_accents($info['name']);
            $writer = $schema->getWriter($name, 'full');
            $writer->sendHeaders($name);

            TikiLib::lib('tiki')->allocate_extra(
                'tracker_export_items',
                function () use ($writer, $source) {
                    $writer->write($source);
                }
            );
            exit;
        }
    }

    public function actionExportSimpleHeaders($input)
    {
        $lib = TikiLib::lib('tabular');
        $info = $lib->getInfo($input->tabularId->int());

        Services_Exception_Denied::checkObject('tiki_p_tabular_export', 'tabular', $info['tabularId']);

        $schema = $this->getSchema($info);
        $schema->validate();

        $headers = array_map(function ($column) {
            return $column->getLabel();
        }, $schema->getColumns());

        $name = TikiLib::lib('tiki')->remove_non_word_characters_and_accents($info['name']) . '_simple_headers.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=' . $name);

        $output = fopen('php://output', 'w');
        fputcsv($output, $headers, escape: TikiLib::TIKI_GLOBAL_CSV_ESCAPE_CHAR);
        fclose($output);
        exit;
    }

    public function action_export_partial_csv($input)
    {
        $tabularId = $input->tabularId->int();

        $lib = TikiLib::lib('tabular');
        $info = $lib->getInfo($tabularId);
        $trackerId = $info['trackerId'];

        Services_Exception_Denied::checkObject('tiki_p_tabular_export', 'tabular', $tabularId);

        $schema = $this->getSchema($info);
        $collection = $schema->getFilterCollection();

        $collection->applyInput($input);

        if ($_SERVER['REQUEST_METHOD'] == 'POST' || $input->confirm->word() === 'export') {
            $search = TikiLib::lib('unifiedsearch');
            $query = $search->buildQuery([
                'type' => 'trackeritem',
                'tracker_id' => $trackerId,
            ]);

            $collection->applyConditions($query);
            $source = new QuerySource($schema, $query);

            if ($info['api_config']) {
                $writer = new APIWriter($info['api_config'], $info['config']);
                $result = $writer->write($source);

                Feedback::success(tr('Your export was completed. %0 item(s) succeeded, %1 item(s) failed and %2 items skipped.', $result['succeeded'], $result['failed'], $result['skipped']));
                foreach ($result['errors'] as $error) {
                    Feedback::error($error);
                }
                return [
                    'FORWARD' => [
                        'controller' => 'tabular',
                        'action' => 'manage',
                    ],
                ];
            } else {
                $name = TikiLib::lib('tiki')->remove_non_word_characters_and_accents($info['name']);
                $writer = $schema->getWriter($name, 'partial');
                $writer->sendHeaders($name);

                TikiLib::lib('tiki')->allocate_extra(
                    'tracker_export_items',
                    function () use ($writer, $source) {
                        $writer->write($source);
                    }
                );
                exit;
            }
        }

        return [
            'FORWARD' => [
                'controller' => 'tabular',
                'action' => 'filter',
                'tabularId' => $tabularId,
                'target' => 'export',
            ],
        ];
    }

    public function action_export_search_csv($input)
    {
        $lib = TikiLib::lib('tabular');
        $trackerId = $input->trackerId->int();
        $tabularId = $input->tabularId->int();
        $conditions = array_filter([
            'trackerId' => $trackerId,
            'tabularId' => $tabularId,
        ]);

        $formats = $lib->getList($conditions);

        if ($tabularId) {
            $info = $lib->getInfo($tabularId);
            $schema = $this->getSchema($info);
            $schema->validate();

            $trackerId = $info['trackerId'];

            Services_Exception_Denied::checkObject('tiki_p_tabular_export', 'tabular', $tabularId);

            $search = TikiLib::lib('unifiedsearch');
            $query = $search->buildQuery($input->filter->none() ?: []);

            // Force filters
            $query->filterType('trackeritem');
            $query->filterContent($trackerId, 'tracker_id');

            $source = new QuerySource($schema, $query);
            $name = TikiLib::lib('tiki')->remove_non_word_characters_and_accents($info['name']);
            $writer = $schema->getWriter($name, 'search');
            $writer->sendHeaders($name);

            TikiLib::lib('tiki')->allocate_extra(
                'tracker_export_items',
                function () use ($writer, $source) {
                    $writer->write($source);
                }
            );
            exit;
        } elseif (count($formats) === 0) {
            throw new Services_Exception(tr('No formats available.'));
        } else {
            if ($trackerId) {
                Services_Exception_Denied::checkObject('tiki_p_view_trackers', 'tracker', $trackerId);
            } else {
                Services_Exception_Denied::checkGlobal('tiki_p_tabular_admin');
            }

            return [
                'title' => tr('Select Format'),
                'formats' => $formats,
                'filters' => $input->filter->none(),
            ];
        }
    }

    public function action_import_csv($input)
    {
        $lib = TikiLib::lib('tabular');
        $info = $lib->getInfo($input->tabularId->int());
        $separator = $input->separator->text();
        $fileIsTsv = ! ($separator == "," || $separator == ';');

        Services_Exception_Denied::checkObject('tiki_p_tabular_import', 'tabular', $info['tabularId']);

        $schema = $this->getSchema($info);
        $schema->validate();

        $done = false;
        $successImportMsg = tr('Your import was completed successfully.');

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && is_uploaded_file($_FILES['file']['tmp_name'])) {
            if ($fileIsTSV) {
                Services_Tracker_Utilities::parseTsvContentToCsv('file');
                $separator = ',';
            }
            $source = $schema->getSource($_FILES['file']['tmp_name'], $separator);
            $writer = new TrackerWriter();

            return TikiLib::lib('tiki')->allocate_extra(
                'tracker_import_items',
                function () use ($writer, $source, $info, $schema) {
                    $result = $writer->write($source);

                    unlink($_FILES['file']['tmp_name']);

                    if (TIKI_API) {
                        $data = [];
                        $columns = $schema->getColumns();
                        foreach ($result as $itemId) {
                            $entry = new TrackerSourceEntry($itemId);
                            $row = ['itemId' => $itemId];
                            foreach ($columns as $column) {
                                $row[$column->getField()] = $entry->render($column, false);
                            }
                            $data[] = $row;
                        }
                        return $data;
                    }

                    Feedback::success($successImportMsg);
                    return [
                        'FORWARD' => [
                            'controller' => 'tabular',
                            'action' => 'list',
                            'tabularId' => $info['tabularId'],
                        ]
                    ];
                }
            );
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $info['odbc_config']) {
            $source = new ODBCSource($schema, $info['odbc_config']);
            $writer = new TrackerWriter();
            $done = $writer->write($source);

            if (TIKI_API) {
                return ['feedback' => $successImportMsg];
            }

            Feedback::success($successImportMsg);
            return [
                'FORWARD' => [
                    'controller' => 'tabular',
                    'action' => 'list',
                    'tabularId' => $info['tabularId'],
                ]
            ];
        } elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && $info['api_config']) {
            $source = new APISource($schema, $info['api_config'], $input->placeholders->none() ?? []);
            $writer = new TrackerWriter();
            $done = $writer->write($source);

            if (TIKI_API) {
                return ['feedback' => $successImportMsg];
            }

            Feedback::success($successImportMsg);
            return [
                'FORWARD' => [
                    'controller' => 'tabular',
                    'action' => 'list',
                    'tabularId' => $info['tabularId'],
                ]
            ];
        }

        $placeholders = [];
        if (! empty($info['api_config'])) {
            if (preg_match_all('/%([^%]+)%/', $info['api_config']['list_url'], $matches)) {
                foreach ($matches[1] as $field) {
                    $placeholders[] = $field;
                }
            } elseif (preg_match_all('/%([^%]+)%/', $info['api_config']['list_parameters'], $matches)) {
                foreach ($matches[1] as $field) {
                    $placeholders[] = $field;
                }
            }
        }

        return [
            'title' => tr('Import'),
            'tabularId' => $info['tabularId'],
            'completed' => $done,
            'odbc' => ! empty($info['odbc_config']),
            'api' => ! empty($info['api_config']),
            'placeholders' => $placeholders,
            'format' => $schema->getFormat(),
        ];
    }

    public function action_delete_csv($input)
    {
        $lib = TikiLib::lib('tabular');
        $info = $lib->getInfo($input->tabularId->int());

        Services_Exception_Denied::checkObject('tiki_p_tabular_import', 'tabular', $info['tabularId']);

        $schema = $this->getSchema($info);
        $schema->validate();

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && is_uploaded_file($_FILES['file']['tmp_name'])) {
            $source = $schema->getSource($_FILES['file']['tmp_name']);
            $writer = new TrackerWriter();

            return TikiLib::lib('tiki')->allocate_extra(
                'tracker_import_items',
                function () use ($writer, $source, $info) {
                    $writer->delete($source);

                    unlink($_FILES['file']['tmp_name']);

                    $message = tr('Your delete request was completed successfully.');

                    if (TIKI_API) {
                        return ['feedback' => $message];
                    }

                    Feedback::success($message);
                }
            );
        }

        return [
            'FORWARD' => [
                'controller' => 'tabular',
                'action' => 'list',
                'tabularId' => $info['tabularId'],
            ]
        ];
    }

    public function action_filter($input)
    {
        $tabularId = $input->tabularId->int();

        $lib = TikiLib::lib('tabular');
        $info = $lib->getInfo($tabularId);
        $trackerId = $info['trackerId'];

        Services_Exception_Denied::checkObject('tiki_p_tabular_list', 'tabular', $tabularId);

        $schema = $this->getSchema($info);
        $collection = $schema->getFilterCollection();

        $collection->applyInput($input);

        /** @var UnifiedSearchLib $search */
        $search = TikiLib::lib('unifiedsearch');
        $query = $search->buildQuery([
            'type' => 'trackeritem',
            'tracker_id' => $trackerId,
        ]);
        $query->setRange(1);
        $collection->applyConditions($query);
        $resultset = $query->search($search->getIndex());
        $collection->setResultSet($resultset);

        $target = $input->target->word();

        if ($target == 'list') {
            $title = tr('Filter %0', $info['name']);
            $method = 'get';
            $action = 'list';
            $label = tr('Filter');
        } elseif ($target = 'export') {
            $title = tr('Export %0', $info['name']);
            $method = 'post';
            $action = 'export_partial_csv';
            $label = tr('Export');
        } else {
            throw new Services_Exception_NotFound();
        }

        return [
            'title' => $title,
            'tabularId' => $tabularId,
            'method' => $method,
            'action' => $action,
            'label' => $label,
            'filters' => array_map(function ($filter) {
                if (! $filter->getControl()->isUsable()) {
                    return false;
                }
                return [
                    'id' => $filter->getControl()->getId(),
                    'label' => $filter->getLabel(),
                    'help' => $filter->getHelp(),
                    'control' => $filter->getControl(),
                ];
            }, $collection->getFilters()),
        ];
    }

    public function action_list($input)
    {
        $tabularId = $input->tabularId->int();

        $lib = TikiLib::lib('tabular');
        $info = $lib->getInfo($tabularId);
        $trackerId = $info['trackerId'];

        Services_Exception_Denied::checkObject('tiki_p_tabular_list', 'tabular', $tabularId);

        $schema = $this->getSchema($info);
        $collection = $schema->getFilterCollection();

        $collection->applyInput($input, true);

        $search = TikiLib::lib('unifiedsearch');
        $query = $search->buildQuery([
            'type' => 'trackeritem',
            'tracker_id' => $trackerId,
        ]);
        $query->setRange($input->offset->int());

        $collection->applyConditions($query);

        $source = new PaginatedQuerySource($schema, $query);
        $writer = new HtmlWriter();

        $columns = array_values(array_filter($schema->getColumns(), function ($c) {
            return ! $c->isExportOnly();
        }));
        $arguments = $collection->getQueryArguments();

        $collection->setResultSet($source->getResultSet());

        $template = ['controls' => [], 'usable' => false, 'selected' => false];
        $filters = ['default' => $template, 'primary' => $template, 'side' => $template];
        foreach ($collection->getFilters() as $filter) {
            // Exclude unusable controls
            if (! $filter->getControl()->isUsable()) {
                continue;
            }

            $pos = $filter->getPosition();

            $filters[$pos]['controls'][] = [
                'id' => $filter->getControl()->getId(),
                'label' => $filter->getLabel(),
                'help' => $filter->getHelp(),
                'control' => $filter->getControl(),
                'description' => $filter->getControl()->getDescription(),
                'selected' => $filter->getControl()->hasValue(),
            ];

            $filters[$pos]['usable'] = true;
            if ($filter->getControl()->hasValue()) {
                $filters[$pos]['selected'] = true;
            }
        }

        return [
            'title' => tr($info['name']),
            'tabularId' => $tabularId,
            'filters' => $filters,
            'columns' => $columns,
            'data' => $writer->getData($source),
            'resultset' => $source->getResultSet(),
            'baseArguments' => $arguments,
            'config' => $info['config'],
        ];
    }

    public function action_create_tracker($input)
    {
        global $tikilib;

        $tabularlib = TikiLib::lib('tabular');
        Services_Exception_Denied::checkGlobal('tiki_p_tabular_admin');

        $headerlib = TikiLib::lib('header');
        $headerlib->add_jsfile(PLOTLYJS_DIST_PATH . '/plotly-basic.min.js', true);

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Create a tracker
            $trackerUtilities = new Services_Tracker_Utilities();
            $trackerData = [
                'name' => $input->tracker_name->text(),
                'description' => '',
                'descriptionIsParsed' => 'n'
            ];
            $trackerId = $trackerUtilities->createTracker($trackerData);

            $fieldDescriptor = json_decode($input->fields->none(), true);

            $types = $trackerUtilities->getFieldTypes();

            foreach ($fieldDescriptor as $key => $field) {
                $fieldName = $field['label'];
                $fieldType = $field['type'];
                $typeInfo = $types[$fieldType];

                // Populate default field options
                $options = $trackerUtilities->parseOptions("{}", $typeInfo);
                $options = $trackerUtilities->buildOptions($options, $fieldType);

                $fieldData = [
                    'trackerId' => $trackerId,
                    'name' => $fieldName,
                    'type' => $fieldType,
                    'isMandatory' => ($field['isPrimary'] || $field['isUniqueKey']) ? true : false,
                    'description' => '',
                    'descriptionIsParsed' => '',
                    'permName' => null,
                    'options' => $options,
                ];

                $fieldId = $trackerUtilities->createField($fieldData);

                $fieldDescriptor[$key]['field'] = ! empty($fieldData['permName']) ? $fieldData['permName'] : 'f_' . $fieldId;
                $fieldDescriptor[$key]['mode'] = $this->getFieldTypeDefaultMode($fieldType);

                // Force reload (number of fields and existing fields in tracker)
                Tracker_Definition::clearCache($trackerId);
            }

            // Create tabular tracker
            $tabularId = $tabularlib->create($input->name->text(), $trackerId);

            $info = $tabularlib->getInfo($tabularId);

            $info['format_descriptor'] = $fieldDescriptor;
            $info['filter_descriptor'] = [];
            $info['config'] = $input->config->none();
            $schema = $this->getSchema($info);

            $tabularlib->update($info['tabularId'], $input->name->text(), $schema->getFormatDescriptor(), $schema->getFilterDescriptor(), $info['config']);

            // Import the loaded file

            // Force reload schema
            Tracker_Definition::clearCache($trackerId);
            $schema = $this->getSchema($info);
            $schema->validate();

            if (! $schema->getPrimaryKey()) {
                Feedback::error(tr('Primary Key required'));
                return [
                    'FORWARD' => [
                        'controller' => 'tabular',
                        'action' => 'edit',
                        'tabularId' => $tabularId,
                    ],
                ];
            }

            $done = false;

            if (is_uploaded_file($_FILES['file']['tmp_name'])) {
                try {
                    $delimiter = ',';

                    if ($input->delimiter->text() == 'semicolon') {
                        $delimiter = ';';
                    } elseif ($input->delimiter->text() == 'tab') {
                        $delimiter = "\t";
                    }

                    $source = new CsvSource($schema, $_FILES['file']['tmp_name'], $delimiter);
                    $writer = new TrackerWriter();
                    $done = $writer->write($source);

                    unlink($_FILES['file']['tmp_name']);
                } catch (Exception $e) {
                    Feedback::error($e->getMessage());

                    // Rollback changes
                    $tabularlib->remove($tabularId);
                    $trackerUtilities->removeTracker($trackerId);
                }
            }

            if ($done) {
                Feedback::success(tr('Your import was completed successfully.'));
                return [
                    'FORWARD' => [
                        'controller' => 'tabular',
                        'action' => 'list',
                        'tabularId' => $info['tabularId'],
                    ]
                ];
            }
        }

        $uploadMaxFileSize = $tikilib->return_bytes(ini_get('upload_max_filesize'));

        return [
            'title' => tr('Create import-export format and tracker from file'),
            'types' => $this->getSupportedTabularFieldTypes(),
            'config' => [
                'import_update' => 1,
                'ignore_blanks' => 0,
                'import_transaction' => 0,
                'bulk_import' => 0,
                'skip_unmodified' => 0,
                'upload_max_filesize' => $uploadMaxFileSize
            ],
        ];
    }

    private function getSchema(array $info, $prefill = false, $prefill_odbc = false)
    {
        $tracker = \Tracker_Definition::get($info['trackerId']);

        if (! $tracker) {
            throw new Services_Exception_NotFound();
        }

        $schema = new Schema($tracker);

        $descriptor = $info['format_descriptor'];
        if ($prefill) {
            $fields = $tracker->getFields();

            foreach ($fields as $field) {
                $fieldName = $field['name'];
                $permName = $field['permName'];
                try {
                    $local = $schema->getFieldSchema($permName);
                    if ($local) {
                        $columns = $local->getColumns();
                    } else {
                        Feedback::warning(tr('Field %0 does not support export.', $permName));
                        continue;
                    }
                } catch (Exception $e) {
                    Feedback::warning($e->getMessage());
                    continue;
                }

                if (empty($columns)) {
                    Feedback::warning(tr('Configuration of field %0 does not support export.', $permName));
                    continue;
                }

                $descriptor[] = [
                    'label' => $fieldName,
                    'field' => $permName,
                    'mode' => $columns[0]->getMode(),
                    'displayAlign' => 'left',
                    'isPrimary' => false,
                    'isReadOnly' => false,
                    'isExportOnly' => false,
                    'isUniqueKey' => false
                ];
            }
        }
        if ($prefill_odbc) {
            $columns = [];
            $src = new ODBCSource($schema, $info['odbc_config']);
            try {
                $columns = $src->getRemoteSchema();
            } catch (Exception $e) {
                Feedback::error($e->getMessage());
            }
            $trackerUtilities = new Services_Tracker_Utilities();
            $types = $trackerUtilities->getFieldTypes();
            foreach ($columns as $column) {
                $prefix = $tracker->getConfiguration('fieldPrefix');
                if (empty($prefix)) {
                    $prefix = strtolower(substr($tracker->getConfiguration('name'), 0, 1)) . substr(preg_replace('/[^a-z0-9]/i', '', ucwords(strtolower($tracker->getConfiguration('name')))), 1);
                }
                $permName = $prefix . $column['name'];
                $field = $tracker->getFieldFromPermName($permName);
                if ($field) {
                    $fieldType = $field['type'];
                } else {
                    switch ($column['type']) {
                        case 'integer':
                        case 'decimal':
                        case 'numeric':
                        case 'smallint':
                        case 'real':
                        case 'float':
                        case 'double':
                        case 'bit':
                        case 'tinyint':
                        case 'bigint':
                            $fieldType = 'n';
                            break;
                        case 'date':
                        case 'time':
                        case 'timestamp':
                        case 'datetime':
                        case 'utcdatetime':
                        case 'utctime':
                            $fieldType = 'j';
                            break;
                        case 'text':
                        case 'longtext':
                        case 'longvarchar':
                            $fieldType = 'a';
                            break;
                        default:
                            $fieldType = 't';
                    }
                    $typeInfo = $types[$fieldType];

                    // Populate default field options
                    $options = $trackerUtilities->parseOptions("{}", $typeInfo);
                    $options = $trackerUtilities->buildOptions($options, $fieldType);

                    $fieldData = [
                        'trackerId' => $info['trackerId'],
                        'name' => $column['name'],
                        'type' => $fieldType,
                        'isMandatory' => false,
                        'description' => $column['remarks'],
                        'descriptionIsParsed' => '',
                        'permName' => $permName,
                        'options' => $options,
                    ];

                    $fieldId = $trackerUtilities->createField($fieldData);
                }

                $descriptor[] = [
                    'label' => $column['name'],
                    'field' => $permName,
                    'mode' => $this->getFieldTypeDefaultMode($fieldType),
                    'remoteField' => $column['name'],
                    'displayAlign' => 'left',
                    'isPrimary' => false,
                    'isReadOnly' => false,
                    'isExportOnly' => false,
                    'isUniqueKey' => false
                ];
            }
            // reload definition and schema in case new fields were created
            $tracker = \Tracker_Definition::get($info['trackerId'], false);
            $schema = new Schema($tracker);
        }

        $schema->loadFormatDescriptor($descriptor);
        $schema->loadFilterDescriptor($info['filter_descriptor']);
        $schema->loadConfig($info['config']);

        return $schema;
    }

    /**
     * Get the list of supported field types by tabular
     * Info: Item Link and List are removed due to missing links on csv upload
     *
     * @return mixed
     */
    private function getSupportedTabularFieldTypes()
    {
        $trackerUtilities = new Services_Tracker_Utilities();
        $types = $trackerUtilities->getFieldTypes();

        unset($types['A']); // Attachment (deprecated)
        unset($types['w']); // Dynamic Items List
        unset($types['h']); // Header
        unset($types['icon']); // Icon
        unset($types['LANG']); // Language
        unset($types['G']); // Location
        unset($types['k']); // Page Selector
        unset($types['REL']); // Relations
        unset($types['S']); // Static Text
        unset($types['r']); // Item Link
        unset($types['l']); // Items List

        return $types;
    }

    /**
     * Get the default mode for a given field type to use in Tabular display fields
     *
     * @param string $fieldType Field type
     * @return string The default mode to display
     */
    private function getFieldTypeDefaultMode($fieldType)
    {

        switch ($fieldType) {
            case 'c': // Checkbox
                $mode  = 'y/n';
                break;
            case 'e': // Category
            case 'g': // Group Selector
                $mode = 'id';
                break;
            case 'd': // Dropdown
            case 'D': // Dropdown + Other
            case 'R': // Radio Buttons
            case 'M': // MultiSelect
            case 'y': // Country Selector
                $mode = 'code';
                break;
            case 'f': // Datetime
            case 'j': // Datetime + Picker
                $mode = 'unix';
                break;
            case 'u': // User Selector
                $mode = 'username';
                break;
            default:
                $mode = 'default';
        }

        return $mode;
    }
}
