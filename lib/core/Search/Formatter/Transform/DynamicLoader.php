<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/** If a field isn't available in the index, this tries to load it from the original data, which can have a huge performance impcat.
 * Typically it does it for multivalue fields which are tokenized to
 * hashes for indexing that are meaningless for the user.
 * To restore them to meaningful values, it needs to retrieve the initial data.
 * An example is the UserSelect field in trackers. */

namespace Tiki\Search\Formatter\Transform;

use Search_Formatter_DataSource_Interface;

class DynamicLoader
{
    private $source;

    public function __construct(Search_Formatter_DataSource_Interface $datasource)
    {
        $this->source = $datasource;
    }

    public function __invoke($entry)
    {
        return new DynamicLoaderWrapper($entry, $this->source);
    }
}
