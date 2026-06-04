<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\TwoFactorAuth\Exception;

use Exception as BaseException;

class Exception extends BaseException
{
    public function __construct(string $message, ?BaseException $previous = null, int $code = 0)
    {
        parent::__construct($message, $code, $previous);
    }
}
