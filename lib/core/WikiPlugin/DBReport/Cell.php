<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\WikiPlugin\DBReport;

class Cell
{
    public $link;
    public $style;
    public $colspan;
    public $rowspan;
    public $contents;

    public function code($mode)
    {
        $result = 'CELL';
        if (isset($this->colspan) && isset($this->rowspan)) {
            if ($this->rowspan != 1) {
                $result .= ' ROWSPAN ' . $this->rowspan;
            }
            if ($this->colspan != 1) {
                $result .= ' COLSPAN ' . $this->colspan;
            }
        } elseif (isset($this->colspan)) {
            if ($this->colspan != 1) {
                if ($mode == 'ROW') {
                    $result .= ' SPAN ' . $this->colspan;
                } else {
                    $result .= ' COLSPAN ' . $this->colspan;
                }
            }
        } elseif (isset($this->rowspan)) {
            if ($this->rowspan != 1) {
                if ($mode == 'ROW') {
                    $result .= ' ROWSPAN ' . $this->rowspan;
                } else {
                    $result .= ' SPAN ' . $this->rowspan;
                }
            }
        }
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
        if ($heading) {
            $html = '<th';
        } else {
            $html = '<td';
        }
        if (isset($this->style)) {
            $html .= $this->style->attributes();
        }
        if (isset($this->rowspan)) {
            $html .= ' rowspan="' . $this->rowspan . '"';
        }
        if (isset($this->colspan)) {
            $html .= ' colspan="' . $this->colspan . '"';
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
        $html .= '</td>';
        return $html;
    }
}
