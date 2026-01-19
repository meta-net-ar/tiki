<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\FileGallery;

class FileLink extends File
{
    public $param;

    public function __construct()
    {
        $this->param = array_merge(
            parent::getParams(),
            [
                'objectId' => 0,
                'objectType' => '',
            ]
        );
        parent::__construct();
    }
}
