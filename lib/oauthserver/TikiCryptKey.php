<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Lcobucci\JWT\Signer\Key;
use League\OAuth2\Server\CryptKey;

class TikiCryptKey extends CryptKey
{
    protected $key;
    public function __construct($key, $passPhrase = null, $keyPermissionsCheck = true)
    {
        $this->key = $key;
        $this->keyContents = $key;
    }

    public function getKeyPath()
    {
        return $this->key; //This used to be new Key($this->key), which is impossible, Lcobucci\JWT\Signer\Key is an interface.  Returned $this->key as per League\OAuth2\Server\CryptKey base implementation, but it doesn't mean that it works.  benoitg - 2026-02-16.
    }

    public function isNullKey()
    {
        return is_null($this->key);
    }
}
