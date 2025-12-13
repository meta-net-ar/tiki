<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
include_once dirname(__DIR__) . '/entities/AccessTokenEntity.php';

use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use Tiki\Lib\Auth\ApiTokenException;

class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity)
    {
        try {
            $token = TikiLib::lib('api_token')->createToken([
                'type' => 'oauth_access',
                'token' => $accessTokenEntity->getIdentifier(),
                'label' => 'OAuth client ' . $accessTokenEntity->getClient()->getIdentifier(),
                'user' => $accessTokenEntity->getUserIdentifier() ?? $accessTokenEntity->getClient()->getUser(),
                'expireAfter' => $accessTokenEntity->getExpiryDateTime()->getTimestamp(),
                'parameters' => json_encode([
                    'user'   => $accessTokenEntity->getUserIdentifier(),
                    'client' => $accessTokenEntity->getClient()->getIdentifier(),
                    'scopes' => $accessTokenEntity->getScopes(),
                ]),
            ]);
        } catch (ApiTokenException $e) {
            throw new UniqueTokenIdentifierConstraintViolationException($e->getMessage());
        }

        $accessTokenEntity->setIdentifier($token['token']);
        return $accessTokenEntity;
    }

    public function revokeAccessToken($token)
    {
        TikiLib::lib('api_token')->deleteToken($token);
        return $this;
    }

    public function isAccessTokenRevoked($token)
    {
        return ! TikiLib::lib('api_token')->validToken($token);
    }

    public function get($token)
    {
        $token = TikiLib::lib('api_token')->getToken($token);
        if (empty($token)) {
            return null;
        }

        $parameters = json_decode($token['parameters'], true);

        $client_repo = new ClientRepository(TikiDb::get());
        $client = $client_repo->get($parameters['client']);

        if (empty($client)) {
            return null;
        }

        $entity = new AccessTokenEntity();
        $entity->setIdentifier($token['token']);
        $entity->setExpiryDateTime(new \DateTime($token['expireAfter']));
        $entity->setUserIdentifier($token['user']);
        $entity->setClient($client);

        return $entity;
    }

    public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null)
    {
        $accessToken = new AccessTokenEntity();
        $accessToken->setClient($clientEntity);

        foreach ($scopes as $scope) {
            $accessToken->addScope($scope);
        }

        $accessToken->setUserIdentifier($userIdentifier);
        return $accessToken;
    }
}
