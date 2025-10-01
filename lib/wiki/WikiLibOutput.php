<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Wiki;

use TikiLib;

class WikiLibOutput
{
    public $info;
    public $originalValue;
    public $parsedValue;
    public $options;

    public function __construct($info, $originalValue, $options = [])
    {
        //TODO: info may have an override, we need to build it in using MYSQL
        $this->info = $info;
        $this->originalValue = $originalValue;
        $this->options = $options;

        $this->parsedValue = TikiLib::lib('parser')->parse_data($this->originalValue, $this->options = $options);
    }
}
