<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\TwoFactorAuth\Exception;

class TwoFactorAuthException extends Exception
{
    public function __construct(string $message, ?Exception $previous = null, int $code = 0)
    {
        parent::__construct($message, $previous, $code);
    }
}
