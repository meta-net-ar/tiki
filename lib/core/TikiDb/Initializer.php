<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class TikiDb_Initializer
{
    private $preferred;
    private $initializeCallback;

    public function setPreferredConnector()
    {
        $this->preferred = $this->getInitializer();
    }

    public function setInitializeCallback($callback)
    {
        $this->initializeCallback = $callback;
    }

    public function getConnection(array $credentials)
    {
        if ($connector = $this->getInitializer()) {
            return $this->initialize($connector, $credentials);
        }
    }

    private function initialize($connector, $credentials)
    {
        if ($db = $connector->getConnection($credentials)) {
            if ($callback = $this->initializeCallback) {
                $callback($db);
            }

            return $db;
        }
    }

    private function getInitializer(): TikiDb_Initializer_Pdo
    {
        $connector = new TikiDb_Initializer_Pdo();
        if ($connector->isSupported()) {
            return $connector;
        } else {
            header('HTTP/1.0 503 Service Unavailable', true, 503);
            echo tr("PDO connector isn't available, and is the only one supported by Tiki.  Check if the PDO php module and PHP mysql driver is available");
            /*
            Dying here isn't great, but previously no error was seen because
            tiki-db.php has a catch all error handler, and does not pass errors to the "Lost database connection" template.
            Nor do we get the actual error from lower in the code.
            And you cannot use tiki-check.php either if the mysql driver isn't available to php, so...
            TODO:  Refactor db error handling so the connection error is actually seen from tiki-install, tiki-check, and tiki in general.  - benoitg - 2026-01-20
            */
            die(1);
        }
    }
}
