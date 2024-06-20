<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Represents a specific tracker; a row in tiki_trackers
 */
class Tracker_Definition
{
    /**
     * This is a cache of tracker definitions
     */
    private static array $definitions = [];

    /** This is the raw row from  tiki_trackers, with columns
     * trackerId: int, Primary key
     * name: varchar(255)
     * description: text
     * descriptionIsParsed: 'y' or 'n'
     * created: bigint relative to unix epoch
     * lastModif: bigint relative to unix epoch
     * items: int A cache of the number of items currently stored in the tracker
    */
    private array $trackerInfo;
    private $factory;
    /**
     * This is the direct return of the the 'data' key of the very messy legacy TrackerLib::list_tracker_fields, may as well read the code directly if you want to know what's in there...
     */
    private ?array $fields = null;

    public static function get($trackerId, $useCache = true): Tracker_Definition|false
    {
        $trackerId = (int) $trackerId;

        if (! $trackerId) {
            throw new InvalidArgumentException("trackerId parameter must be present");
        }

        if ($useCache && isset(self::$definitions[$trackerId])) {
            return self::$definitions[$trackerId];
        }

        $trklib = TikiLib::lib('trk');
        $tracker_info = $trklib->get_tracker($trackerId);

        $definition = false;

        if ($tracker_info) {
            if ($t = $trklib->get_tracker_options($trackerId)) {
                $tracker_info = array_merge($t, $tracker_info);
            }

            if (in_array($trackerId, $trklib->getSystemTrackerIds())) {
                $tracker_info['system_tracker'] = true;
            }

            $definition = new self($tracker_info);
        }

        return self::$definitions[$trackerId] = $definition;
    }

    public static function createFake(array $trackerInfo, array $fields)
    {
        $def = new self($trackerInfo);
        $def->fields = $fields;

        return $def;
    }

    public static function clearCache(int $trackerId): void
    {
        unset(self::$definitions[$trackerId]);
    }

    private function __construct(array $trackerInfo)
    {
        $this->trackerInfo = $trackerInfo;
    }

    /** @deprecated */
    public function getInformation()
    {
        return $this->trackerInfo;
    }

    public function getId(): int
    {
        return $this->trackerInfo['trackerId'];
    }

    public function getFieldFactory(): Tracker_Field_Factory
    {
        if ($this->factory) {
            return $this->factory;
        }

        return $this->factory = new Tracker_Field_Factory($this);
    }

    public function getConfiguration($key, $default = false)
    {
        return isset($this->trackerInfo[$key]) ? $this->trackerInfo[$key] : $default;
    }

    public function isEnabled($key)
    {
        return $this->getConfiguration($key) === 'y';
    }

    public function getRelationshipBehaviour($relation)
    {
        if ($behaviour = $this->getConfiguration('relationshipBehaviour')) {
            return new Tiki\Relation\Semantics($behaviour, $relation);
        }
        return null;
    }

    public function getFieldsIdKeys()
    {
        $fields = [];
        foreach ($this->getFields() as $key => $field) {
            $fields[$field['fieldId']] = $field;
        }
        return $fields;
    }

    public function getFields(): array
    {
        if ($this->fields !== null) {
            return $this->fields;
        }

        $trklib = TikiLib::lib('trk');
        $trackerId = $this->trackerInfo['trackerId'];

        if ($trackerId) {
            /** This is inefficient and goes back and forth between the new and old objects to build an undocumented fields structure - benoitg - 2024-03-03 */
            $fields = $trklib->list_tracker_fields($trackerId, 0, -1, 'position_asc', '', false /* Translation must be done from the views to avoid translating the sources on edit. */);

            return $this->fields = $fields['data'];
        } else {
            return $this->fields = [];
        }
    }

    /**
     * Get the field info
     *
     * @param [type] $id The fieldId or permName.  Searches the fieldId if is_numeric, otherwise searches the permName.
     * @return array|null
     */
    public function getField($id): array|null
    {
        if (is_numeric($id)) {
            return $this->getFieldInfoFromFieldId((int) $id);
        } else {
            return $this->getFieldFromPermName($id);
        }
    }

    public function hasFieldId(int $id): bool
    {
        if (! $id) {
            throw new InvalidArgumentException("id parameter must be provided");
        }
        foreach ($this->getFields() as $f) {
            if ($f['fieldId'] == $id) {
                return true;
            }
        }
        return false;
    }

    public function getFieldInfoFromFieldId(int $id): array
    {
        if (! $id) {
            throw new InvalidArgumentException("id parameter must be provided");
        }
        foreach ($this->getFields() as $f) {
            if ($f['fieldId'] == $id) {
                return $f;
            }
        }
        throw new RuntimeException("Field with fieldId: {$id} not found in definition of {$this->trackerInfo['trackerId']}");
    }

    public function getFieldFromName($name): ?array
    {
        if (empty($name)) {
            throw new InvalidArgumentException("name parameter must be provided");
        }
        foreach ($this->getFields() as $f) {
            if ($f['name'] == $name) {
                return $f;
            }
        }
        return null;
    }

    public function getFieldFromPermName($permName): ?array
    {
        if (empty($permName)) {
            throw new InvalidArgumentException("permName parameter must be provided");
        }

        foreach ($this->getFields() as $f) {
            if ($f['permName'] == $permName) {
                return $f;
            }
        }
        return null;
    }

    /**
     * Get the tracker's configured "Main" or "Title" field's id.
     * There may not be one that is configured, in withc case it returns null
     * There may be more than one configured (the interface doesn't currenty prevent it - benoitg- 2024-03-08), in which case it returns the first one.
     *
     * @return int|null
     */
    public function getMainFieldId(): int|null
    {
        foreach ($this->getFields() as $field) {
            if ($field['isMain'] == 'y') {
                return $field['fieldId'];
            }
        }
        return null;
    }

    public function getFieldFromNameMaj($name)
    {
        $name = strtoupper($name);
        foreach ($this->getFields() as $f) {
            if (strtoupper($f['name']) == $name) {
                return $f;
            }
        }
    }

    public function getPopupFields()
    {
        if (! empty($this->trackerInfo['showPopup'])) {
            return explode(',', $this->trackerInfo['showPopup']);
        } else {
            return [];
        }
    }

    public function getAuthorField()
    {
        foreach ($this->getFields() as $field) {
            if (
                $field['type'] == 'u'
                && $field['options_map']['autoassign'] == 1
                && ($this->isEnabled('userCanSeeOwn') or $this->isEnabled('writerCanModify'))
            ) {
                return $field['fieldId'];
            }
        }
    }

    public function getAuthorIpField()
    {
        foreach ($this->getFields() as $field) {
            if (
                $field['type'] == 'I'
                && $field['options_map']['autoassign'] == 1
            ) {
                return $field['fieldId'];
            }
        }
    }

    public function getWriterField()
    {
        foreach ($this->getFields() as $field) {
            if (
                in_array($field['type'], ['u', 'I'])
                && $field['options_map']['autoassign'] == 1
            ) {
                return $field['fieldId'];
            }
        }
    }

    public function getUserField()
    {
        foreach ($this->getFields() as $field) {
            if (
                $field['type'] == 'u'
                && $field['options_map']['autoassign'] == 1
            ) {
                return $field['fieldId'];
            }
        }
    }

    public function getItemOwnerFields()
    {
        $ownerFields = [];
        foreach ($this->getFields() as $field) {
            if (
                $field['type'] == 'u'
                && $field['options_map']['owner'] == 1
            ) {
                $ownerFields[] = $field['fieldId'];
            }
        }
        if (! $ownerFields) {
            $ownerFields = [$this->getUserField()];
        }
        return array_filter($ownerFields);
    }

    public function getItemGroupOwnerFields()
    {
        $ownerFields = [];
        foreach ($this->getFields() as $field) {
            if ($field['type'] == 'g' && ! empty($field['options_map']['owner'])) {
                $ownerFields[] = $field['fieldId'];
            }
        }
        if (! $ownerFields) {
            $ownerFields = [$this->getWriterGroupField()];
        }
        return array_filter($ownerFields);
    }

    public function getArticleField()
    {
        foreach ($this->getFields() as $field) {
            if ($field['type'] == 'articles') {
                return $field['fieldId'];
            }
        }
    }

    public function getGeolocationField()
    {
        foreach ($this->getFields() as $field) {
            if ($field['type'] == 'G' && in_array($field['options_map']['use_as_item_location'], [1, 'y'])) {
                return $field['fieldId'];
            }
        }
    }

    public function getWikiFields()
    {
        $fields = [];
        foreach ($this->getFields() as $field) {
            if ($field['type'] == 'wiki') {
                $fields[] = $field['fieldId'];
            }
        }
        return $fields;
    }

    public function getIconField()
    {
        foreach ($this->getFields() as $field) {
            if ($field['type'] == 'icon') {
                return $field['fieldId'];
            }
        }
    }

    public function getWriterGroupField()
    {
        foreach ($this->getFields() as $field) {
            if (
                $field['type'] == 'g'
                && $field['options_map']['autoassign'] == 1
            ) {
                return $field['fieldId'];
            }
        }
    }

    public function getRateField()
    {
        // This is here to support some legacy code for the deprecated 's' type rating field. It is not meant to be generically apply to the newer stars rating field
        foreach ($this->getFields() as $field) {
//          if ($field['type'] == 's' && $field['name'] == 'Rating') { // Do not force the name to be exactly the non-l10n string "Rating" to allow fetching the fieldID !!!
            if ($field['type'] == 's') {
                return $field['fieldId'];
            }
        }
    }

    public function getFreetagField()
    {
        foreach ($this->getFields() as $field) {
            if ($field['type'] == 'F') {
                return $field['fieldId'];
            }
        }
    }

    public function getLanguageField()
    {
        foreach ($this->getFields() as $field) {
            if (
                $field['type'] == 'LANG'
                && $field['options_map']['autoassign'] == 1
            ) {
                return $field['fieldId'];
            }
        }
    }

    public function getCategorizedFields()
    {
        $out = [];

        foreach ($this->getFields() as $field) {
            if ($field['type'] == 'e') {
                $out[] = $field['fieldId'];
            }
        }

        return $out;
    }

    public function getRelationField($relation)
    {
        foreach ($this->getFields() as $field) {
            if (
                $field['type'] == 'REL'
                && $field['options_map']['relation'] == $relation
            ) {
                return $field['fieldId'];
            }
        }
    }

    /**
     * Get the names of the item user(s) if any.
     * An item user is defined if a 'user selector' field
     * exist for this tracker and it has at least one user selected.
     *
     * @param int $itemId
     * @return array|mixed item user name
     */
    public function getItemUsers($itemId)
    {
        $trklib = TikiLib::lib('trk');
        return $trklib->get_item_creators($this->trackerInfo['trackerId'], $itemId);
    }

    public function getSyncInformation()
    {
        global $prefs;

        if ($prefs['tracker_remote_sync'] != 'y') {
            return false;
        }

        $attributelib = TikiLib::lib('attribute');
        $attributes = $attributelib->get_attributes('tracker', $this->getConfiguration('trackerId'));

        if (! isset($attributes['tiki.sync.provider'])) {
            return false;
        }

        return [
            'provider' => $attributes['tiki.sync.provider'],
            'source' => $attributes['tiki.sync.source'],
            'last' => $attributes['tiki.sync.last'],
            'modified' => $this->getConfiguration('lastModif') > $attributes['tiki.sync.last'],
        ];
    }

    public function canInsert(array $keyList)
    {
        foreach ($keyList as $key) {
            if (! $this->getFieldFromPermName($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the status types using the alternate status labels if they are set.
     * @param string $lg The language key to translate the status labels, if different than preferences.
     * @return array
     */
    public function getStatusTypes($lg = '')
    {
        $status = TikiLib::lib('trk')->status_types($lg);
        $trackerInfo = $this->trackerInfo;

        if (isset($trackerInfo['altOpenStatus']) && ! empty($trackerInfo['altOpenStatus'])) {
            $status['o']['label'] = $trackerInfo['altOpenStatus'];
        }
        if (isset($trackerInfo['altPendingStatus']) && ! empty($trackerInfo['altPendingStatus'])) {
            $status['p']['label'] = $trackerInfo['altPendingStatus'];
        }
        if (isset($trackerInfo['altClosedStatus']) && ! empty($trackerInfo['altClosedStatus'])) {
            $status['c']['label'] = $trackerInfo['altClosedStatus'];
        }

        return $status;
    }
}
