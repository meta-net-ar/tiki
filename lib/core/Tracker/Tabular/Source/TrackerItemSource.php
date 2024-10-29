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
    private ?Tracker_Item $item;

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
        if ($itemId && $query = $schema->getDefaultFilterQuery()) {
            // filter out default query not returning the target tracker item
            $query->filterIdentifier($itemId, 'object_id');
            $this->forceFreshIndex();
            $result = $query->search(\TikiLib::lib('unifiedsearch')->getIndex());
            if ($result->count() == 0) {
                $this->item = null;
            }
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

    /**
     * Ensure index queue is processed as normal tracker save happens in a transaction
     * and by the time sync event is executed, queue has not been processed yet.
     */
    private function forceFreshIndex()
    {
        global $prefs;
        if ($prefs['feature_search'] == 'y' && $prefs['unified_incremental_update'] == 'y') {
            \TikiLib::lib('unifiedsearch')->processUpdateQueue(10, true);
        }
    }
}
