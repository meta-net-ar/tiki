<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace TikiLib\Core\Services\Exception;

class SocnetsTokenNotFoundException extends \Services_Exception
{
    public function __construct(string $providerName)
    {
        parent::__construct(tr('You need to connect your %0 account first.', $providerName), 401);
    }
}
