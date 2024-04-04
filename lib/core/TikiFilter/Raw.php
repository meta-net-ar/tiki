<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// FIXME: This may quietly alter the value. Users of this filter should report if the value is modified.
class TikiFilter_Raw implements Laminas\Filter\FilterInterface
{
    /**
     * Returns the input value unchanged
     *
     * @param mixed $value
     * @return mixed
     */
    public function filter($value)
    {
        return $value;
    }
}
