<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tracker\Field;

use Tracker_Field_Factory;

/**
 * Represents the fields on a tracker, as opposed to the fields on a tracker item, which are represented by AbstractItemField.  It's instances represent a row of the table 'tiki_tracker_fields'
 */
abstract class AbstractTrackerField
{
    public const DB_TABLE_NAME = 'tiki_tracker_fields';

    protected \Tracker_Definition $trackerDefinition;

    /**
     * The raw database row from tiki_tracker_fields, with keys:
     *
     * fieldId
     * trackerId
     * name
     * permName
     * options
     * type: A string (typically a letter) identifying the type of field
     * isMain
     * isTblVisible
     * position
     * isSearchable
     * isPublic
     * isHidden
     * isMandatory
     * description
     * isMultilingual
     * itemChoices
     * errorMsg
     * visibleBy
     * editableBy
     * descriptionIsParsed
     * validation
     * validationParam
     * validationMessage
     * rules
     * encryptionKeyId
     * excludeFromNotification
     * visibleInViewMode
     * visibleInEditMode
     * visibleInHistoryMode
     */
    protected array $fieldRow;
    /**
     * @var handle ??? -
     */
    private \Tracker_Options $options;

    public static function getFromTrackerAndId(\Tracker_Definition $trackerDefinition, int $fieldId)
    {
        global $tikilib;
        $result = $tikilib->query("SELECT fieldId, trackerId, name, permName, options, type FROM tiki_tracker_fields WHERE fieldId=?", [$fieldId]);
        $row = $result->fetchRow();
        return static::getInstanceFromTrackerAndRow($trackerDefinition, $row);
    }

    public static function getInstanceFromTrackerAndRow(\Tracker_Definition $trackerDefinition, array $fieldRow)
    {
        //check if fieldId is not null or empty
        if (! isset($fieldRow['fieldId']) || is_null($fieldRow['fieldId'])) {
            throw new \InvalidArgumentException("Field ID is missing or null");
        }
        $itemFieldClass = Tracker_Field_Factory::getTrackerItemFieldClassFromType($fieldRow['type']);
        $class = $itemFieldClass::getTrackerFieldClass();
        if (! isset($fieldRow['options'])) {
            throw new \InvalidArgumentException("Field row is missing required 'options' key for field ID: " . $fieldRow['fieldId']);
        }

        $field = $trackerDefinition->getFieldInstanceFromCache($fieldRow['fieldId']);
        if (! $field) {
            $field = new $class($trackerDefinition, $fieldRow);
            $trackerDefinition->setFieldInstanceInCache($field);
        }
        return $field;
    }

    public static function getInstanceFromRow(array $fieldRow)
    {
        //This is cached, there is a finite number of trackers in the system, and we use them everywhere so we want to hydrate the cache.  So probably no point in sql-optimizing this - benoitg - 2024-08-27
        $tracker = \Tracker_Definition::get($fieldRow['trackerId'], true);
        return static::getInstanceFromTrackerAndRow($tracker, $fieldRow);
    }

    protected function __construct(\Tracker_Definition $trackerDefinition, array $fieldRow)
    {
        $this->options = \Tracker_Options::fromSerialized($fieldRow['options'], $fieldRow);

        $this->trackerDefinition = $trackerDefinition;
        $this->fieldRow = $fieldRow;
    }

    public function getTrackerDefinition()
    {
        return $this->trackerDefinition;
    }

    /** Returns a structure, that is very close, but not the same as the raw row.  It augmented with a key options_array  (built with Tracker_Options:: buildOptionsArray()) */
    public function getLegacyDefinition(): array
    {
        $fieldInfo = $this->fieldRow;
        $fieldInfo['options_array'] = $this->options->buildOptionsArray();
        return $fieldInfo;
    }

    public function getId(): int
    {
        return $this->fieldRow['fieldId'];
    }
    public function getName(): string
    {
        return $this->fieldRow['name'];
    }
    public function getPermName(): string
    {
        return $this->fieldRow['permName'];
    }
    public function isMandatory(): bool
    {
        return $this->fieldRow['isMandatory'] == 'y';
    }

    public function getType(): string
    {
        return $this->fieldRow['type'];
    }

    public function getFieldTypeName(): string
    {
        $fieldInfo = Tracker_Field_Factory::getFieldInfo($this->fieldRow['type']);
        return $fieldInfo['name'];
    }

    /**
     * Return option from the options array.
     * For the list of options for a particular field check its getManagedTypesInfo() method.
     * Note: This function should be public, as long as certain low-level trackerlib functions need to be accessed directly.
     * Otherwise one would be forced to get the options from fields like this: $myField['options_array'][0] ...
     * @param int $number | string $key.  depending on type: based on the numeric array position, or by name.
     * @param mixed $default - defaultValue to return if nothing found
     * @return mixed
     */
    public function getOption(int|string $key, $default = false)
    {
        if (is_numeric($key)) {
            return $this->options->getParamFromIndex($key, $default);
        } else {
            return $this->options->getParam($key, $default);
        }
    }
}
