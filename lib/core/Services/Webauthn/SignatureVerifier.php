<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Services\Webauthn;

use phpseclib3\Crypt\EC\PublicKey;
use phpseclib3\Crypt\PublicKeyLoader;

class SignatureVerifier
{
    /**
     * Verify signature using phpseclib.
     *
     * @param string $dataToVerify
     * @param string $signature
     * @param string $publicKey
     * @return bool
     * @throws \Exception
     */
    public static function verify(string $dataToVerify, string $signature, string $publicKey): bool
    {
        try {
            $key = PublicKeyLoader::load($publicKey);
            if (! $key instanceof PublicKey) {
                $errMsg = tr('Failed to verify signature due to invalid EC public key.');
                throw new \Exception($errMsg);
            }
            return $key->withHash('sha256')->verify($dataToVerify, $signature);
        } catch (\Exception $e) {
            $errMsg = tr('Failed to verify signature: %0', $e->getMessage());
            throw new \Exception($errMsg);
        }
    }
}
