<?php

namespace Tiki\Lib\iot\DrawflowNodeDefinitions;

use Tiki\Lib\iot\DrawflowNodeType;
use Tiki\Lib\iot\DrawflowActionInterface;

class DrawflowStringTemplateBuilder implements DrawflowActionInterface
{
    public $n_input = 1;
    public $n_output = 1;
    public $name;
    public $description;
    public $node_identifier = "string-template-builder";
    public $node_df_identifier = "stringtemplate";

    public function __construct($app_uuid)
    {
        $this->name = tra("String template");
        $this->description = tra("Read input from previous node and construct text from it");
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
        return DrawflowNodeType::Template;
    }

    public function getTemplate(array $config): string
    {
        return "
        <div class='{$this->node_identifier} draggable-node'>
            <div class='text-center'><span data-icon-name='pen'></span>&nbsp;" . tra("String template") . "</div>
            <textarea class='form-control' rows='4' df-{$this->node_df_identifier}>" . tra("Admin, we have got ==input== from the sensor. Thanks! (from tiki iot)") . "</textarea>
        </div>
        <div class='draggable-node clone  bg-light px-2 py-1 border text-center w-100' data-inputs='{$this->n_input}' data-outputs='{$this->n_output}' data-for='{$this->node_identifier}'>
            <div class='node-mask' title='" . htmlentities($this->description, ENT_QUOTES) . "'>
                <span><span data-icon-name='pen'></span>&nbsp;{$this->name}</span>
            </div>
        </div>";
    }

    public function execute(mixed $input, mixed $user_input): bool | array
    {
        if (! $input['success']) {
            $input['message'] = tra('String template builder skipped, previous condition operation did not succeed');
            return $input;
        }
        $in = is_array($input) ? $input['next_payload'] : $input;
        return ['success' => true, 'message' => tra('Email template built: ') . json_encode($user_input)  , 'next_payload' => str_replace("==input==", $in, $user_input)];
    }
}
