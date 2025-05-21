<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\WikiPlugin\DBReport;

class Parameter extends Content
{
    public $name;
    public function code($indent = '')
    {
        $result = $indent . 'PARAM';
        // if (isset($this->name)) $result .= ' :'.$this->name;
        if (isset($this->elements)) {
            foreach ($this->elements as $element) {
                $result .= ' ' . $element->code();
            }
        }
        $result .= "\n";
        // $result .= ' "' . parent::code() . "\"\n";
        return $result;
    }
}
