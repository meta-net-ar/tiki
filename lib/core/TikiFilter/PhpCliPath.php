<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\TikiFilter;

use Feedback;
use Laminas\Filter\FilterInterface;
use Tiki\Process\PhpExecutableFinder;

/**
 * Class PhpCliPath
 *
 * Filters for paths pointing to a valid PHP CLI binary
 */
class PhpCliPath implements FilterInterface
{
    /**
     * Accepts the path only if it points to a legitimate PHP CLI binary,
     * otherwise rejects the value and raises an error, so the preference
     * can never hold an arbitrary executable.
     *
     * @param mixed $input
     * @return string
     */
    public function filter($input)
    {
        $input = trim((string) $input);
        if ($input === '') {
            return '';
        }

        $finder = new PhpExecutableFinder();
        if ($finder->isValidPhpCliPath($input)) {
            return $input;
        }

        Feedback::error(tr('"%0" was rejected because it is not a valid PHP command line binary.', htmlspecialchars($input)));
        return '';
    }
}
