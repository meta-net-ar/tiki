<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\MailIn\Source;

use ZBateson\MailMimeParser\MailMimeParser;

/**
 * @deprecated This class is deprecated and will be removed after Tiki30LTS.
 * POP3 access is no longer supported in Tiki.
 */
class Pop3 implements SourceInterface
{
    protected $host;
    protected $port;
    protected $username;
    protected $password;
    protected $ssl;
    protected $novalidatecert;
    private $mailMimeParser;

    public function __construct($host, $port, $username, $password, $ssl = false, $novalidatecert = false)
    {
        trigger_error(__CLASS__ . ' is deprecated and will be removed after Tiki30LTS. POP3 access is no longer supported in Tiki.', E_USER_DEPRECATED);
        $this->host = $host;
        $this->port = (int) $port;
        $this->username = $username;
        $this->password = $password;
        $this->ssl = $ssl;
        $this->novalidatecert = $novalidatecert;

        $this->mailMimeParser = new MailMimeParser();
    }

    public function test()
    {
        trigger_error(__CLASS__ . ' is deprecated and no longer functional.', E_USER_DEPRECATED);
        return false;
    }

    public function getMessages()
    {
        trigger_error(__CLASS__ . ' is deprecated and no longer functional. getMessages() will return no results.', E_USER_DEPRECATED);
    }

    protected function connect()
    {
        trigger_error(__CLASS__ . ' is deprecated and connect() is no longer supported.', E_USER_DEPRECATED);
    }

    /**
     * @param $message Message
     * @param $zbMessage
     */
    private function handleAttachments($message, $zbMessage)
    {
        trigger_error(__CLASS__ . ' is deprecated and attachment handling is no longer functional.', E_USER_DEPRECATED);
    }
}
