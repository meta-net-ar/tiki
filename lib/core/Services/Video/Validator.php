<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Services\Video;

class Validator
{
    public const MIN_TITLE_LENGTH = 3;
    public const MAX_TITLE_LENGTH = 120; // Common limit (e.g. PeerTube is 120)
    public const MIN_DESC_LENGTH = 3;

    /**
     * Validates video metadata (name and description).
     *
     * @param string $name
     * @param string $description
     * @return array List of error messages (translated)
     */
    public static function validateMetadata($name, $description)
    {
        $errors = [];

        if (mb_strlen($name) < self::MIN_TITLE_LENGTH || mb_strlen($name) > self::MAX_TITLE_LENGTH) {
            $errors[] = tr('Title must be between %0 and %1 characters.', self::MIN_TITLE_LENGTH, self::MAX_TITLE_LENGTH);
        }

        if ($description !== '' && mb_strlen($description) < self::MIN_DESC_LENGTH) {
            $errors[] = tr('Description must be at least %0 characters, or leave it empty.', self::MIN_DESC_LENGTH);
        }

        return $errors;
    }
}
