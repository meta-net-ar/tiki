<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_MySql_Prefilter
{
    /**
     * Returns MySQL prefilter logic to possibly insert in a closure
     * @param $fields
     * @param $entry
     * @return array
     */
    public function get($fields, $entry)
    {
        return array_filter(
            $fields,
            function ($field) use ($entry) {
                if ($field == 'ignored_fields') {
                    return false;
                }
                if (! empty($entry[$field]) && is_string($entry[$field])) {
                    return preg_match('/token[a-z]{20,}/', $entry[$field]);
                }
                return true;
            }
        );
    }
}
