<?php

namespace Tiki\Lib\iot;

use Exception;
use Tiki\Lib\iot\DrawflowNodeType;

/**
 * TODO Fix the processor for flow with complex paths & node inter connexions
 * Known limitations: the class is unable to process graph correctly when one node feed it's output to more than one node'input
 * One to one flat connexions are working fine plus connections nested right at the parent node
 * Class can also process graph with multiple route nodes
 * Same applies to the js processor
*/
class DrawflowProcessor
{
    public $drawflowJson = [];
    public $nodeMap = [];
    public $nodes;
    public $adjacencyList = [];
    public $rootNodes;
    public $graph = [];
    public array $data_input = [];
    public array $node_definition_instances = [];
    public string $app_uuid;
    public array $outputs_track = [];
    public static $app_flow_logs = [];

    public function __construct(string $drawflowJson, array $data_input, string $app_uuid)
    {
        $drawflowJson = json_decode($drawflowJson, true);
        // TODO L drawflowJson can be empty and we should handle it as a valid case (empty flow)
        if (! $drawflowJson || ! isset($drawflowJson['drawflow']) || ! isset($drawflowJson['drawflow']['Home']) || ! isset($drawflowJson['drawflow']['Home']['data'])) {
            throw new Exception(tra("Invalid Drawflow JSON provided."));
        }

        $this->drawflowJson = $drawflowJson;
        $this->nodeMap = [];
        $this->nodes = $drawflowJson['drawflow']['Home']['data'];
        $this->adjacencyList = [];
        $this->rootNodes = null;
        $this->graph = [];
        $this->data_input = $data_input;
        $this->app_uuid = $app_uuid;
        $this->node_definition_instances = self::loadNodeDefinitions($this->app_uuid);
        return $this;
    }

    public static function loadNodeDefinitions($app_uuid): array
    {
        $nodeDefinitions = [];
        $nodeDirectory = __DIR__ . '/DrawflowNodeDefinitions';
        $nodeFiles = glob($nodeDirectory . '/Drawflow*.php');

        foreach ($nodeFiles as $nodeFile) {
            $className = pathinfo($nodeFile, PATHINFO_FILENAME);
            $fullClassName = __NAMESPACE__ . '\\DrawflowNodeDefinitions\\' . $className;
            if (class_exists($fullClassName)) {
                $node_instance = new $fullClassName($app_uuid);
                $nodeDefinitions[$node_instance->node_identifier] = $node_instance;
            }
        }

        return $nodeDefinitions;
    }

    public function getAdjacentList()
    {
        foreach ($this->nodes as $nodeId => $node) {
            $this->nodeMap[$nodeId] = [
            'id' => $node['id'],
            'name' => $node['name'],
            'type' => $node['class'],
            'data' => $node['data'],
            'action' => isset($node['action']) ? $node['action'] : null,
            'isStartNode' => ! ! $node['isStartNode'],
            'isVisited' => false,
            ];
        }

        foreach ($this->nodes as $nodeId => $node) {
            $this->adjacencyList[$nodeId] = [];

            if (isset($node['outputs'])) {
                foreach ($node['outputs'] as $outputName => $output) {
                    $connections = $output['connections'];

                    foreach ($connections as $connection) {
                        $targetNodeId = $connection['node'];
                        $this->adjacencyList[$nodeId][] = $targetNodeId;
                    }
                }
            }
        }

        return $this;
    }

    public function getRootNodes()
    {
        $this->rootNodes = array_diff(array_keys($this->adjacencyList), array_merge(...array_values($this->adjacencyList)));

        if (count($this->rootNodes) == 0) {
            throw new Exception(tra("Wrong diagram, no valid starting point found"));
        }

        return $this;
    }

    public function checkBadRouting()
    {
        $flatAdjacentList = array_merge(...array_values($this->adjacencyList));
        $duplicates = array_unique(array_diff_assoc($flatAdjacentList, array_unique($flatAdjacentList)));
        if (count($duplicates) > 0) {
            throw new Exception(tra("You have some single inputs nodes paired with multiple output"), json_encode($duplicates, JSON_PRETTY_PRINT));
        }
        return $this;
    }

    private function buildNodeQueue($nodeIds, $mainObject)
    {
        $outputObject = [];

        foreach ($nodeIds as $nodeId) {
            $outputObject[$nodeId] = [];
            $this->buildConnections($nodeId, $outputObject[$nodeId], $mainObject);
        }

        return $outputObject;
    }

    private function buildConnections($nodeId, &$connectionsObject, $mainObject)
    {
        $connectedNodes = isset($mainObject[$nodeId]) ? $mainObject[$nodeId] : [];

        foreach ($connectedNodes as $connectedNodeId) {
            $connectionsObject[$connectedNodeId] = [];
            $this->buildConnections($connectedNodeId, $connectionsObject[$connectedNodeId], $mainObject);
        }
    }

    public function buildQueue()
    {
        foreach ($this->rootNodes as $val) {
            $direct_nodes = $this->adjacencyList[$val];
            $this->graph[$val] = [];
            $node_children = [];
            foreach ($direct_nodes as $node_id) {
                $this->graph[$val][$node_id] = $this->buildNodeQueue($this->adjacencyList[$node_id], $this->adjacencyList);
            }
        }

        return $this;
    }

    public function traverseNode($nodeValue, $currentTopLevelNodeId, $drawflowJson, $inter_node_value)
    {
        foreach ($nodeValue as $node_id => $value) {
            //DrawflowProcessor::logExecutions("Current Root Node " . $currentTopLevelNodeId . "\n");
            //DrawflowProcessor::logExecutions("Current Node ID " . $node_id);
            $name = $drawflowJson['drawflow']['Home']['data'][$node_id]['name'];
            $parent_node_id = $this->getParentNodeId($node_id);
            $in_value = $parent_node_id ? $this->outputs_track[$parent_node_id] : null;
            $inter_node_value = $in_value ? $in_value : $inter_node_value; //last parent out will takeover if it exist
            $node = $this->node_definition_instances[$name];
            $data = $drawflowJson['drawflow']['Home']['data'][$node_id]['data'];
            $html = $drawflowJson['drawflow']['Home']['data'][$node_id]['html'];
            $node_type = $node->getType();
            if ($node_type == DrawflowNodeType::Template) {
                $user_flow_data = array_values($data)[0];
                $inter_node_value = $node->execute($inter_node_value, $user_flow_data);
            }
            if ($node_type == DrawflowNodeType::User_choice) {
                $inter_node_value = $node->execute($inter_node_value, $html);
            }
            if ($node_type == DrawflowNodeType::User_input) {
                $user_flow_data = array_values($data)[0];
                $inter_node_value = $node->execute($inter_node_value, $user_flow_data);
            }
            $this->outputs_track[$node_id] = $inter_node_value; //we save the output
            /* we will do this the day our node will start supporting multipe inputs/outputs for now it's either 0-1,1-1,1-0
            foreach ($data as $source => $input_field_binded) {
                $value_from_input = $input[$input_field_binded] ?? null;
                $data[$source] = $value_from_input;
            }*/
            //DrawflowProcessor::logExecutions(json_encode([$name, $data, $html]));
            DrawflowProcessor::logExecutions((string) $inter_node_value['message'], $this->app_uuid);
            if (count($value) > 0) {
                $this->traverseNode($value, $currentTopLevelNodeId, $drawflowJson, $inter_node_value);
            } else {
                //DrawflowProcessor::logExecutions("Current Root Node " . $currentTopLevelNodeId . "\n");
                //DrawflowProcessor::logExecutions("End of path");
                //DrawflowProcessor::logExecutions("\n");
            }
        }
    }
    public function traverseGraph()
    {
        $graph = $this->graph;
        $visited = [];

        foreach ($this->graph as $topLevelNodeId => $graphValue) {
            $input = $this->data_input; //to be assigned everytime we visit a new root node
            DrawflowProcessor::logExecutions("Root Node " . $topLevelNodeId, $this->app_uuid);
            $name = $this->drawflowJson['drawflow']['Home']['data'][$topLevelNodeId]['name'];
            $data = $this->drawflowJson['drawflow']['Home']['data'][$topLevelNodeId]['data'];
            $html = $this->drawflowJson['drawflow']['Home']['data'][$topLevelNodeId]['html'];
            $node = $this->node_definition_instances[$name];
            $source_fields = array_values($data);
            $path_input = $node->execute($input[$source_fields[0]], ""); //we get only the first input because our logic is still limited
            $this->outputs_track[$topLevelNodeId] = $path_input; //also save output of toplevel node
            DrawflowProcessor::logExecutions(json_encode([$name, $data, $html]), $this->app_uuid);
            $this->traverseNode($this->graph[$topLevelNodeId], $topLevelNodeId, $this->drawflowJson, $path_input);
            DrawflowProcessor::logExecutions("End of full direction", $this->app_uuid);
        }

        return $this;
    }
    public function getParentNodeId(int $id): int | null
    {
        foreach ($this->adjacencyList as $parentNode => $children) {
            if (in_array($id, $children)) {
                return $parentNode;
            }
        }
        return null; // If no parent found
    }
    public static function logExecutions(string $text, string $app_uuid)
    {
        \TikiDb::get()->query("INSERT INTO `tiki_iot_apps_actions_logs` (`app_uuid`,`action_message`) VALUES(?,?)", [$app_uuid,$text]);
        self::$app_flow_logs[] = $text;
    }
}
