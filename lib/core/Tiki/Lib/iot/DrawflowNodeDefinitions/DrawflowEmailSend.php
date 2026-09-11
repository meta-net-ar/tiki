<?php

namespace Tiki\Lib\iot\DrawflowNodeDefinitions;

use Exception;
use Tiki\Lib\iot\DrawflowNodeType;
use Tiki\Lib\iot\DrawflowActionInterface;

class DrawflowEmailSend implements DrawflowActionInterface
{
    public $n_input = 1;
    public $n_output = 1;
    public $name;
    public $description;
    public $node_identifier = "email-send";
    public $node_df_identifier = "emailsend";

    public function __construct($app_uuid)
    {
        $this->name = tra("Email Send");
        $this->description = tra("Send an email to the provided address");
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
            <div class='text-center'><span><span data-icon-name='envelope'></span>&nbsp;" . tra("Email Send") . "</span></div>
            <input type='email' df-{$this->node_df_identifier} class='form-control' placeholder='" . tra("Enter email address") . "'>
        </div>
        <div class='draggable-node clone  bg-light px-2 py-1 border text-center w-100' data-inputs='{$this->n_input}' data-outputs='{$this->n_output}' data-for='{$this->node_identifier}'>
            <div class='node-mask' title='" . htmlentities($this->description, ENT_QUOTES) . "'>
                <span><span data-icon-name='envelope'></span>&nbsp;" . $this->name . "</span>
            </div>
        </div>";
    }

    public function execute(mixed $input, mixed $user_input): bool | array
    {
        if (! $input['success']) {
            $input['message'] = tra('Email not scheduled to be sent, previous condition did not pass');
            return $input;
        }
        try {
            global $prefs;
            $email_text = is_array($input) ? $input['next_payload'] : $input;
            $email_to = $user_input;
            $email_subject = $prefs['browsertitle'] . "| " . tra('IoT Email notification');
            $mail = new \TikiMail();
            $from = $prefs['sender_email'];
            $mail->setFrom($from, $prefs['browsertitle'] . " - " . tra("IoT Email notification"));
            $mail->setText($email_text);
            $mail->setSubject($email_subject);
            if (! $mail->send([$email_to])) {
                throw new Exception(tra("Email can't be sent from %0 to %1 please contact the administrator", [$from,$email_to]));
            }
            $input['message'] = tra('Email scheduled to be sent:') . json_encode($user_input);
            $input['success'] = true;
        } catch (\Exception $e) {
            $input['message'] = tra('Couldn\'t send email:') . $e->getMessage();
            $input['success'] = false;
        }
        return $input;
    }
}
