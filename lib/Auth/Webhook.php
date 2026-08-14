<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Auth;

use TikiLib;

/**
 * Webhook library for access and modification of webhook configuration
 *
 * @uses TikiLib
 */
class Webhook extends TikiLib
{
    private $table;
    public $verification_types = ['base64 hmac', 'hmac'];

    public function __construct()
    {
        parent::__construct();
        $this->table = $this->table('tiki_webhooks');
    }

    public function getWebhooks($conditions = [])
    {
        return $this->table->fetchAll([], $conditions, -1, -1, ['webhookId' => 'asc']);
    }

    public function getWebhook($webhookId)
    {
        return $this->table->fetchFullRow(['webhookId' => (int) $webhookId]);
    }

    public function createWebhook($data)
    {
        $data['created'] = $this->now;
        $data['lastModif'] = $this->now;
        $webhookId = $this->table->insert($data);
        return $this->getWebhook($webhookId);
    }

    public function updateWebhook($webhookId, $data)
    {
        $data['lastModif'] = $this->now;
        $this->table->update($data, ['webhookId' => $webhookId]);
        return $this->getWebhook($webhookId);
    }

    public function deleteWebhook($webhookId)
    {
        return $this->table->delete(['webhookId' => $webhookId]);
    }

    public function verify($webhook)
    {
        $rawData = file_get_contents("php://input");
        $headers = getallheaders();
        $received = $headers[$webhook['signatureHeader']] ?? '';
        switch ($webhook['verification']) {
            case 'hmac':
                $computed = hash_hmac('sha256', $rawData, $webhook['secret']);
                break;
            case 'base64 hmac':
                $computed = base64_encode(hash_hmac('sha256', $rawData, $webhook['secret'], true));
                break;
            default:
                $computed = '';
        }
        if (empty($received) || empty($computed) || $received !== $computed) {
            return false;
        } else {
            return true;
        }
    }
}
