<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace TikiLib\Core\Services\Exception;

class SocnetsApiException extends \Services_Exception
{
    public function __construct(string $message, int $code = 502, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
