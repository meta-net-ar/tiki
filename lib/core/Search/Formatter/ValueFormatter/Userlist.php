<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_Formatter_ValueFormatter_Userlist extends Search_Formatter_ValueFormatter_Abstract
{
    private $separator;

    public function __construct($arguments)
    {
        if (isset($arguments['separator'])) {
            $this->separator = $arguments['separator'];
        }
    }

    public function render($name, $value, array $entry)
    {
        $userlib = TikiLib::lib('user');

        if (! is_array($value)) {
            // use intelligent split as this list might contain real names of type Last, First
            $value = explode(', ', $value);
            for ($i = 0, $l = count($value); $i < $l; $i++) {
                $val = $value[$i];
                if (! $userlib->user_exists($val) && isset($value[$i + 1])) {
                    $val .= ', ' . $value[$i + 1];
                    if ($userlib->user_exists($val) || $userlib->user_exists_by_realname($val)) {
                        $value[$i] = $val;
                        unset($value[$i + 1]);
                        $i++;
                    }
                }
            }
        }

        if ($this->separator) {
            return implode($this->separator, $value);
        } else {
            return implode(', ', $value);
        }
    }
}
