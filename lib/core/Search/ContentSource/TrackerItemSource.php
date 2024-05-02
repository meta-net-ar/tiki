<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_ContentSource_TrackerItemSource implements Search_ContentSource_Interface, Tiki_Profile_Writer_ReferenceProvider, Search_FacetProvider_Interface
{
    private $db;
    private $trklib;
    private $mode;
    private $indexer;

    public function __construct($mode = '')
    {
        $this->db = TikiDb::get();
        $this->trklib = TikiLib::lib('trk');
        $this->mode = $mode;
    }

    public function getReferenceMap()
    {
        return [
            'tracker_id' => 'tracker',
        ];
    }

    public function getDocuments()
    {
        return $this->db->table('tiki_tracker_items')->fetchColumn('itemId', []);
    }

    public function getDocument($objectId, Search_Type_Factory_Interface $typeFactory): array|false
    {
        $data = [];

        $item = $this->trklib->get_tracker_item($objectId);

        if (empty($item)) {
            return false;
        }

        try {
            $itemObject = Tracker_Item::fromInfo($item);
            $definition = $itemObject->getDefinition();
            if (! $definition) {
                throw new InvalidArgumentException("Tracker definition not found for item $objectId");
            }
        } catch (InvalidArgumentException $e) {
            // ignore corrupted items, e.g. where trackerId == 0
            trigger_error($e->getMessage());
            return false;
        }

        $permNeeded = $itemObject->getViewPermission();
        $specialUsers = $itemObject->getSpecialPermissionUsers($objectId, 'View');

        $fieldPermissions = [];

        foreach (self::getIndexableHandlers($definition, $item) as $handler) {
            if ($this->indexer) {
                $this->indexer->errorContext = 'Field ' . $handler->getConfiguration('fieldId') . ' / ' . $handler->getConfiguration('name');
            }

            $documentPart = $handler->getDocumentPart($typeFactory, $this->mode);
            $data = array_merge($data, $documentPart);

            $field = $handler->getFieldDefinition();
            // if isHidden is "y" (Visible after creation by administrators only) then field perms apply
            // all other settings of "visibility" control writing, not reading
            if ($field['isHidden'] === 'y' || ! empty($field['visibleBy'])) {
                $fieldPermissions[$field['permName']] = array_merge(
                    $itemObject->getAllowedUserGroupsForField($field),
                    ['perm_names' => array_keys($documentPart)]
                );
            }
        }

        if ($this->indexer) {
            $this->indexer->errorContext = null;
        }

        $ownerGroup = $itemObject->getOwnerGroup();
        $data = array_merge(
            [
                'title' => $typeFactory->sortable($this->trklib->get_isMain_value($item['trackerId'], $objectId)),
                'modification_date' => $typeFactory->timestamp($item['lastModif']),
                'creation_date' => $typeFactory->timestamp($item['created']),
                'contributors' => $typeFactory->multivalue(array_unique([$item['createdBy'], $item['lastModifBy']])),
                'date' => $typeFactory->timestamp($item['created']),

                'tracker_status' => $typeFactory->identifier($item['status']),
                'tracker_id' => $typeFactory->identifier($item['trackerId']),

                'view_permission' => $typeFactory->identifier($permNeeded),
                'parent_object_id' => $typeFactory->identifier($item['trackerId']),
                'parent_object_type' => $typeFactory->identifier('tracker'),

                'field_permissions' => $typeFactory->plaintext(json_encode($fieldPermissions)),

                // Fake attributes, removed before indexing
                '_extra_users' => $specialUsers,
                '_permission_accessor' => $itemObject->getPerms(),
                '_extra_groups' => $ownerGroup ? [$ownerGroup] : null,
            ],
            $data
        );

        if (empty($data['title'])) {
            $data['title'] = $typeFactory->sortable(tr('Unknown'));
        }
        if (empty($data['language'])) {
            $data['language'] = $typeFactory->identifier('unknown');
        }

        return $data;
    }

    public function getProvidedFields(): array
    {
        static $data;

        if (is_array($data)) {
            return $data;
        }

        $data = [
            'title',
            'language',
            'modification_date',
            'creation_date',
            'date',
            'contributors',

            'tracker_status',
            'tracker_id',

            'view_permission',
            'parent_object_id',
            'parent_object_type',
        ];

        foreach (self::getAllIndexableHandlers() as $handler) {
            $data = array_merge($data, $handler->getProvidedFields());
        }

        return array_unique($data);
    }

    public function getProvidedFieldTypes(): array
    {
        static $data;

        if (is_array($data)) {
            return $data;
        }

        $data = [
            'title' => 'sortable',
            'language' => 'identifier',
            'modification_date' => 'timestamp',
            'creation_date' => 'timestamp',
            'date' => 'timestamp',
            'contributors' => 'multivalue',

            'tracker_status' => 'identifier',
            'tracker_id' => 'identifier',

            'view_permission' => 'identifier',
            'parent_object_id' => 'identifier',
            'parent_object_type' => 'identifier',
        ];

        foreach (self::getAllIndexableHandlers() as $handler) {
            $data = array_merge($data, $handler->getProvidedFieldTypes());
        }

        return $data;
    }

    public function getGlobalFields(): array
    {
        static $data;

        if (is_array($data)) {
            return $data;
        }

        $data = [];

        foreach (self::getAllIndexableHandlers() as $handler) {
            if (! ($handler->isMainField())) {
                $data = array_merge($data, $handler->getGlobalFields());
            } else {
                //Skip this field if it's the main title field, it will still be part of title later, we don't want it twice in contents, and we want it at the end
                //var_dump($handler->getBaseKey());
            }
        }

        $data['title'] = true;
        //$data['date'] = true;//Why would we want to append the date to contents? - benoitg - 2024-03-09
        return $data;
    }

    public static function getIndexableHandlers(Tracker_Definition $definition, $item = [])
    {
        return self::getHandlersMatching('\Tracker\Field\IndexableInterface', $definition, $item);
    }

    private static function getHandlersMatching($interface, $definition, $item)
    {
        global $prefs;

        $factory = $definition->getFieldFactory();

        $handlers = [];
        foreach ($definition->getFields() as $field) {
            if ($prefs['unified_trackerfield_keys'] === 'permName' && isset($field['permName']) && strlen($field['permName']) > Tracker_Item::PERM_NAME_MAX_ALLOWED_SIZE) {
                continue;
            }
            if ($prefs['unified_exclude_nonsearchable_fields'] === 'y' && $field['isSearchable'] !== 'y') {
                continue;
            }
            $handler = $factory->getHandler($field, $item);

            if ($handler instanceof $interface) {
                $handlers[] = $handler;
            }
        }

        return $handlers;
    }

    /**
     * Get all indexable field handlers from EVERY field configured in
     * ANY tracker in the database.
     *
    */
    private static function getAllIndexableHandlers()
    {
        //I think this will hit the database a ridiculous number of times as currently implemented - benoitg - 2024-03-09
        $trackers = TikiDb::get()->table('tiki_trackers')->fetchColumn('trackerId', []);

        $handlers = [];
        foreach ($trackers as $trackerId) {
            $definition = Tracker_Definition::get($trackerId);
            $handlers = array_merge($handlers, self::getIndexableHandlers($definition));
        }

        return $handlers;
    }

    public function getFacets()
    {
        global $prefs;

        $trackers = $this->db->table('tiki_trackers')->fetchColumn('trackerId', []);

        $handlers = [];
        foreach ($trackers as $trackerId) {
            $definition = Tracker_Definition::get($trackerId);
            $handlers = array_merge($handlers, self::getHandlersMatching('Search_FacetProvider_Interface', $definition, []));
        }

        $source = new Search_FacetProvider();
        $source->addFacets([
            Search_Query_Facet_Term::fromField('tracker_id')
                ->setLabel(tr('Tracker'))
                ->setRenderCallback(function ($id) {
                    $lib = TikiLib::lib('object');
                    return $lib->get_title('tracker', $id);
                }),
            Search_Query_Facet_Term::fromField('tracker_status')
                ->setLabel(tr('Tracker Status'))
                ->setRenderCallback(function ($status) {
                    $status_types = TikiLib::lib('trk')->status_types();
                    $statuses = [
                        'o' => $status_types['o']['label'],
                        'p' => $status_types['p']['label'],
                        'c' => $status_types['c']['label']
                    ];
                    return $statuses[$status];
                })
        ]);

        foreach ($handlers as $handler) {
            if ($prefs['unified_exclude_nonsearchable_fields_from_facets'] !== 'y' || $handler->getConfiguration('isSearchable') === 'y') {
                $source->addProvider($handler);
            }
        }

        return $source->getFacets();
    }

    public function setIndexer($indexer)
    {
        $this->indexer = $indexer;
    }
}
