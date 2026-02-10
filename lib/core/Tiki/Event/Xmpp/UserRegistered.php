<?php

namespace Tiki\Event\Xmpp;

use Symfony\Contracts\EventDispatcher\Event;

class UserRegistered extends Event
{
    public const NAME = 'tiki.xmpp.user.registered';

    public function __construct(
        private string $username,
        private array $groups
    ) {
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getGroups(): array
    {
        return $this->groups;
    }
}
