<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\WikiPlugin\DBReport;

class Fail
{
    public $link;
    public $style;
    public $contents;

    public function code($mode)
    {
        $result = 'FAIL';
        if (isset($this->style)) {
            $result .= ' ' . $this->style->code();
        }
        if (isset($this->link)) {
            $result .= ' ' . $this->link->code();
        }
        if (isset($this->contents)) {
            foreach ($this->contents as $content) {
                $result .= ' ' . $content->code();
            }
        }
        return $result;
    }

    public function html($heading = false)
    {
        $html = '<div';
        if (isset($this->style)) {
            $html .= $this->style->attributes();
        }
        if (isset($this->link)) {
            $html .= ' ' . $this->link->html_onclick();
        }
        $html .= '>';
        if (isset($this->contents)) {
            foreach ($this->contents as $content) {
                $html .= $content->html();
            }
        }
        $html .= '</div>';
        return $html;
    }
}
