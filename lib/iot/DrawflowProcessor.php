<?php

declare(strict_types=1);

namespace Tiki\Lib\iot;

use Exception;
use Tiki\Lib\iot\DrawflowNodeType;

class DrawflowProcessor
{
    public array $drawflowData = [];
    public array $nodeMap = [];
    public array $nodes = [];
    public array $adjacencyList = [];
    public array $rootNodes = [];
    public array $graph = [];
    public array $data_input = [];
    public array $nodeDefinitions = [];
    public string $app_uuid;
    public array $outputs_track = [];
    public static array $app_flow_logs = [];

    public function __construct(string $drawflowJson, array $data_input, string $app_uuid)
    {
        $this->app_uuid = $app_uuid;
        $this->data_input = $data_input;
        $this->initializeDrawflowData($drawflowJson);
        $this->nodeDefinitions = $this->loadNodeDefinitions();
    }

    private function initializeDrawflowData(string $drawflowJson): void
    {
        $data = json_decode($drawflowJson, true)['drawflow']['Home']['data'] ?? [];
        if (empty($data)) {
            $this->nodes = [];
            return;
        }

        $this->nodes = $data;
    }

    private function loadNodeDefinitions(): array
    {
        $definitions = [];
        $nodeDirectory = __DIR__ . '/DrawflowNodeDefinitions';
        if (! is_dir($nodeDirectory)) {
            throw new Exception("Node directory missing: $nodeDirectory");
        }
        foreach (glob($nodeDirectory . '/Drawflow*.php') as $file) {
            $className = pathinfo($file, PATHINFO_FILENAME);
            $fullClass = __NAMESPACE__ . "\\DrawflowNodeDefinitions\\$className";
            if (class_exists($fullClass)) {
                $instance = new $fullClass($this->app_uuid);
                $definitions[$instance->node_identifier] = $instance;
            }
        }
        return $definitions;
    }

    public function buildConnectionGraph(): self
    {
        $this->nodeMap = [];
        $this->adjacencyList = [];

        foreach ($this->nodes as $nodeId => $node) {
            $this->nodeMap[$nodeId] = [
                'id' => $node['id'] ?? null,
                'name' => $node['name'] ?? 'Unnamed Node',
                'type' => $node['class'] ?? 'generic',
                'data' => $node['data'] ?? [],
                'action' => $node['action'] ?? null,
                'isStartNode' => (bool)($node['isStartNode'] ?? false),
                'isVisited' => false
            ];

            $this->adjacencyList[$nodeId] = [];
            foreach ($node['outputs'] ?? [] as $output) {
                foreach ($output['connections'] ?? [] as $connection) {
                    if ($targetNode = $connection['node'] ?? null) {
                        $this->adjacencyList[$nodeId][] = $targetNode;
                    }
                }
            }
        }
        $this->findRootNodes();
        return $this;
    }

    private function findRootNodes(): void
    {
        $allTargets = array_merge(...array_values($this->adjacencyList));
        $this->rootNodes = array_diff(
            array_keys($this->adjacencyList),
            array_unique($allTargets)
        );
    }

    public function executeFlow(): array
    {
        $results = [];
        $visited = [];

        foreach ($this->rootNodes as $nodeId) {
            $results[] = $this->processNode($nodeId, $visited, $this->data_input);
        }

        return [
            'success' => ! empty($results),
            'logs' => self::$app_flow_logs,
            'outputs' => array_filter($results)
        ];
    }

    private function processNode(string $nodeId, array &$visited, array $input): ?array
    {
        if (isset($visited[$nodeId])) {
            return null;
        }

        $visited[$nodeId] = true;
        $node = $this->getNodeHandler($nodeId);
        $result = [];

        try {
            $nodeData = $this->nodes[$nodeId]['data'] ?? [];
            $result = $node->execute($input, $nodeData);
            $this->logExecution(
                $nodeId,
                $result['message'] ?? 'Executed without message',
                $result['success'] ?? false
            );

            foreach ($this->adjacencyList[$nodeId] as $targetId) {
                $this->processNode($targetId, $visited, $result);
            }
        } catch (Exception $e) {
            $this->logExecution($nodeId, "ERROR: " . $e->getMessage(), false);
            $result = ['success' => false, 'message' => $e->getMessage()];
        }

        return $result;
    }

    private function getNodeHandler(string $nodeId): DrawflowActionInterface
    {
        $nodeType = $this->nodeMap[$nodeId]['name'] ?? '';
        if (! isset($this->nodeDefinitions[$nodeType])) {
            throw new Exception("Undefined node type: $nodeType");
        }
        return $this->nodeDefinitions[$nodeType];
    }

    private function logExecution(string $nodeId, string $message, bool $success): void
    {
        $logMessage = sprintf(
            "[%s][%s] %s",
            date('Y-m-d H:i:s'),
            $success ? 'SUCCESS' : 'ERROR',
            $message
        );
        \TikiDb::get()->query(
            "INSERT INTO tiki_iot_apps_actions_logs 
            (app_uuid, node_id, action_message, success) 
            VALUES (?, ?, ?, ?)",
            [$this->app_uuid, $nodeId, $logMessage, (int)$success]
        );
        self::$app_flow_logs[] = $logMessage;
    }
}
