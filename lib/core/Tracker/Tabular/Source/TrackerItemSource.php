<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tracker\Tabular\Source;

use Tracker\Tabular\Schema;
use Tracker_item;

class TrackerItemSource implements SourceInterface
{
    private Schema $schema;
    private Tracker_Item $item;

    public function __construct(Schema $schema, $itemId = null, $itemInfo = null)
    {
        $this->schema = $schema;
        if ($itemId && $itemId > 0) {
            $this->item = Tracker_Item::fromId($itemId);
        } elseif ($itemInfo) {
            $this->item = Tracker_Item::fromInfo($itemInfo);
        } else {
            $this->item = null;
        }
    }

    public function getEntries()
    {
        if ($this->item) {
            yield new TrackerSourceEntry($this->item);
        }
    }

    public function getSchema()
    {
        return $this->schema;
    }
}
