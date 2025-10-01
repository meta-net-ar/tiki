<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\WikiPlugin\DBReport;

class Strings
{
    public $literal;

    public function __construct($text)
    {
        $this->literal = stripcslashes($text);
    }

    public function text()
    {
        return $this->literal;
    }

    public function code()
    {
        return addcslashes($this->literal, "\0..\37[]\\");
    }

    public function html()
    {
        return htmlentities($this->text(), ENT_COMPAT);
    }

    public function uri()
    {
        return $this->text();
    }
}
