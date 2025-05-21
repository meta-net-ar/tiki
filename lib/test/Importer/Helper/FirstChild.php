<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Importer\Helper;

use TikiImporter;

// dummy classes to test the TikiImporter::getOptions()

class FirstChild extends TikiImporter
{
    public static function importOptions(): array
    {
        return [
            ['name' => 'someName', 'property1' => 'someProperty'],
            ['name' => 'differentName', 'property' => 'anotherProperty']
        ];
    }
}
