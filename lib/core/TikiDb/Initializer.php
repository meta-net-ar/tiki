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

    private function getInitializer()
    {
        $connector = new TikiDb_Initializer_Pdo();
        if ($connector->isSupported()) {
            return $connector;
        }
    }
}
