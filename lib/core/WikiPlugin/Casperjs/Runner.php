<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\HeadlessBrowser\Casperjs;

class WikiPlugin_Casperjs_Runner
{
    private $casperjs;

    public function __construct()
    {
        $this->casperjs = new Casperjs();
    }

    public function run($script, $options = [], $casperInstance = null)
    {
        try {
            return $this->casperjs->executeCasperJs($script, $options, $casperInstance);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
