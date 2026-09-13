<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Auth;

use TikiLib;

/**
 * ApiToken library for access and modification of API tokens and OAuth tokens
 *
 * @uses TikiLib
 */
class ApiToken extends TikiLib
{
    private const TOKEN_BYTES = 32;
    private const VERIFIER_PREFIX = 'sha256:';

    private $table;

    public function __construct()
    {
        parent::__construct();
        $this->table = $this->table('tiki_api_tokens');
    }

    public function getTokens($conditions = [])
    {
        $tokens = $this->table->fetchAll([], $conditions, -1, -1, ['tokenId' => 'asc']);

        return array_map([$this, 'removeVerifier'], $tokens);
    }

    public function getToken($tokenId)
    {
        if (is_numeric($tokenId)) {
            $token = $this->table->fetchFullRow(['tokenId' => (int) $tokenId]);
        } else {
            $token = $this->findTokenByValue($tokenId);
        }

        return $token ? $this->removeVerifier($token) : $token;
    }

    public function createToken($token)
    {
        $this->table->deleteMultiple([
            'type' => $this->table->expr("$$ != 'manual'"),
            'expireAfter' => $this->table->expr("$$ < NOW()")
        ]);
        $tokenValue = (string) ($token['token'] ?? '');
        if ($tokenValue === '') {
            $tokenValue = $this->generate();
        }

        if (str_starts_with($tokenValue, self::VERIFIER_PREFIX)) {
            throw new ApiTokenException(tr('Access token uses a reserved prefix.'));
        }

        if ($this->findTokenByValue($tokenValue)) {
            throw new ApiTokenException(tr('Access token already exists.'));
        }
        $token['token'] = $this->createVerifier($tokenValue);
        $token['created'] = $this->now;
        $token['lastModif'] = $this->now;
        $tokenId = $this->table->insert($token);
        $createdToken = $this->table->fetchFullRow(['tokenId' => $tokenId]);
        $createdToken['token'] = $tokenValue;

        return $createdToken;
    }

    public function updateToken($tokenId, $token)
    {
        // Tokens are immutable. Rotating a token means creating a replacement.
        unset($token['token']);
        $token['lastModif'] = $this->now;
        $this->table->update($token, ['tokenId' => $tokenId]);
        return $this->getToken($tokenId);
    }

    public function deleteToken($tokenId)
    {
        if (is_numeric($tokenId)) {
            return $this->table->delete(['tokenId' => $tokenId]);
        }

        $token = $this->findTokenByValue($tokenId);
        if (! $token) {
            return false;
        }

        return $this->table->delete(['tokenId' => $token['tokenId']]);
    }

    public function deleteAllTokens()
    {
        return $this->table->deleteMultiple([]);
    }

    public function validToken($token)
    {
        $token = $this->findTokenByValue($token);
        if (! $token) {
            return false;
        }
        if (! empty($token['expireAfter']) && $token['expireAfter'] < $this->now) {
            return false;
        }
        return $this->removeVerifier($token);
    }

    public function hit($token)
    {
        $this->table->update(['hits' => $token['hits'] + 1], ['tokenId' => $token['tokenId']]);
    }

    private function generate()
    {
        return bin2hex(random_bytes(self::TOKEN_BYTES));
    }

    private function createVerifier(string $token): string
    {
        return self::VERIFIER_PREFIX . hash('sha256', $token);
    }

    /**
     * Find a token by its bearer value, without ever accepting the stored
     * verifier as a bearer value.
     *
     * All stored tokens are hashed verifiers: the installer patch
     * (20260621_hash_api_tokens) converts any pre-upgrade plaintext tokens, and
     * createToken() only ever stores verifiers, so a plaintext lookup is no
     * longer needed.
     */
    private function findTokenByValue(string $token)
    {
        if ($token === '' || str_starts_with($token, self::VERIFIER_PREFIX)) {
            return false;
        }

        $row = $this->table->fetchFullRow(['token' => $this->createVerifier($token)]);

        return $row ?: false;
    }

    private function removeVerifier(array $token): array
    {
        unset($token['token']);

        return $token;
    }
}
