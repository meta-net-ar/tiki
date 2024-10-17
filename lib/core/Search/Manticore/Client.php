<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Manticore;

use Manticoresearch\Exceptions\ExceptionInterface as ManticoreException;
use Tiki\Profiling\DatabaseQueryLog;

class Client
{
    public const UNIFIED_MANTICORE_READ_LOG_GROUP = "Unified index Manticore: Read";
    public const UNIFIED_MANTICORE_WRITE_LOG_GROUP = "Unified index Manticore: Write";
    public const UNIFIED_MANTICORE_UNKNOWN_LOG_GROUP = "Unified index Manticore: Read or Write";
    public $index;
    protected $dsn;
    protected $client;

    public function __construct($dsn, $port)
    {
        $this->dsn = rtrim($dsn, '/');

        $parsed = parse_url($this->dsn);
        if ($parsed === false) {
            throw new Exception(tr("Malformed Manticore connection url: %0", $this->dsn));
        }

        $config = [
            'transport' => $parsed['scheme'] == 'https' ? 'Https' : 'Http',
            'scheme' => $parsed['scheme'],
            'host' => $parsed['host'],
            'port' => $port,
            'timeout' => 10,
            'connection_timeout' => 5,
        ];
        try {
            $this->client = new \Manticoresearch\Client($config, new \Tiki_Log('Manticore', \Psr\Log\LogLevel::WARNING));
        } catch (ManticoreException $e) {
            throw new ClientException($e);
        }
    }

    public function getStatus()
    {
        $logHandle = DatabaseQueryLog::logStart('SHOW STATUS', null, self::UNIFIED_MANTICORE_READ_LOG_GROUP);
        try {
            $status = ['status' => 0];
            $result = $this->client->sql([
                'mode' => 'raw',
                'body' => [
                    'query' => 'SHOW STATUS',
                ],
            ]);
            if (! empty($result['data'])) {
                $result = $result['data'];
            }
            if (is_array($result)) {
                foreach ($result as $row) {
                    $status[$row['Counter']] = $row['Value'];
                }
            }
            return $status;
        } catch (ManticoreException $e) {
            return [
                'status' => 0,
                'error' => $e->getMessage(),
            ];
        } finally {
            DatabaseQueryLog::logEnd($logHandle);
        }
    }

    public function getVersion()
    {
        $status = $this->getStatus();
        return $status['version'] ?? 0;
    }

    public function getIndexStatus($index = '')
    {
        $logHandle = DatabaseQueryLog::logStart('status()', null, self::UNIFIED_MANTICORE_READ_LOG_GROUP);
        try {
            $index = $this->client->index($index);
            return $index->status();
        } catch (ManticoreException $e) {
            throw new ClientException($e);
        } finally {
            DatabaseQueryLog::logEnd($logHandle);
        }
    }

    public function createIndex(string $index, array $definition, array $settings = [], $silent = false)
    {
        $logHandle = DatabaseQueryLog::logStart('create()', array_merge([$index], $definition), self::UNIFIED_MANTICORE_WRITE_LOG_GROUP);
        try {
            $index = $this->client->index($index);
            $response = $index->create($definition, $settings, $silent);
            if (! empty($response['error'])) {
                throw new Exception($response['error']);
            }
        } catch (ManticoreException $e) {
            throw new ClientException($e);
        } finally {
            DatabaseQueryLog::logEnd($logHandle);
        }
    }

    public function deleteIndex(string $index)
    {
        $logHandle = DatabaseQueryLog::logStart('drop()', [$index], self::UNIFIED_MANTICORE_WRITE_LOG_GROUP);
        try {
            $index = $this->client->index($index);
            return $index->drop(true);
        } catch (ManticoreException $e) {
            throw new ClientException($e);
        } finally {
            DatabaseQueryLog::logEnd($logHandle);
        }
    }

    public function describe($index)
    {
        $logHandle = DatabaseQueryLog::logStart('describe()', [$index], self::UNIFIED_MANTICORE_READ_LOG_GROUP);
        try {
            $index = $this->client->index($index);
            $result = $index->describe();
        } catch (ManticoreException $e) {
            $result = [
                'error' => $e->getMessage()
            ];
        } finally {
            DatabaseQueryLog::logEnd($logHandle);
        }

        if (empty($result['error']) && ! empty($result['data'])) {
            $mapping = [];
            foreach ($result['data'] as $row) {
                $mapping[$row['Field']] = [
                    'type' => $row['Type'],
                    'options' => explode(' ', $row['Properties'])
                ];
            }
            return $mapping;
        } else {
            return [];
        }
    }

    public function alter(string $index, string $operation, string $field, string $type)
    {
        $logHandle = DatabaseQueryLog::logStart('alter()', [$index, $operation, $field, $type], self::UNIFIED_MANTICORE_WRITE_LOG_GROUP);
        try {
            $index = $this->client->index($index);
            return $index->alter($operation, $field, $type);
        } catch (ManticoreException $e) {
            throw new ClientException($e);
        } finally {
            DatabaseQueryLog::logEnd($logHandle);
        }
    }

    public function search(string $index, string $query)
    {
        $logHandle = DatabaseQueryLog::logStart('search()', [$index, $query], self::UNIFIED_MANTICORE_READ_LOG_GROUP);
        try {
            $index = $this->client->index($index);
            return $index->search($query);
        } catch (ManticoreException $e) {
            throw new ClientException($e);
        } finally {
            DatabaseQueryLog::logEnd($logHandle);
        }
    }

    public function storeQuery(string $index, string $query, string $name)
    {
        $logHandle = DatabaseQueryLog::logStart('pq()', [$index, $query, $name], self::UNIFIED_MANTICORE_WRITE_LOG_GROUP);
        try {
            $res = $this->client->pq()->doc([
                'index' => $index . 'pq',
                'body' => [
                    'query' => $query['query'],
                    'tags' => [$name],
                ]
            ]);
            return $res;
        } catch (ManticoreException $e) {
            throw new ClientException($e);
        } finally {
            DatabaseQueryLog::logEnd($logHandle);
        }
    }

    public function index(string $index, array $data)
    {
        $logHandle = DatabaseQueryLog::logStart('addDocument()', array_merge([$index], $data), self::UNIFIED_MANTICORE_WRITE_LOG_GROUP);
        try {
            $index = $this->client->index($index);
            return $index->addDocument($data);
        } catch (ManticoreException $e) {
            throw new ClientException($e);
        } finally {
            DatabaseQueryLog::logEnd($logHandle);
        }
    }

    public function unindex(string $index, string $type, $id)
    {
        $logHandle = DatabaseQueryLog::logStart('deleteDocuments()', [$index, $type, $id], self::UNIFIED_MANTICORE_WRITE_LOG_GROUP);
        try {
            $index = $this->client->index($index);
            return $index->deleteDocuments([
                'object_type' => $type,
                'object_id' => $id,
            ]);
        } catch (ManticoreException $e) {
            throw new ClientException($e);
        } finally {
            DatabaseQueryLog::logEnd($logHandle);
        }
    }

    public function document(string $index, string $type, $id)
    {
        $logHandle = DatabaseQueryLog::logStart('search()', [$index, $type, $id], self::UNIFIED_MANTICORE_READ_LOG_GROUP);
        try {
            $params = [
                'body' => [
                    'index' => $this->index,
                    'query' => [
                        'equals' => [
                            'object_type' => $type,
                            'object_id' => $id,
                        ]
                    ]
                ]
            ];
            $result = new ResultSet($this->client->search($params, true));
            return $result->current();
        } catch (ManticoreException $e) {
            throw new ClientException($e);
        } finally {
            DatabaseQueryLog::logEnd($logHandle);
        }
    }

    public function getIndex($index)
    {
        return $this->client->index($index);
    }

    public function getClient()
    {
        return $this->client;
    }
}
