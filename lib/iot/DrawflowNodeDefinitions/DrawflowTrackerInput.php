<?php

namespace Tiki\Lib\iot\DrawflowNodeDefinitions;

use Tiki\Lib\iot\DrawflowNodeType;
use Tiki\Lib\iot\DrawflowActionInterface;

class DrawflowTrackerInput implements DrawflowActionInterface
{
    public $n_input = 0;
    public $n_output = 1;
    public $name;
    public $description;
    public $node_identifier = "sensor-input-node";
    public $node_df_identifier = "sensordatasource";

    public function __construct($app_uuid)
    {
        $this->name = tra("Sensor Input");
        $this->description = tra("Read input from configured sensor at given tracker field");
        $safe_uid = str_replace('-', '', $app_uuid);
        $this->node_df_identifier .= $safe_uid;
        $this->node_identifier .= $safe_uid;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getType(): DrawflowNodeType
    {
        return DrawflowNodeType::User_input;
    }

    public function getTemplate(array $config): string
    {
        $inputs = "<select df-{$this->node_df_identifier} class='text-center noselect2'>";
        $inputs .= '<option selected>' . tra('Please select a value') . '</option>';
        foreach ($config['tracker_fields'] as $key => $input_vals) {
            $inputs .= "<option value='{$input_vals['value']}'>{$input_vals['label']}</option>";
        }
        $inputs .= '</select>';
        return "
        <div class='{$this->node_identifier} draggable-node'>
            <div class='text-center'><span><span data-icon-name='right_to_bracket'></span>&nbsp;" . tra("SENSOR INPUT") . "</span></div>
            $inputs
        </div>
        <div class='draggable-node clone  bg-light px-2 py-1 border text-center w-100' data-inputs='{$this->n_input}' data-outputs='{$this->n_output}' data-for='{$this->node_identifier}'>
            <div class='node-mask' title='" . htmlentities($this->description, ENT_QUOTES) . "'>
                <span><span data-icon-name='right_to_bracket'></span>&nbsp;" . $this->name . "</span>
            </div>
        </div>";
    }

    public function execute(mixed $input, mixed $user_input): bool | array
    {
        return ['success' => true, 'message' => tra('Got input : ') . json_encode($input)  , 'next_payload' => $input];
    }
}
