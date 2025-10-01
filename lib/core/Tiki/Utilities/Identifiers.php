<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Utilities;

use Exception;
use Ramsey\Uuid\Uuid;

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

    /**
     * This will return a universally unique identifiers (UUIDs)
     * leveraging ramsey/uuid library.
     *
     * @param string $version The UUIDs version to generate
     * @return string A UUIDs string of $version version (4 or 7)
     * @throws Exception When the given version is not 4 or 7
     */
    public static function generateUUID(int $version = 4): string
    {
        switch ($version) {
            case 4:
                return Uuid::uuid4()->toString();
            case 7:
                return Uuid::uuid7()->toString();
            default:
                throw new Exception(tr("UUID version %0 not supported by Tiki.\n Supported versions are 4 and 7.", $version));
        }
    }
}
