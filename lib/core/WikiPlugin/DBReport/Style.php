<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\WikiPlugin\DBReport;

class Style
{
    public $tag;
    public $class;
    public $style;

    public function __construct(&$token)
    {
        if (is_object($token)) {
            if ($token->content['class'] ?? '') {
                $subtoken =& $token->content['class'];
                unset($this->class);
                $this->class = new Content($subtoken);
            }
            if ($token->content['style'] ?? '') {
                $subtoken =& $token->content['style'];
                unset($this->style);
                $this->style = new Content($subtoken);
            }
        } elseif (is_string($token)) {
            unset($subtoken);
            $subtoken = new Token($token);
            $subtoken->type = 'txt';
            unset($this->class);
            $this->class = new Content($subtoken);
        }
    }

    public function code()
    {
        $code = ':';
        if (isset($this->class)) {
            $code .= addcslashes($this->class->code(), ' ');
        } elseif ($this->tag != 'span') {
            $code .= $this->tag;
        }
        // if (isset($this->class)) $code .= $this->class->code();
        if (isset($this->style)) {
            $code .= "{" . $this->style->code() . "}";
        }
        return $code;
    }

    public function attributes()
    {
        if (isset($this->class)) {
            $html .= ' class="' . $this->class->html() . '"';
        }
        if (isset($this->style)) {
            $html .= ' style="' . $this->style->html() . '"';
        }
        return $html;
    }

    public function html_start()
    {
        if (isset($this->class)) {
            $class = $this->class->html();
            switch (strtolower($class)) {
                case 'u':
                case 'b':
                case 'i':
                case 'h1':
                case 'h2':
                case 'h3':
                case 'h4':
                case 'h5':
                case 'h6':
                    $this->tag = $class;
                    $html = '<' . $class;
                    break;
                default:
                    $this->tag = 'span';
                    $html = '<span class="' . $class . '"';
            }
        } else {
            $this->tag = 'span';
            $html = '<span';
        }
        if (isset($this->style)) {
            $html .= ' style="' . $this->style->html() . '"';
        }
        $html .= '>';
        return $html;
    }

    public function html_end()
    {
        return '</' . $this->tag . '>';
    }
}
