<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Package\Extension\Api\Search as PackageApiSearch;

class Search_Indexer
{
    private $searchIndex;
    /**
     *
     * @var array of Search_ContentSource_Interface
     */
    private $contentSources = [];
    private $globalSources = [];
    private $packageSources = [];

    private $cacheGlobals = null;
    private $cacheTypes = [];
    private $cacheErrors = [];
    private $stats;

    private $contentFilters = [];

    public $log = null;
    private $logWriter = null;

    public $errorContext = null;

    public function __construct(Search_Index_Interface $searchIndex, $logWriter = null)
    {
        if (! $logWriter instanceof \Laminas\Log\Writer\AbstractWriter) {
            $logWriter = new Laminas\Log\Writer\Noop();
        } else {
            // writing logs
            set_error_handler(function ($errno, $errstr, $errfile = '', $errline = 0) {

                $error = '';

                switch ($errno) {
                    case E_PARSE:
                    case E_ERROR:
                    case E_CORE_ERROR:
                    case E_COMPILE_ERROR:
                    case E_USER_ERROR:
                        $error = 'FATAL';
                        break;
                    case E_WARNING:
                    case E_USER_WARNING:
                    case E_COMPILE_WARNING:
                    case E_RECOVERABLE_ERROR:
                        $error = 'WARNING';
                        break;
                    case E_NOTICE:
                    case E_USER_NOTICE:
                        $error = 'NOTICE';
                        break;
                    case E_STRICT:
                        $error = 'STRICT';
                        break;
                    case E_DEPRECATED:
                    case E_USER_DEPRECATED:
                        $error = 'DEPRECATED';
                        break;
                    default:
                        break;
                }

                if ($this->errorContext) {
                    $error = $this->errorContext . ': ' . $error;
                }

                $this->cacheErrors[] = compact('error', 'errno', 'errstr', 'errfile', 'errline');

                return true;
            }, E_ALL);
        }

        $logWriter->setFormatter(new Laminas\Log\Formatter\Simple());
        $this->log = new Laminas\Log\Logger();
        $this->log->addWriter($logWriter);
        $this->logWriter = $logWriter;

        if (method_exists($searchIndex, 'setIndexer')) {
            $searchIndex->setIndexer($this);
        }
        $this->searchIndex = $searchIndex;

        $api = new PackageApiSearch();
        $this->packageSources = $api->getSources();
    }

    public function addContentSource(string $objectType, Search_ContentSource_Interface $contentSource)
    {
        $this->contentSources[$objectType] = $contentSource;
    }

    private function getContentSource(string $objectType): Search_ContentSource_Interface
    {
        return $this->contentSources[$objectType];
    }

    public function addGlobalSource(Search_GlobalSource_Interface $globalSource)
    {
        if (is_a($globalSource, "Search_GlobalSource_RelationSource")) {
            $globalSource->setContentSources($this->contentSources);
        }
        $this->globalSources[] = $globalSource;
    }

    public function clearSources()
    {
        $this->contentSources = [];
        $this->globalSources = [];
    }

    public function addContentFilter(Laminas\Filter\FilterInterface $filter)
    {
        $this->contentFilters[] = $filter;
    }

    /**
     * Rebuild the entire index.
     *
     * @param array $lastStats array from prefs containing the last stats info
     * @param Symfony\Component\Console\Helper\ProgressBar $progress progress bar object from rebuild console command
     *
     * @return array
     */
    public function rebuild($lastStats = [], $progress = null)
    {
        $this->log('Starting rebuild');
        $contentTypes = array_fill_keys(array_keys($this->contentSources), 0);

        if ($progress) {
            $progress->start();
        }
        $this->stats = [];
        $this->stats['counts'] = $contentTypes;
        $this->stats['times'] = $contentTypes;

        $timer = new timer();

        foreach ($this->contentSources as $objectType => $contentSource) {
            if ($progress) {
                if (! empty($lastStats['default']['times'][$objectType]) && ! empty($lastStats['default']['counts'][$objectType])) {
                    $docTime = $lastStats['default']['times'][$objectType] * 1000 / $lastStats['default']['counts'][$objectType];
                } else {
                    $docTime = 1;
                }
                $progress->setMessage(tr('Processing %0 documents', $objectType));
            }
            $timer->start();
            $documents = $contentSource->getDocuments();

            foreach ($documents as $objectId) {
                $this->stats['counts'][$objectType] += $this->addDocument($objectType, $objectId);

                if ($progress) {
                    $progress->advance(round($docTime));
                }
            }

            $this->stats['times'][$objectType] = $timer->stop();
        }

        if (method_exists($this->searchIndex, 'generateSearchedFieldIndexStats')) {
            $this->searchIndex->generateSearchedFieldIndexStats();
        }

        $totalTime = 0;

        foreach ($this->stats['times'] as $time) {
            $totalTime += $time;
        }

        $this->stats['times']['total'] = $totalTime;

        global $prefs;
        if ($prefs['unified_engine'] !== 'elastic') {
            $this->log('Starting optimization');
            $this->searchIndex->optimize();
            $this->log('Finished optimization');
        }
        $this->log('Finished rebuild');
        return $this->stats;
    }

    /**
     * Get current rebuild stats for a given key
     */
    public function getStats($key)
    {
        return $this->stats[$key] ?? null;
    }

    /**
     * Augment statistics during rebuild
     */
    public function addStats($key, $value)
    {
        $this->stats[$key] = $value;
    }

    public function update(array $objectList)
    {

        foreach (array_unique($objectList, SORT_REGULAR) as $object) {
            $this->searchIndex->invalidateMultiple([$object]);
            $this->addDocument($object['object_type'], $object['object_id']);
        }

        $this->searchIndex->endUpdate();
    }

    private function addDocument($objectType, $objectId)
    {
        $this->log("addDocument $objectType $objectId");

        $data = $this->getDocuments($objectType, $objectId);
        foreach ($data as $entry) {
            try {
                $this->searchIndex->addDocument($entry);
            } catch (\Search\Manticore\FatalException $e) {
                throw new Exception($e->getMessage());
            } catch (Exception $e) {
                $msg = tr(
                    'Indexing failed while processing "%0" (type %1) with the error "%2"',
                    $objectId,
                    $objectType,
                    $e->getMessage()
                );
                Feedback::error($msg);
                TikiLib::lib('errortracking')->captureException($e);
            }
            foreach ($this->cacheErrors as $err) {
                $this->log->err($err['error'] . ': ' . $err['errstr'], [
                    'code' => $err['errno'],
                    'file' => $err['errfile'],
                    'line' => $err['errline'],
                ]);
                $e = new ErrorException($err['errstr'], 0, $err['errno'], $err['errfile'], $err['errline']);
                TikiLib::lib('errortracking')->captureException($e);
            }
            $this->cacheErrors = [];
            // log file display feedback messages after each document line to make it easier to track
            if (! $this->logWriter instanceof Laminas\Log\Writer\Noop) {
                Feedback::printToLog($this->log);
                Feedback::clear();
            }
        }

        return count($data);
    }


    /**
     * Return all supported content types and their fields
     *
     * @return array
     */
    public function getAvailableFields()
    {
        $output = [
            'global' => [],
            'object_types' => [],
        ];
        /**
         * @var  string $objectType
         * @var  Search_ContentSource_Interface $contentSource
         */
        foreach ($this->contentSources as $objectType => $contentSource) {
            $output['object_types'][$objectType] = $contentSource->getProvidedFields();
            $output['global'] = array_unique(
                array_merge(
                    $output['global'],
                    array_keys(
                        array_filter($contentSource->getGlobalFields())
                    )
                )
            );
        }

        return $output;
    }

    public function getAvailableFieldTypes()
    {
        $output = [];

        foreach ($this->contentSources as $contentSource) {
            $output = array_merge($output, $contentSource->getProvidedFieldTypes());
        }

        foreach ($this->globalSources as $globalSource) {
            $output = array_merge($output, $globalSource->getProvidedFieldTypes());
        }

        foreach ($this->packageSources as $packageSource) {
            $output = array_merge($output, $packageSource->getProvidedFieldTypes());
        }

        return $output;
    }

    /**
     * Final array of keys representing the information to be indexed for a single tiki object.
     * Don't know why this is getDocuments and not getDocument.
     *
     * @param string $objectType the object type of the object id to find the content source as added in addContentSource
     * @param [type] $objectId The object we want information for
     * @return array of keys representing the
     */
    public function getDocuments(string $objectType, $objectId): array
    {
        $out = [];

        $typeFactory = $this->searchIndex->getTypeFactory();

        if (isset($this->contentSources[$objectType])) {
            $globalFields = $this->getGlobalFields($objectType);

            $contentSource = $this->getContentSource($objectType);

            if (method_exists($contentSource, 'setIndexer')) {
                $contentSource->setIndexer($this);
            }

            if (false !== $data = $contentSource->getDocument($objectId, $typeFactory)) {
                if ($data === null) {
                    Feedback::error(tr(
                        'Object %0 type %1 returned null from getDocument function',
                        $objectId,
                        $objectType
                    ));
                    $data = [];
                }
                // Why do we need to do this?  benoitg - 2024-03-09
                if (! is_int(key($data))) {
                    $data = [$data];
                }

                foreach ($data as $entry) {
                    $out[] = $this->augmentDocument($objectType, $objectId, $entry, $typeFactory, $globalFields);
                }
            }
        }

        return $out;
    }
/**
 * Called once for each document.  Each globalSource can filter out, modify or add keys base of the global sources
 */
    private function augmentDocument($objectType, $objectId, $data, $typeFactory, $globalFields)
    {
        $initialData = $data;

        foreach ($this->globalSources as $globalSource) {
            $local = $globalSource->getData($objectType, $objectId, $typeFactory, $initialData);

            if (false !== $local) {
                $data = array_merge($data, $local);
            }
        }
        foreach ($this->packageSources as $packageSource) {
            if ($packageSource->toIndex($objectType, $objectId, $initialData)) {
                $local = $packageSource->getData($objectType, $objectId, $typeFactory, $initialData);

                if (false !== $local) {
                    $data = array_merge($data, $local);
                }
            }
        }

        $base = [
            'object_type' => $typeFactory->identifier($objectType),
            'object_id' => $typeFactory->identifier($objectId),
            'contents' => $typeFactory->plainmediumtext($this->getGlobalContent($data, $globalFields)),
        ];
        //var_dump($globalFields);
        $data = array_merge(array_filter($data), $base);
        $data = $this->applyFilters($data);

        $data = $this->removeTemporaryKeys($data);

        return $data;
    }

    private function applyFilters($data)
    {
        $keys = array_keys($data);

        foreach ($keys as $key) {
            $value = $data[$key];

            if (is_callable([$value, 'filter'])) {
                $data[$key] = $value->filter($this->contentFilters);
            }
        }

        return $data;
    }

    private function removeTemporaryKeys($data)
    {
        $keys = array_keys($data);
        $toRemove = array_filter(
            $keys,
            function ($key) {
                return $key[0] === '_';
            }
        );

        foreach ($keys as $key) {
            if ($key[0] === '_') {
                unset($data[$key]);
            }
        }

        return $data;
    }

    /** This generates the 'contents' key in the final search.  That key has slightly conlicting requirements:
     * 1- Server as teh objects textual, human readable summary
     * 2- Be fuzzy-searchable from PluginList as the main content of the object
     *
     * So, fields should not repeat, and title must be present, but ideally at the end (since it will already be displayed in list right above or besided the summary)
     */
    public static function getGlobalContent(array &$data, $globalFields): string
    {
        $content = '';

        foreach ($globalFields as $name => $preserve) {
            if (isset($data[$name])) {
                $v = $data[$name]->getValue();
                if (is_string($v)) {
                    $content .= $v . ' ';

                    if (! $preserve) {
                        $data[$name] = false;
                    }
                }
            }
        }

        return $content;
    }

    private function getGlobalFields($objectType)
    {
        if (is_null($this->cacheGlobals)) {
            $this->cacheGlobals = [];
            foreach ($this->globalSources as $source) {
                $this->cacheGlobals = array_merge($this->cacheGlobals, $source->getGlobalFields());
            }
        }

        if (! isset($this->cacheTypes[$objectType])) {
            $this->cacheTypes[$objectType] = array_merge($this->cacheGlobals, $this->contentSources[$objectType]->getGlobalFields());
        }

        return $this->cacheTypes[$objectType];
    }

    private function log($message)
    {
        $this->log->info($message, ['memoryUsage' => \Symfony\Component\Console\Helper\Helper::formatMemory(memory_get_usage()),
                                    'memoryAvail' => \Symfony\Component\Console\Helper\Helper::formatMemory(TikiLib::lib('tiki')->get_memory_avail())]);
    }
}
