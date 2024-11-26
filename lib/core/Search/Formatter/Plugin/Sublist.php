<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * Formatter plugin used in Sublist context to return direct results as arrays
 * in order to use those as children elements in parent lists.
 */
class Search_Formatter_Plugin_Sublist implements Search_Formatter_Plugin_Interface
{
    public function getFormat()
    {
        return self::FORMAT_ARRAY;
    }

    public function getFields()
    {
        return [];
    }

    public function prepareEntry($valueFormatter)
    {
        return $valueFormatter->getPlainValues();
    }

    public function renderEntries(Search_ResultSet $entries)
    {
        return $entries;
    }
}
