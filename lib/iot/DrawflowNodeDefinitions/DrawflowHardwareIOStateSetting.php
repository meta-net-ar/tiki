<?php

namespace Tiki\Lib\iot\DrawflowNodeDefinitions;

use Tiki\Lib\iot\DrawflowNodeType;
use Tiki\Lib\iot\DrawflowActionInterface;

class DrawflowHardwareIOStateSetting implements DrawflowActionInterface
{
    public $n_input = 1;
    public $n_output = 1;
    public $name;
    public $description;
    public $node_identifier = "hardware-io-state-setting-node";
    public $node_df_identifier = "hardwareiostatesetting";
    public $app_uuid;

    public function __construct($app_uuid)
    {
        $this->name = tra("Hardware I/O State Setting");
        $this->description = tra("Set the state of a hardware I/O (ON or OFF)");
        $safe_uid = str_replace('-', '', $app_uuid);
        $this->node_df_identifier .= $safe_uid;
        $this->node_identifier .= $safe_uid;
        $this->app_uuid = $app_uuid;
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
            <div class='text-center'><span><span data-icon-name='microchip'></span>&nbsp;" . tra("Hardware I/O State Setting") . "</span></div>
            <input type='text' df-{$this->node_df_identifier}-io-id class='form-control' placeholder='" . tra("Enter I/O ID (based on your hardware lib configuration)") . "'>
            <select df-{$this->node_df_identifier}-io-state class='noselect2'>
                <option value='ON'>" . tra('ON') . "</option>
                <option value='OFF'>" . tra('OFF') . "</option>
            </select>
        </div>
        <div class='draggable-node clone bg-light px-2 py-1 border text-center w-100' data-inputs='{$this->n_input}' data-outputs='{$this->n_output}' data-for='{$this->node_identifier}'>
            <div class='node-mask' title='" . htmlentities($this->description, ENT_QUOTES) . "'>
                <span><span data-icon-name='microchip'></span>&nbsp;" . $this->name . "</span>
            </div>
        </div>";
    }

    public function execute(mixed $input, mixed $user_input): bool | array
    {
        // Placeholder implementation for setting the state of the hardware I/O based on the selected ID and state
        if (! $input["success"] || empty($user_input['io'])) {
            $input['message'] = tra("IO status update skipped");
            return $input;
        }
        $io_id = $user_input['io']['id'];
        $io_state = $user_input['io']['state'];
        try {
            $current_state = \TikiDb::get()->getOne("SELECT state_object FROM `tiki_iot_apps` WHERE `app_uuid`=?", [$this->app_uuid], 'exception');
            $current_state_array = json_decode($current_state, true);
            if (empty($current_state_array[$io_id])) {
                $current_state_array[$io_id] = [];
            }
            $current_state_array[$io_id]['state'] = $io_state;
            $current_state_array[$io_id]['hardware_sync_done'] = false;
            \TikiDb::get()->query("UPDATE `tiki_iot_apps` SET `state_object`=? WHERE `app_uuid`=?", [json_encode($current_state_array),$this->app_uuid]);
            $input['message'] = tra('IO status scheduled to be changed: ') . $io_id . ' => ' . $io_state;
            $input['success'] = true;
        } catch (\Exception $e) {
            $input['message'] = tra("Couldn't update IO status: ") . $e->getMessage();
            $input['success'] = false;
        }
        return $input;
    }
}
