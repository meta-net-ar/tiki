<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
include_once dirname(__DIR__) . '/entities/RefreshTokenEntity.php';

use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use Tiki\Lib\Auth\ApiTokenException;

class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    public function getNewRefreshToken()
    {
        return new RefreshTokenEntity();
    }

    public function isRefreshTokenRevoked($tokenId)
    {
        return ! TikiLib::lib('api_token')->validToken($tokenId);
    }

    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity)
    {
        try {
            $accessTokenEntity = $refreshTokenEntity->getAccessToken();
            $token = TikiLib::lib('api_token')->createToken([
                'type' => 'oauth_refresh',
                'token' => $refreshTokenEntity->getIdentifier(),
                'label' => 'OAuth client ' . $accessTokenEntity->getClient()->getIdentifier(),
                'user' => $accessTokenEntity->getUserIdentifier(),
                'expireAfter' => $refreshTokenEntity->getExpiryDateTime()->getTimestamp(),
            ]);
        } catch (ApiTokenException $e) {
            throw new UniqueTokenIdentifierConstraintViolationException($e->getMessage());
        }

        $refreshTokenEntity->setIdentifier($token['token']);
        return $refreshTokenEntity;
    }

    public function revokeRefreshToken($tokenId)
    {
        TikiLib::lib('api_token')->deleteToken($tokenId);
        return $this;
    }
}
