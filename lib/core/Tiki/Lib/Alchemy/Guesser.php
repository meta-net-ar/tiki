<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Alchemy;

class Guesser implements \Symfony\Component\Mime\MimeTypeGuesserInterface
{
    private $fileMimeTypes = [];

    public function isGuesserSupported(): bool
    {
        return true;
    }

    public function add($filePath, $mimeType)
    {
        $this->fileMimeTypes[$filePath] = $mimeType;
    }

    /**
     * @inheritdoc
     */
    public function guessMimeType($path): ?string
    {
        if (array_key_exists($path, $this->fileMimeTypes)) {
            return $this->fileMimeTypes[$path];
        }

        return null;
    }
}
