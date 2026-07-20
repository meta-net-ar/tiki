<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\TestHelpers\Tracker;

use TikiLib;
use Tracker_Definition;

/**
 * Shared fixture factory for tests that need real trackers in the database.
 *
 * Creates trackers, fields and items through the legacy TrackerLib API on
 * purpose: that API is the behavior the tracker test suite pins down, so
 * fixtures must go through the same code path production writes use.
 *
 * Use this for fixture setup only. When item or field creation is itself the
 * behavior under test (e.g. input preparation via Tracker_Item::prepareInput,
 * or error handling of replace_item), keep those calls inline in the test.
 *
 * The factory tracks every tracker and item it creates; call cleanup() from
 * tearDownAfterClass() to remove them.
 */
final class TrackerFixtureFactory
{
    /** @var \TrackerLib */
    private $trklib;

    /** @var int[] */
    private $trackerIds = [];

    /** @var int[] */
    private $itemIds = [];

    public function __construct()
    {
        $this->trklib = TikiLib::lib('trk');
    }

    /**
     * Create a tracker and track its ID for cleanup.
     *
     * @throws \RuntimeException when the tracker cannot be created
     */
    public function createTracker(string $name, string $description = '', array $options = [], string $descriptionIsParsed = 'n'): int
    {
        $trackerId = $this->trklib->replace_tracker(0, $name, $description, $options, $descriptionIsParsed);
        if (! $trackerId) {
            throw new \RuntimeException("TrackerFixtureFactory: could not create tracker '$name'");
        }
        $this->trackerIds[] = (int) $trackerId;

        return (int) $trackerId;
    }

    /**
     * Add one field to a tracker.
     *
     * $spec keys (defaults in parentheses):
     *   name (required), permName (required), type ('t'),
     *   options ('') — an array is json_encode()d, a string is passed through,
     *   isMain ('y'), isSearchable ('y'), isTblVisible ('y'), isPublic ('y'),
     *   isHidden ('n'), isMandatory ('n'), position (10),
     *   description (''), isMultilingual (''), itemChoices (null),
     *   errorMsg (''), visibleBy (null), editableBy (null),
     *   descriptionIsParsed ('n'), validation (''), validationParam (''),
     *   validationMessage ('')
     *
     * @throws \RuntimeException when the field cannot be created
     */
    public function addField(int $trackerId, array $spec): int
    {
        foreach (['name', 'permName'] as $required) {
            if (empty($spec[$required])) {
                throw new \InvalidArgumentException("TrackerFixtureFactory: field spec requires '$required'");
            }
        }

        $options = $spec['options'] ?? '';
        if (is_array($options)) {
            $options = json_encode($options);
        }

        $fieldId = $this->trklib->replace_tracker_field(
            $trackerId,
            0, // fieldId 0 = create new
            $spec['name'],
            $spec['type'] ?? 't',
            $spec['isMain'] ?? 'y',
            $spec['isSearchable'] ?? 'y',
            $spec['isTblVisible'] ?? 'y',
            $spec['isPublic'] ?? 'y',
            $spec['isHidden'] ?? 'n',
            $spec['isMandatory'] ?? 'n',
            $spec['position'] ?? 10,
            $options,
            $spec['description'] ?? '',
            $spec['isMultilingual'] ?? '',
            $spec['itemChoices'] ?? null,
            $spec['errorMsg'] ?? '',
            $spec['visibleBy'] ?? null,
            $spec['editableBy'] ?? null,
            $spec['descriptionIsParsed'] ?? 'n',
            $spec['validation'] ?? '',
            $spec['validationParam'] ?? '',
            $spec['validationMessage'] ?? '',
            $spec['permName']
        );
        if (! $fieldId) {
            throw new \RuntimeException("TrackerFixtureFactory: could not create field '{$spec['permName']}' on tracker $trackerId");
        }

        return (int) $fieldId;
    }

    /**
     * Add several fields. Positions default to (index + 1) * $positionStep
     * unless a spec sets its own 'position'.
     *
     * @param array[] $specs field specs, see addField()
     * @return array map of permName => fieldId
     */
    public function addFields(int $trackerId, array $specs, int $positionStep = 10): array
    {
        $fieldIds = [];
        $i = 0;
        foreach ($specs as $spec) {
            $i++;
            if (! isset($spec['position'])) {
                $spec['position'] = $i * $positionStep;
            }
            $fieldIds[$spec['permName']] = $this->addField($trackerId, $spec);
        }

        return $fieldIds;
    }

    /**
     * Create an item with field values keyed by permName and track its ID.
     *
     * Fields without a value in $valuesByPermName are set to '' (matching the
     * long-standing convention of the tests this factory was extracted from).
     * A null value is passed through as null, not coerced to ''.
     *
     * @throws \RuntimeException when the item cannot be created
     */
    public function createItem(int $trackerId, array $valuesByPermName = [], string $status = 'o'): int
    {
        // Deliberately uses the definition cache, matching the tests this
        // factory was extracted from.
        $definition = Tracker_Definition::get($trackerId);
        if (! $definition) {
            throw new \RuntimeException("TrackerFixtureFactory: no definition for tracker $trackerId");
        }

        $data = [];
        foreach ($definition->getFields() as $field) {
            $value = array_key_exists($field['permName'], $valuesByPermName)
                ? $valuesByPermName[$field['permName']]
                : '';
            $data[] = array_merge($field, ['value' => $value]);
        }

        $itemId = $this->trklib->replace_item($trackerId, 0, ['data' => $data], $status);
        if (! $itemId || $itemId <= 0) {
            throw new \RuntimeException("TrackerFixtureFactory: could not create item in tracker $trackerId");
        }
        $this->itemIds[] = (int) $itemId;

        return (int) $itemId;
    }

    /**
     * Update an existing item through the same replace_item path createItem()
     * uses. This is the only fixture path on which a field handler's
     * handleSave() receives a non-null $oldValue — creation alone never
     * exercises the update arm.
     *
     * Fields absent from $valuesByPermName are re-submitted with their
     * currently stored value, matching what an edit form does. (Submitting ''
     * for them, as createItem() does, would overwrite sibling fields.)
     *
     * @throws \RuntimeException when the item cannot be updated
     */
    public function updateItem(int $trackerId, int $itemId, array $valuesByPermName, string $status = 'o'): void
    {
        $definition = Tracker_Definition::get($trackerId);
        if (! $definition) {
            throw new \RuntimeException("TrackerFixtureFactory: no definition for tracker $trackerId");
        }

        $current = $this->trklib->get_tracker_item($itemId);
        if (! $current) {
            throw new \RuntimeException("TrackerFixtureFactory: no item $itemId to update");
        }

        $data = [];
        foreach ($definition->getFields() as $field) {
            $value = array_key_exists($field['permName'], $valuesByPermName)
                ? $valuesByPermName[$field['permName']]
                : ($current[$field['fieldId']] ?? '');
            $data[] = array_merge($field, ['value' => $value]);
        }

        $result = $this->trklib->replace_item($trackerId, $itemId, ['data' => $data], $status);
        if (! $result) {
            throw new \RuntimeException("TrackerFixtureFactory: could not update item $itemId in tracker $trackerId");
        }
    }

    /**
     * Look up a field ID by permName, bypassing the definition cache so
     * fields added after an earlier Tracker_Definition::get() are visible.
     */
    public function getFieldId(int $trackerId, string $permName): ?int
    {
        $definition = Tracker_Definition::get($trackerId, false);
        if (! $definition) {
            return null;
        }
        $field = $definition->getFieldFromPermName($permName);

        return $field ? (int) $field['fieldId'] : null;
    }

    /**
     * Remove every tracked item, then every tracked tracker.
     *
     * Each removal is wrapped in try/catch so a cleanup problem never masks
     * the real test failure.
     */
    public function cleanup(): void
    {
        foreach ($this->itemIds as $itemId) {
            try {
                $this->trklib->remove_tracker_item($itemId);
            } catch (\Throwable) {
                // Ignore cleanup errors
            }
        }
        $this->itemIds = [];

        foreach ($this->trackerIds as $trackerId) {
            try {
                $this->trklib->remove_tracker($trackerId);
            } catch (\Throwable) {
                // Ignore cleanup errors
            }
        }
        $this->trackerIds = [];
    }
}
