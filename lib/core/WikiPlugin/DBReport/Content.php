<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\WikiPlugin\DBReport;

class Content
{
    public $elements;

    public function parse_text(&$text)
    {
        $parse_state = 0;
        $parse_text = '';
        $pos = 0;
        $len = strlen($text);
        while ($pos < $len) {
            $char = $text[$pos++];
            switch ($parse_state) {
                case 0: // start of next token
                    switch ($char) {
                        case '[':
                            $parse_state = 3;
                            break;
                        case '\\':
                            $parse_state = 2;
                            $parse_text .= $char;
                            break;
                        default:
                            $parse_state = 1;
                            $parse_text .= $char;
                    }
                    break;
                case 1: // text string
                    switch ($char) {
                        case '[':
                            unset($this->elements);
                            $this->elements[] = new Strings($parse_text);
                            $parse_text = '';
                            $parse_state = 3;
                            break;
                        case '\\':
                            $parse_state = 2;
                            $parse_text .= $char;
                            break;
                        default:
                            $parse_text .= $char;
                    }
                    break;
                case 2: // literal escape
                    $parse_text .= $char;
                    $parse_state = 1;
                    break;
                case 3: // field text
                    switch ($char) {
                        case '[':
                            break;
                        case ']':
                            unset($this->elements);
                            $this->elements[] = new Field($parse_text);
                            $parse_text = '';
                            $parse_state = 0;
                            break;
                        case '\\':
                            $parse_state = 4;
                            $parse_text .= $char;
                            break;
                        default:
                            $parse_text .= $char;
                    }
                    break;
                case 4: // field escape
                    $parse_text .= $char;
                    $parse_state = 3;
                    break;
            }
        }
        // hanging text is parsed as a string
        if ($parse_state != 0) {
            unset($this->elements);
            $this->elements[] = new Strings($parse_text);
        }
    }

    public function append_field($name)
    {
        unset($this->elements);
        $this->elements[] = new Field($name);
    }

    public function append_variable($name)
    {
        $this->elements[] = new Field('$' . $name);
    }

    public function append_string($text)
    {
        $this->elements[] = new Strings($text);
    }

    public function append($text)
    {
        $this->parse_text($text);
    }

    public function __construct(&$token)
    {
        switch ($token->type) {
            case 'txt':
                $this->parse_text($token->content);
                break;
            case 'fld':
                $this->append_field($token->content);
                break;
            case 'var':
                $this->append_variable($token->content);
                break;
        }
    }

    public function text()
    {
        $result = '';
        if (isset($this->elements)) {
            foreach ($this->elements as $element) {
                $result .= $element->text();
            }
        }
        return $result;
    }

    public function code()
    {
        $result = '';
        if (isset($this->elements)) {
            foreach ($this->elements as $element) {
                $result .= $element->code();
            }
        }
        return $result;
    }

    public function html()
    {
        $result = '';
        if (isset($this->elements)) {
            foreach ($this->elements as $element) {
                $result .= $element->html();
            }
        }
        return $result;
    }

    public function uri()
    {
        $result = '';
        if (isset($this->elements)) {
            foreach ($this->elements as $element) {
                $result .= $element->uri();
            }
        }
        return $result;
    }
}
