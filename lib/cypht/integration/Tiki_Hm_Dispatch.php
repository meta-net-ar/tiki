<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Tiki_Hm_Dispatch extends Hm_Dispatch
{
    private bool $renderHomePage;

    public function __construct($config, $renderHomePage)
    {
        $this->renderHomePage = $renderHomePage;
        parent::__construct($config);
    }

    public function get_page($filters, $request)
    {
        if ($this->renderHomePage) {
            $request->get['page'] = 'home';
        }
        return parent::get_page($filters, $request);
    }
}
