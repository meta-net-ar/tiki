<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Symfony\Component\Console\Helper\FormatterHelper;
use Tiki\Profiling\Timer;

class Services_Search_Controller
{
    public function action_help($input)
    {
        return [
            'title' => tr('Help'),
        ];
    }

    public function action_rebuild($input)
    {
        global $num_queries;
        global $prefs;

        Services_Exception_Denied::checkGlobal('admin');

        $timer = new Timer();
        $timer->start();

        $memory_peak_usage_before = memory_get_peak_usage();

        $num_queries_before = $num_queries;

        $unifiedsearchlib = TikiLib::lib('unifiedsearch');
        $currentEngine = $unifiedsearchlib->getCurrentEngineDetails();
        $unusedIndices = $unifiedsearchlib->listAllUnusedIndexes($currentEngine);

        if (isset($unusedIndices['indices']) && count($unusedIndices['indices'])) {
            $unusedIndicesMessage = "You have the following unused indexes:\n";
            $unusedIndicesMessage .= '<ul>';
            foreach ($unusedIndices['indices'] as $m => $indices) {
                $unusedIndicesMessage .= "<li>" . $indices . "</li>";
            }
            $unusedIndicesMessage .= '</ul>';
            $unusedIndicesMessage .= "If you don't need them (for debugging), run the following command:";
            $unusedIndicesMessage .= "<ul><li>php console.php index:cleanup</li></ul>";
            Feedback::note(['mes' => $unusedIndicesMessage]);
        } elseif (isset($unusedIndices['error'])) {
            Feedback::error(['mes' => tr($unusedIndices['error'])]);
        }

        $access = TikiLib::lib('access');
        $stat = null;


        if ($input->getlaststats->int()) {
            $stat = $prefs['unified_last_rebuild_stats'];
        } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Apply 'Search index rebuild memory limit' setting if available
            if (! empty($prefs['allocate_memory_unified_rebuild'])) {
                $memory_limiter = new Tiki_MemoryLimit($prefs['allocate_memory_unified_rebuild']);
            }

            $stat = $unifiedsearchlib->rebuild($input->loggit->int());

            TikiLib::lib('cache')->invalidateAll('search_valueformatter');

            // Also rebuild admin index
            TikiLib::lib('prefs')->rebuildIndex();

            // Back up original memory limit if possible
            if (isset($memory_limiter)) {
                unset($memory_limiter);
            }

            //clean error messages related with search index
            $removeIndexErrorsCallback = function ($item) {
                if ($item['type'] == 'error') {
                    foreach ($item['mes'] as $me) {
                        if (strpos($me, 'does not exist in the current index') !== false) {
                            return true;
                        }
                    }
                }
                return false;
            };

            Feedback::removeIf($removeIndexErrorsCallback);
        }

        $num_queries_after = $num_queries;

        list($engine, $version, $index) = $unifiedsearchlib->getCurrentEngineDetails();

        $lastLogItem = $unifiedsearchlib->getLastLogItem();
        list($fallbackEngine, $fallbackEngineName, $fallbackVersion, $fallbackIndex) = $unifiedsearchlib->getFallbackEngineDetails();

        $formattedStats = null;
        if (! empty($stat)) {
            $list = false;
            $unifiedsearchlib->formatStats($stat, function ($line) use (&$msg, &$list) {
                if (substr($line, 0, 2) === '  ') {
                    if (! $list) {
                        $list = true;
                        $msg .= "<ul>";
                    }
                    $msg .= "<li>$line</li>";
                } else {
                    if ($list) {
                        $list = false;
                        $msg .= '</ul>';
                    }
                    $msg .= "<strong>$line</strong>";
                }
            });
            if ($list) {
                $msg .= '</ul>';
            }
            if ($input->getlaststats->int()) {
                $formattedStats = $msg;
            } else {
                Feedback::success(['title' => tr('Indexed stats'), 'mes' => $msg]);
            }
        }

        if ($fallbackEngine != null) {
            if (! empty($stat['fallback'])) {
                Feedback::success(['title' => tr('Fallback search engine'), 'mes' => tr('Fallback search index was rebuilt.')]);
            } else {
                Feedback::error(['title' => tr('Fallback search engine'), 'mes' => tr('Fallback search index was not rebuilt.')]);
            }
        }

        $num_queries = ($num_queries_after - $num_queries_before);

        $executionTime = FormatterHelper::formatTime($timer->stop());

        if ($input->loggit->int()) {
            $log = new Laminas\Log\Writer\Stream($unifiedsearchlib->getLogFilename($input->loggit->int(), $prefs['unified_engine']), 'a');
            $loggerInstance = new Laminas\Log\Logger();
            $loggerInstance->addWriter($log);
            $loggerInstance->info("Execution time: " . $executionTime);
        }

        if ($num_queries) {
            $msg = '<ul>';
            $msg .= '<li>' . tr('Execution time:') . ' ' . $executionTime . '</li>';
            $msg .= '<li>' . tr('Current Memory usage:') . ' ' . FormatterHelper::formatMemory(memory_get_usage()) . '</li>';
            $msg .= '<li>' . tr('Memory peak usage before indexing:') . ' ' . FormatterHelper::formatMemory($memory_peak_usage_before) . '</li>';
            $msg .= '<li>' . tr('Memory peak usage after indexing:') . ' ' . FormatterHelper::formatMemory(memory_get_peak_usage()) . '</li>';
            $msg .= '<li>' . tr('Number of queries:') . ' ' . $num_queries . '</li>';
            $msg .= '</ul>';
            Feedback::success(['title' => tr('Execution Statistics'), 'mes' => $msg]);
        }

        return [
            'title' => $input->getlaststats->int() ? '' : tr('Rebuild Index'),
            'formattedStats' => $formattedStats,
            'search_engine' => $engine,
            'search_version' => $version,
            'search_index' => $index,
            'fallback_search_engine' => isset($fallbackEngineName) ? $fallbackEngineName : '',
            'fallback_search_version' => isset($fallbackVersion) ? $fallbackVersion : '',
            'fallback_search_index' => isset($fallbackIndex) ? $fallbackIndex : '',
            'queue_count' => $unifiedsearchlib->getQueueCount(),
            'log_file_browser' => $unifiedsearchlib->getLogFilename(1),
            'fallback_log_file_browser' => $unifiedsearchlib->getLogFilename(1, $fallbackEngine),
            'log_file_console' => $unifiedsearchlib->getLogFilename(2),
            'fallback_log_file_console' => $unifiedsearchlib->getLogFilename(2, $fallbackEngine),
            'lastLogItemWeb' => $lastLogItem['web'] ?: tr('Unable to get info from log file.'),
            'lastLogItemConsole' => $lastLogItem['console'] ?: tr('Unable to get info from log file.'),
            'isAjax' => $access->is_xml_http_request(),
            'showForm' => empty($stat)
        ];
    }

    public function action_process_queue($input)
    {
        Services_Exception_Denied::checkGlobal('admin');

        $batch = $input->batch->int() ?: 0;

        $unifiedsearchlib = TikiLib::lib('unifiedsearch');
        $stat = null;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            @ini_set('max_execution_time', 0);
            @ini_set('memory_limit', -1);
            $stat = $unifiedsearchlib->processUpdateQueue($batch);
        }

        return [
            'title' => tr('Process Update Queue'),
            'stat' => $stat,
            'queue_count' => $unifiedsearchlib->getQueueCount(),
            'batch' => $batch,
        ];
    }

    public function action_lookup($input)
    {
        global $prefs;

        $smarty = TikiLib::lib('smarty');

        try {
            $filterData = $input->filter->none();
            $filter = (! empty($filterData) && is_array($filterData)) ? $filterData : [];
            $format = $input->format->text() ?: '{title}';
            $use_permname = $input->use_permname->text();
            $titleFilter = null;
            $highlightHelper = null;

            /** @var UnifiedSearchLib $lib */
            $lib = TikiLib::lib('unifiedsearch');

            if (! empty($filter['title']) && preg_match_all('/\{(\w+)\}/', $format, $matches)) {
                // formatted object_selector search results should also search in formatted fields besides the title
                $titleFilter = $filter['title'];
                unset($filter['title']);
                $query = $lib->buildQuery($filter);
                $searchable = [];
                $index = $lib->getIndex();
                foreach ($matches[1] as $field) {
                    if (! method_exists($index, 'isTextField')) {
                        $searchable[] = $field;
                        continue;
                    }
                    if ($index->isTextField($field)) {
                        $searchable[] = $field;
                        continue;
                    }
                }
                if ($searchable) {
                    $query->filterContent($titleFilter, $searchable);
                }
                $highlightHelper = new Search_MySql_HighlightHelper(explode(' ', $titleFilter));
            } else {
                $query = $lib->buildQuery($filter);
            }

            $query->setOrder($input->sort_order->text() ?: 'title_asc');
            $query->setRange($input->offset->int(), $input->maxRecords->int() ?: $prefs['maxRecords']);

            $result = $query->search($lib->getIndex());

            $result->applyTransform(function ($item) use ($format, $smarty, $titleFilter, $highlightHelper, $use_permname) {
                $transformed = [
                    'object_type' => $item['object_type'],
                    'object_id' => $use_permname != 'y' ? $item['object_id'] : (TikiLib::lib('trk')->get_field_info($item['object_id'])['permName'] ?? $item['object_id']),
                    'parent_id' => $item['gallery_id'],
                    'title' => preg_replace_callback('/\{([\w\.]+)\}/', function ($matches) use ($item, $format, $titleFilter, $highlightHelper) {
                        $key = $matches[1];
                        if (isset($item[$key])) {
                            // if this is a trackeritem we do not want only the name but also the trackerid listed when setting up a field
                            // otherwise its hard to distingish which field that is if multiple tracker use the same fieldname
                            // example: setup of trackerfield item-link: choose some fields from a list. currently this list show all fields of all trackers
                            if ($item['object_type'] == 'trackerfield') {
                                return $item[$key] . ' (Tracker-' . $item['tracker_id'] . ', Field-' . $item['object_id'] . ')';
                            } else {
                                $value = $item[$key];
                                if (is_array($value)) {
                                    $value = $item[$key . "_paths"] ??
                                        $item[$key . "_names"] ??
                                        $item[$key . "_text"] ??
                                        implode(',', $item[$key]);
                                } elseif (! empty($item[$key . '_text'])) {
                                    $value = $item[$key . '_text'];
                                }
                                if ($titleFilter) {
                                    $value = $highlightHelper->filter($value);
                                }
                                return $value;
                            }
                        } elseif (substr($key, 0, 5) == 'meta.') {
                            return '';
                        } elseif ($format == '{title}') {
                            return tr('empty');
                        } else {
                            return '';
                        }
                    }, $format),
                ];
                if ($item['object_type'] == 'trackeritem') {
                    $transformed['status_icon'] = smarty_function_tracker_item_status_icon(['item' => $item['object_id']], $smarty->getEmptyInternalTemplate());
                }
                return $transformed;
            });

            return [
                'title' => tr('Lookup Result'),
                'resultset' => $result,
            ];
        } catch (Search_Elastic_TransportException $e) {
            throw new Services_Exception_NotAvailable('Search functionality currently unavailable.');
        } catch (Exception $e) {
            throw new Services_Exception_NotAvailable($e->getMessage());
        }
    }

    public function action_object_selector($input)
    {
        global $smarty;
        return [
            'selector' => smarty_function_object_selector($input->params->array(), $smarty->getEmptyInternalTemplate())
        ];
    }
}
