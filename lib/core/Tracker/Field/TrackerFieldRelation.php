<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tracker\Field;

use Tracker_Definition;
use Tracker_Field_Factory;
use Tracker\RelationInfoEntity;
use Tracker\RelationInfoRelation;

class TrackerFieldRelation extends AbstractTrackerFieldRelational
{
    public const OPT_RELATION = 'relation';
    public const OPT_FILTER = 'filter';
    public const OPT_READONLY = 'readonly';
    public const OPT_INVERT = 'invert';

    public function getParsedFilter(): array
    {
        parse_str($this->getOption(TrackerFieldRelation::OPT_FILTER), $filter);
        return $filter;
    }

    public function getRelationshipTracker(): Tracker_Definition|null
    {
        $relationshipTracker = null;
        if ($trackerId = $this->getOption('relationshipTrackerId')) {
            $relationshipTracker = Tracker_Definition::get($trackerId);
        }
        return $relationshipTracker;
    }
    private function getFieldRelationId(): string
    {
        $optionRelation = $this->getOption('relation');
        return $optionRelation;
    }

    public function getRelationId(): string
    {
        $id = $this->getFieldRelationId();
        $canonicalId = preg_replace('/\.invert$/', '', $id);
        return $canonicalId;
    }

    public function getDistantTrackerId(): ?int
    {
        $filter = $this->getParsedFilter();
        if ($filter) {
            $distantTrackerId = $filter['tracker_id'];
        } else {
            $distantTrackerId = null;
        }
        return $distantTrackerId;
    }

    /**
     * Get the corresponding relation id qualifier the matching relation would
     * have if also defined on the distant tracker.
     *
     * Note that for TrackerFieldRelation, this is stored in
     * the 'relation' option, it is not a field id.
     *
     * @see RelationLib
     * @param string $qualifier
     * @return string
     */
    public static function getInvertQualifier(string $qualifier): string
    {
        $length = strlen('.invert');

        if (str_ends_with($qualifier, '.invert')) {
            $qualifier = substr($qualifier, 0, -$length);
        } else {
            $qualifier .= '.invert';
        }
        return $qualifier;
    }

    private function getInvertFieldInstance(): ?AbstractTrackerFieldRelational
    {
        $invertRelation = static::getInvertQualifier($this->getOption('relation'));
        foreach (Tracker_Definition::getAllRelationalFields() as $field) {
            if ($field->getOption('relation') == $invertRelation) {
                return $field;
            }
        }
        return null;
    }

    public function getRelationInfo(): ?RelationInfoRelation
    {
        //$relationshipTracker = $this->getRelationshipTracker();

        $filter = $this->getParsedFilter();
        $distantTrackerId = $this->getDistantTrackerId();
        if ($distantTrackerId) {
            $distantTracker = Tracker_Definition::get($distantTrackerId);
        } else {
            //While editing the relationship, the distant tracker may not yet have been set
            $this->getRelationId();
            return null;
        }

        $invertField = $this->getInvertFieldInstance();
        $isManyToMany = (bool)$invertField;

        $relationInfo = new RelationInfoRelation();
        //TODO:  This should be ordered so we always show the non-invert first.  Othewise, the name depends on the order in which the trackers were traversed - benoitg - 2024-08-28.
        $relationInfo->id = $this->getRelationId();
        $name = $this->getName();
        if ($invertField) {
            $name .= ' | ' . $invertField->getName();
        }
        $relationInfo->name = $name;

        $first = new RelationInfoEntity();
        $first->instance = $distantTracker;
        $this->isMandatory() ? $first->cardinalityMin = 1 :
        $first->cardinalityMin = 0;
        $first->cardinalityMax = PHP_INT_MAX;
        $relationInfo->first = $first;


        $second = new RelationInfoEntity();
        $second->instance = $this->trackerDefinition;
        if ($invertField) {
            $invertField->isMandatory() ? $second->cardinalityMin = 1 :
            $second->cardinalityMin = 0;
        } else {
            $this->isMandatory() ? $second->cardinalityMin = 1 :
            $second->cardinalityMin = 0;
        }
        $second->cardinalityMax = $isManyToMany ? PHP_INT_MAX : 1;
        $relationInfo->second = $second;
        return $relationInfo;
    }
}
