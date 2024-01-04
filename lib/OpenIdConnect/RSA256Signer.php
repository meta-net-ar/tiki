<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\OpenIdConnect;

use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Signer;

class RSA256Signer implements Signer
{
    private $sha256;

    public function __construct()
    {
        $this->sha256 = new Sha256();
    }

    /**
     * {@inheritdoc}
     */
    public function verify($expected, $payload, $key): bool
    {
        if (is_array($key->contents())) {
            return ! empty(
                array_filter(
                    $key->contents(),
                    function ($content) use ($expected, $payload) {
                        $this->sha256->verify($expected, $payload, $content);
                    }
                )
            );
        }

        return $this->sha256->verify($expected, $payload, $key);
    }

    public function algorithmId(): string
    {
        return $this->sha256->algorithmId();
    }

    public function sign($payload, $key): string
    {
        return $this->sha256->sign($payload, $key);
    }
}
