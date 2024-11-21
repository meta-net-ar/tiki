<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
interface Search_Formatter_Plugin_Interface
{
    public const FORMAT_WIKI = 'wiki';
    public const FORMAT_HTML = 'html';
    public const FORMAT_ARRAY = 'array';
    public const FORMAT_CSV = 'csv';

    /** @return The array of fields this Formatter looks for.  The key is the  field name, the value is the default value for that field */
    public function getFields();

    public function getFormat();

    public function prepareEntry($entry);

    public function renderEntries(Search_ResultSet $entries);
}
