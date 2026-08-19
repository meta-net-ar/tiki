<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Class TikiFilter_Lang
 *
 * Filters for valid language values
 */
class TikiFilter_Lang implements Laminas\Filter\FilterInterface
{
    /**
     * Keep locale validation in Language::is_valid_language() so the regex and
     * language.php existence checks cannot drift apart. That method is static and
     * does not need a DB connection, so it is safe during early bootstrap / installer.
     *
     * @param mixed $input
     * @return mixed|string
     */
    public function filter($input)
    {
        return Language::is_valid_language($input) ? $input : '';
    }
}
