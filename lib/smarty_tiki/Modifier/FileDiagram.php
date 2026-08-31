<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;

/**
 * Checks if a given file id is a diagram
 */
class FileDiagram implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'file_diagram';
    }

    public function handle($fileId)
    {
        return \Tiki\File\DiagramHelper::isDiagram($fileId);
    }
}
