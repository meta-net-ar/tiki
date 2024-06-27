<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tracker\Tabular\Source;

class TrackerItemSource implements SourceInterface
{
    private $schema;
    private $itemId;

    public function __construct(\Tracker\Tabular\Schema $schema, $itemId)
    {
        $this->schema = $schema;
        $this->itemId = $itemId;
    }

    public function getEntries()
    {
        yield new TrackerSourceEntry($this->itemId);
    }

    public function getSchema()
    {
        return $this->schema;
    }
}
