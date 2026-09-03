<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Services_Exception_MissingValue extends Services_Exception_FieldError
{
    public function __construct($field)
    {
        $message = (defined('TIKI_API') && TIKI_API) ? tr('Field Required') : tr('%0 is required.', $field);
        parent::__construct($field, $message);
    }
}
