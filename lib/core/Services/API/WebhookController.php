<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//this script may only be included - so its better to die if called directly.
if (str_contains($_SERVER['SCRIPT_NAME'], basename(__FILE__))) {
    header('location: index.php');
    exit;
}

class Services_API_WebhookController
{
    private $lib;

    public function setUp()
    {
        Services_Exception_Denied::checkGlobal('admin');
        $this->lib = TikiLib::lib('webhook');
    }

    public function action_list()
    {
        $webhooks = $this->lib->getWebhooks();
        return [
            'title' => '',
            'webhooks' => $webhooks,
        ];
    }

    public function action_new($input)
    {
        $webhook = [
            'verification' => 'base64 hmac',
            'algo' => 'sha256',
        ];
        return [
            'title' => tr('New Webhook Handler'),
            'webhook' => $webhook,
            'verification_types' => $this->lib->verification_types,
            'algos' => hash_hmac_algos(),
            'modal' => $input->modal->int(),
        ];
    }

    public function action_create($input)
    {
        $util = new Services_Utilities();
        if (! $util->isActionPost()) {
            throw new Services_Exception_Denied();
        }

        $webhook_data = $this->prepare_webhook_from_input($input);

        $webhook = $this->lib->createWebhook($webhook_data);

        Feedback::success(tr('New webhook successfully created.'));

        return $webhook;
    }

    public function action_edit($input)
    {
        $webhook = $this->lib->getWebhook($input->webhookId->int());
        return [
            'title' => tr('Edit Webhook Handler'),
            'webhook' => $webhook,
            'verification_types' => $this->lib->verification_types,
            'algos' => hash_hmac_algos(),
            'modal' => $input->modal->int(),
        ];
    }

    public function action_update($input)
    {
        $util = new Services_Utilities();
        if (! $util->isActionPost()) {
            throw new Services_Exception_Denied();
        }

        $webhook = $this->lib->getWebhook($input->webhookId->int());
        if (empty($webhook)) {
            throw new Services_Exception_NotFound();
        }

        $webhook_data = $this->prepare_webhook_from_input($input);

        $webhook = $this->lib->updateWebhook($webhook['webhookId'], $webhook_data);

        Feedback::success(tr('Webhook successfully updated.'));

        return $webhook;
    }

    public function action_delete($input)
    {
        $webhookId = $input->webhookId->int();
        $this->lib->deleteWebhook($webhookId);

        $access = TikiLib::lib('access');
        $access->redirect('tiki-admin.php?page=security');
    }

    private function prepare_webhook_from_input($input)
    {
        $users = TikiLib::lib('user')->extract_users($input->user->text(), false);
        $data = [
            'name' => $input->name->text(),
            'user' => $users[0] ?? null,
            'verification' => $input->verification->text(),
            'algo' => $input->algo->text(),
            'signatureHeader' => $input->signature_header->text(),
            'secret' => $input->secret->text(),
        ];
        if (empty($data['user'])) {
            throw new Services_Exception_FieldError('user', tr('User must be selected.'));
        }
        if (empty($data['name'])) {
            throw new Services_Exception_FieldError('user', tr('Name cannot be empty.'));
        }
        if (empty($data['signatureHeader'])) {
            throw new Services_Exception_FieldError('user', tr('Signature header cannot be empty.'));
        }
        return $data;
    }
}
