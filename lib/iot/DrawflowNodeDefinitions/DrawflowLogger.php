<?php

namespace Tiki\Lib\iot\DrawflowNodeDefinitions;

use Tiki\Lib\iot\DrawflowNodeType;
use Tiki\Lib\iot\DrawflowActionInterface;

class DrawflowLogger implements DrawflowActionInterface
{
    public $n_input = 1;
    public $n_output = 1;
    public $name;
    public $description;
    public $node_identifier = "file-logger";
    public $node_df_identifier = "filelogger";

    public function __construct($app_uuid)
    {
        $this->name = tra("File logger");
        $this->description = tra("Log input to a file");
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
            <div class='text-center'><span data-icon-name='file_pen'></span>&nbsp;" . tra("File logger") . "</div>
            <textarea class='form-control' rows='4' df-{$this->node_df_identifier}>Logging input: ==input==</textarea>
        </div>
        <div class='draggable-node clone  bg-light px-2 py-1 border text-center w-100' data-inputs='{$this->n_input}' data-outputs='{$this->n_output}' data-for='{$this->node_identifier}'>
            <div class='node-mask' title='" . htmlentities($this->description, ENT_QUOTES) . "'>
                <span><span data-icon-name='file_pen'></span>&nbsp;" . $this->name . "</span>
            </div>
        </div>";
    }

    public function execute(mixed $input, mixed $user_input): bool | array
    {
        // Implement file logging logic here
        // For demonstration, returning a simple success response
        if (! $input["success"]) {
            $input['message'] = tra("File logging skipped, previous condition did not pass");
            return $input;
        }
        $in = is_array($input) ? $input['next_payload'] : $input;
        $content = str_replace("==input==", $in, $user_input);

        $dir = realpath(__DIR__ . '/../../../');
        $path = $dir . DIRECTORY_SEPARATOR . TEMP_PATH . DIRECTORY_SEPARATOR . $this->getFileName();
        if (file_put_contents($path, $content)) {
            $input['success'] = true;
            return ['success' => true, 'message' => tra('Logs succesfully written : ') . json_encode([realpath($path),$in]), 'next_payload' => $input];
        }
        $input['success'] = false;
        $input['message'] = tra('Failed to write logs: ') . json_encode([__DIR__ . '/../../../../',$dir, $path, $in]);
        return $input;
    }

    public function getFileName(): string
    {
        $timestamp = time();
        $formattedDateTime = date("Y-m-d-H-i-s", $timestamp);
        $fileName = 'IoT-' . $formattedDateTime . '-' . $timestamp . '.txt';
        return $fileName;
    }
}
