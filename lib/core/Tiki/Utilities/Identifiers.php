<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Utilities;

class Identifiers
{
    /**
     * This will return a unique hash for each different http request to php,
     * including keepalive requests.
     * If called multiple times, it will return the same string for the
     * duration of the request.
     *
     * Appending a sequence number to this is one way
     * you can generate unique html id's that will still
     * be unique if the html is included in another page
     * using in an ajax request
     *
     * @return string An 8 character long alphanumeric string
     */
    public static function getHttpRequestId(): string
    {
        $values = [];
        $values[] = $_SERVER['REMOTE_ADDR'] ?? '';//May not exist for console or unit tests
        $values[] = $_SERVER['REQUEST_TIME_FLOAT'] ?? '';
        $values[] = $_SERVER['REMOTE_PORT'] ?? '';
        $uniqueid = hash('crc32b', implode('', $values));
        return $uniqueid;
    }
}
