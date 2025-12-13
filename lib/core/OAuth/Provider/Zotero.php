<?php

namespace Tiki\OAuth\Provider;

use League\OAuth1\Client\Server\Server;
use League\OAuth1\Client\Server\User;

class Zotero extends Server
{
    public $urlUserDetails = 'https://api.zotero.org/users/me';

    /**
     * Constructor required by League\OAuth1\Client\Server\Server
     */
    public function __construct(array $clientCredentials)
    {
        parent::__construct($clientCredentials);
    }

    public function urlUserDetails()
    {
        return $this->urlUserDetails;
    }

    public function userUid($data, \League\OAuth1\Client\Credentials\TokenCredentials $tokenCredentials)
    {
        return $data['userID'] ?? null;
    }

    public function userEmail($data, \League\OAuth1\Client\Credentials\TokenCredentials $tokenCredentials)
    {
        return $data['email'] ?? null;
    }

    public function userScreenName($data, \League\OAuth1\Client\Credentials\TokenCredentials $tokenCredentials)
    {
        return $data['username'] ?? null;
    }

    public function urlTemporaryCredentials()
    {
        return 'https://www.zotero.org/oauth/request';
    }

    public function urlAuthorization()
    {
        return 'https://www.zotero.org/oauth/authorize';
    }

    public function urlTokenCredentials()
    {
        return 'https://www.zotero.org/oauth/access';
    }

    public function userDetails($data, $tokenCredentials)
    {
        $user = new User();
        $user->uid = $data['userID'] ?? null;
        $user->name = $data['username'] ?? null;
        $user->extra = $data;
        return $user;
    }
}
