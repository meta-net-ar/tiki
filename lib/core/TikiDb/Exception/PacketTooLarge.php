<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\TikiDb\Exception;

use Exception;

/**
 * A query bigger than max_allowed_packet makes the server drop the connection, which the rest
 * of the request cannot recover from, so such a query is refused before being sent.
 */
class PacketTooLarge extends Exception
{
    public function __construct(int $length, int $maxAllowedPacket)
    {
        parent::__construct(tr(
            'The data is too large to be sent to the database server: %0 bytes are needed and the server accepts %1 bytes at most (max_allowed_packet).',
            $length,
            $maxAllowedPacket
        ));
    }
}
