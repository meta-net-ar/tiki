<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Math_Formula_Function_Contains extends Math_Formula_Function
{
    public function evaluate($element)
    {
        $reference = $this->evaluateChild($element[0]);
        try {
            $pattern = $this->evaluateChild($element[1]);
        } catch (Math_Formula_Runner_Exception $e) {
            if (strstr($e->getMessage(), 'Unknown operation')) {
                $pattern = $element[1];
            } else {
                $pattern = '';
            }
        }

        if ($pattern instanceof Math_Formula_Element) {
            $pattern = $pattern->getType();
        }

        if (preg_match("/" . str_replace(',', '|', preg_quote($pattern)) . "/", $reference)) {
            return true;
        }

        return false;
    }
}
