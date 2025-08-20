<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Tracker_Definition
{
    public static $definitions = [];

    private $trackerInfo;
    private $factory;
    private $fields;

    public static function get($trackerId, $useCache = true)
    {
        $trackerId = (int) $trackerId;

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

    public static function getDefault()
    {
        $def = new self([]);
        $def->fields = [];

        return $def;
    }

    private function __construct($trackerInfo)
    {
        $this->trackerInfo = $trackerInfo;
    }

    public function getInformation()
    {
        return $this->trackerInfo;
    }

    public function getId()
    {
        return $this->trackerInfo['trackerId'];
    }

    public function getFieldFactory()
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

    public function getFields()
    {
        if ($this->fields) {
            return $this->fields;
        }

        $trklib = TikiLib::lib('trk');
        $trackerId = $this->trackerInfo['trackerId'] ?? 0;

        if ($trackerId) {
            $fields = $trklib->list_tracker_fields($trackerId, 0, -1, 'position_asc', '', false /* Translation must be done from the views to avoid translating the sources on edit. */);

            return $this->fields = $fields['data'];
        } else {
            return $this->fields = [];
        }
    }

    public function setFields($fields)
    {
        $this->fields = $fields;
    }

    public function getField($id)
    {
        if (is_numeric($id)) {
            foreach ($this->getFields() as $f) {
                if ($f['fieldId'] == $id) {
                    return $f;
                }
            }
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
        throw new Error("Field with fieldId: {$id} not found in definition of {$this->trackerInfo['trackerId']}");
    }

    public function getFieldFromName($name): ?array
    {
        foreach ($this->getFields() as $f) {
            if ($f['name'] == $name) {
                return $f;
            }
        }
    }

    public function getFieldFromPermName($name)
    {
        if (empty($name)) {
            return null;
        }

        foreach ($this->getFields() as $f) {
            if ($f['permName'] == $name) {
                return $f;
            }
        }
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

    public function getSynchronizedTabulars($type = null): array
    {
        $tabularIds = explode(',', $this->getConfiguration('tabularSync'));
        $tabularIds = array_filter($tabularIds);
        $tabularIds = array_map('intval', $tabularIds);
        $tabulars = [];
        foreach ($tabularIds as $tabularId) {
            $tabular = TikiLib::lib('tabular')->getInfo($tabularId);
            if (empty($tabular['tabularId'])) {
                throw new Exception(tr("Tracker remote synchronization configured with a import-export format that does not exist. Tracker: %0, Tabular: %1", $this->getId(), $tabularId));
            }
            if ($type == 'odbc' && empty($tabular['odbc_config'])) {
                continue;
            }
            if ($type == 'api' && empty($tabular['api_config'])) {
                continue;
            }
            $tabulars[] = $tabular;
        }
        return $tabulars;
    }
}
