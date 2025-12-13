<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Relation;

use TikiLib;
use JitFilter;

/**
 * SystemTrackerCreator
 *
 * Automatically creates the Relation metadata system tracker and assigns
 * field configuration values.
 */
class SystemTrackerCreator
{
    protected $trackerId;
    protected $fieldIds;
    private $trklib;

    public function __construct()
    {
        $this->trklib = TikiLib::lib('trk');
    }

    public function createRelationshipTracker($type)
    {
        switch ($type) {
            case 'generic':
                $this->createGenericRelationshipTracker();
                break;
            case 'parent-child':
                $this->createParentChildRelationshipTracker();
                break;
            default:
                return false;
        }
        return true;
    }

    public function createGenericRelationshipTracker()
    {
        global $prefs;
        $name = tr('Generic Relationship Metadata');
        $description = tr('System tracker used to describe relations and their particular metadata like description.');
        $descriptionIsParsed = 'n';
        $input = new JitFilter([
            'fieldPrefix' => 'genericRelationship',
            'permName' => 'genericRelationship',
            'relationshipBehaviour' => 'GENERIC_DIRECTIONAL',
        ]);
        $data = $this->trklib->trackerOptionsFromInput($input);
        $trackerId = $this->trklib->replace_tracker(0, $name, $description, $data, $descriptionIsParsed);
        $this->trklib->replace_tracker_field(
            $trackerId,
            0,
            'Describe the relation',
            't',
            'y',
            'y',
            'y',
            'y',
            'n',
            'n',
            10,
            '',
            '',
            '',
            null,
            '',
            null,
            null,
            'n',
            '',
            '',
            '',
            'genericRelationshipDescription'
        );
    }

    public function createParentChildRelationshipTracker()
    {
        global $prefs;
        $name = tr('Parent-child Relationship Metadata');
        $description = tr('System tracker used to describe relations and their particular metadata.');
        $descriptionIsParsed = 'n';
        $input = new JitFilter([
            'fieldPrefix' => 'parentChildRelationship',
            'permName' => 'parentChildRelationship',
            'relationshipBehaviour' => 'GENERIC_ONE_TO_MANY',
        ]);
        $data = $this->trklib->trackerOptionsFromInput($input);
        $trackerId = $this->trklib->replace_tracker(0, $name, $description, $data, $descriptionIsParsed);
    }
}
