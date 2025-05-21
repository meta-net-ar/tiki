<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\WikiPlugin\DBReport;

class Text extends Content
{
    public $link;
    public $style;

    public function code()
    {
        $result = '"' . addcslashes(parent::code(), '"') . '"';
        if (isset($this->style)) {
            $result .= $this->style->code();
        }
        if (isset($this->link)) {
            $result .= ' ' . $this->link->code();
        }
        return $result;
    }

    public function html()
    {
        $html = parent::html();
        if (isset($this->style)) {
            $html = $this->style->html_start() . $html . $this->style->html_end();
        }
        if (isset($this->link)) {
            $html = $this->link->html_start() . $html . $this->link->html_end();
        }
        return $html;
    }
}
