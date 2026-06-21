<?php

namespace Tiki\Lib\iot\DrawflowNodeDefinitions;

use Exception;
use Tiki\Lib\iot\DrawflowNodeType;
use Tiki\Lib\iot\DrawflowActionInterface;

class DrawflowWebhookSend implements DrawflowActionInterface
{
    public $n_input = 1;
    public $n_output = 1;
    public $name;
    public $description;
    public $node_identifier = "webhook-send";
    public $node_df_identifier = "webhooksend";

    public function __construct($app_uuid)
    {
        $this->name = tra("Webhook Send");
        $this->description = tra("Send data to a webhook");
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
            <div class='text-center'><span><span data-icon-name='paper_plane'></span>&nbsp;" . tra('Webhook send') . "</span></div>
            <input type='text' df-{$this->node_df_identifier} class='form-control' placeholder='" . tra('Enter webhook URL') . "'>
        </div>
        <div class='draggable-node clone  bg-light px-2 py-1 border text-center w-100' data-inputs='{$this->n_input}' data-outputs='{$this->n_output}' data-for='{$this->node_identifier}'>
            <div class='node-mask' title='" . htmlentities($this->description, ENT_QUOTES) . "'>
                <span><span data-icon-name='paper_plane'></span>&nbsp;" . $this->name . "</span>
            </div>
        </div>";
    }

    public function execute(mixed $input, mixed $user_input): bool | array
    {
        if (! $input['success']) {
            $input['message'] = tra('Webhook call skipped, previous condition did not pass');
            return $input;
        }
        try {
            $tikilib = \TikiLib::lib('tiki');
            $data = is_array($input) ? $input['next_payload'] : $input;
            $client = $tikilib->get_http_client($user_input);
            $client->setMethod(\Laminas\Http\Request::METHOD_POST);
            $client->setRawBody(json_encode($data));
            $client->setOptions([
                'maxredirects' => 0,
                'timeout'      => 5,
            ]);
            $headers = $client->getRequest()->getHeaders();
            $headers->addHeader(new \Laminas\Http\Header\ContentType('application/json'));
            $client->setHeaders($headers);
            $response = $client->send();
            $body = $response->getBody();
            if (! $response->isSuccess()) {
                $body = json_decode($body);
                $error = $body->error->message;
                throw new Exception(tr('Remote service inaccessible (%0), error: %1', $response->getStatusCode(), $error));
            }
            $input['message'] = tra('Data sent to your webhook, webhook response: ') . json_encode($body);
            $input['success'] = true;
        } catch (Exception $e) {
            $input['message'] = $e->getMessage();
            $input['success'] = false;
        }

        return $input;
    }
}
