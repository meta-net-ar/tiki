<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\MailIn\Source;

use TikiLib;

class Message
{
    private $id;
    private $deleteCallback;

    private $messageId;
    private $from;
    private $recipient;
    private $subject;
    private $body;
    private $htmlBody;
    private $content;
    private $attachments = [];

    private $associatedUser;

    public function __construct($id, $deleteCallback)
    {
        $this->id = $id;
        $this->deleteCallback = $deleteCallback;
    }

    public function getMessageId()
    {
        return $this->messageId;
    }

    public function setMessageId($messageId)
    {
        $this->messageId = $messageId;
    }

    public function setRawFrom($from_email)
    {
        $this->from = $from_email;
        $this->associatedUser = TikiLib::lib('user')->get_user_by_email($from_email);
    }

    public function getFromAddress()
    {
        return $this->from;
    }

    public function setAssociatedUser($user)
    {
        $this->associatedUser = $user;
    }

    public function getAssociatedUser()
    {
        return $this->associatedUser;
    }

    public function delete()
    {
        if ($this->deleteCallback) {
            $callback = $this->deleteCallback;
            $callback();
            $this->deleteCallback = null;
        }
    }

    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function setBody($body)
    {
        $this->body = $body;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function setHtmlBody($body)
    {
        $this->htmlBody = $body;
    }

    public function getHtmlBody($fallback = true)
    {
        if ($fallback) {
            return $this->htmlBody ?: $this->body;
        } else {
            return $this->htmlBody;
        }
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function addAttachment($contentId, $name, $type, $size, $data)
    {
        $this->attachments[$contentId] = [
            'contentId' => $contentId,
            'name' => $name,
            'type' => $type,
            'size' => $size,
            'data' => $data,
            'link' => null,
        ];
    }

    public function setLink($contentId, $link)
    {
        if (isset($this->attachments[$contentId])) {
            $this->attachments[$contentId]['link'] = $link;
        }
    }

    public function getAttachments()
    {
        return array_values($this->attachments);
    }

    public function getAttachment($contentId)
    {
        if (isset($this->attachments[$contentId])) {
            return $this->attachments[$contentId];
        }
    }

    public function getRecipient()
    {
        return $this->recipient;
    }

    public function setRecipient($recipient_address)
    {
        $this->recipient = $recipient_address;
    }

    public function getRecipientAddress()
    {
        return $this->recipient;
    }
}
