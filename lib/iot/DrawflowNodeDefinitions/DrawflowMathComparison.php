<?php

namespace Tiki\Lib\iot\DrawflowNodeDefinitions;

use Exception;
use Tiki\Lib\iot\DrawflowNodeType;
use Tiki\Lib\iot\DrawflowActionInterface;
use Math_Formula_Runner;

class DrawflowMathComparison implements DrawflowActionInterface
{
    public $n_input = 1;
    public $n_output = 1;
    public $name;
    public $description;
    public $node_identifier = "math-comparison";
    public $node_df_identifier = "mathcomparison";

    public function __construct($app_uuid)
    {
        $this->name = tra("Math comparison");
        $this->description = tra("Compare input value with your math expression, compatible with Tiki Calculations syntax");
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
        return "
        <div class='{$this->node_identifier} draggable-node'>
            <div class='text-center'><span><span data-icon-name='calculator'></span>&nbsp;" . tra("Math comparison") . "</span></div>
            <textarea class='form-control' rows='4' df-{$this->node_df_identifier}>(if(or (less-than \$input 220) (more-than \$input 235) ) 1 0)</textarea>
        </div>
        <div class='draggable-node clone  bg-light px-2 py-1 border text-center w-100' data-inputs='{$this->n_input}' data-outputs='{$this->n_output}' data-for='{$this->node_identifier}'>
            <div class='node-mask' title='" . htmlentities($this->description, ENT_QUOTES) . "'>
                <span><span data-icon-name='calculator'></span>&nbsp;" . $this->name . "</span>
            </div>
        </div>";
    }

    public function execute(mixed $input, mixed $user_input): bool | array
    {
        if (! $input['success']) {
            $input['message'] = tra('Math comparison skipped, previous condition did not succeed');
            return $input;
        }
        // the runner dont have reset method, so i believe it is safe to create a new instance each time
        $runner = new Math_Formula_Runner(
            [
            'Math_Formula_Function_' => '',
            'Tiki_Formula_Function_' => '',
            ]
        );
        $inputValue = is_array($input) ? $input['next_payload'] : $input; //inputValue is value that come from previous node
        $result = false;
        try {
            $runner->setFormula($user_input);
            $runner->setVariables(['$input' => $inputValue]);
            $result = (string)$runner->evaluate();
        } catch (Exception $e) {
            $result = false;
            $input['message'] = tra('Error processing your math condition :') . json_encode($user_input) . tra('. The expression error: ') . $e->getMessage();
            $input['success'] = false;
            return $input;
        }
        if ((bool) $result) {
            $input['message'] = tra('Condition passed:') . json_encode($user_input);
            $input['success'] = true;
        } else {
            $input['message'] = tra('Condition did not pass:') . json_encode($user_input); //return false to stop execution
            $input['success'] = false;
        }
        return $input;
    }
}
