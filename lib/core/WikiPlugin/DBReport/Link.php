<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\WikiPlugin\DBReport;

class Link
{
    public $style;
    public $contents;

    public function code()
    {
        $result = '<';
        if (isset($this->contents)) {
            foreach ($this->contents as $content) {
                if (is_a($content, Content::class)) {
                    $result .= '"' . $content->code() . '"';
                } elseif (is_a($content, Field::class)) {
                    $result .= $content->code();
                }
            }
        }
        if (isset($this->style)) {
            $result .= $this->style->code();
        }
        $result .= '>';
        return $result;
    }

    public function uri()
    {
        if (isset($this->contents)) {
            foreach ($this->contents as $content) {
                if (is_a($content, Content::class)) {
                    $uri .= $content->html();
                } elseif (is_a($content, Field::class)) {
                    $uri .= $content->uri();
                }
            }
        }
        return $uri;
    }

    public function html_start()
    {
        $html = '<a href="' . $this->uri() . '"';
        if ($this->style) {
            $html .= $this->style->attributes();
        }
        $html .= '>';
        return $html;
    }

    public function html_end()
    {
        return '</a>';
    }

    public function html_onclick()
    {
        return 'onclick="document.location.href=&quot;' . $this->uri() . '&quot;"';
    }
}
