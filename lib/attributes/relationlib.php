<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * RelationLib
 *
 * @uses TikiDb_Bridge
 */

use Tiki\Relation\ObjectRelation;

/**
 * Manages the table 'tiki_object_relations'
 * @see Tracker_Field_Relation which also manages table 'tiki_object_relations'
 */
class RelationLib extends TikiDb_Bridge
{
    private $table;

    public function __construct()
    {
        $this->table = $this->table('tiki_object_relations');
    }

    /**
     * Obtains the list of relations with a given object as the source.
     * Optionally, the relation searched for can be specified. If the
     * relation ends with a dot, it will be used as a wildcard.
     */
    public function get_relations_from($type, $object, $relation = '', $orderBy = '', $max = -1)
    {
        if (str_ends_with($relation, '.invert')) {
            return $this->get_relations_to($type, $object, substr($relation, 0, -7), $orderBy, $max);
        }

        $cond = [
            'source_type' => $type,
            'source_itemId' => $object
        ];

        $fields = [
            'relationId',
            'relation',
            'type' => 'target_type',
            'itemId' => 'target_itemId',
            'fieldId' => 'source_fieldId',
            'metaItemId' => 'metadata_itemId',
        ];

        $cond = $this->apply_relation_condition($relation, $cond);
        return $this->table->fetchAll($fields, $cond, $max, -1, $orderBy);
    }

    /**
     * Obtains a list of relations in from source or target side.
     *
     * @param string $type     of source item
     * @param string $object   id of source item
     * @param string $relation qualifier of the relation
     * @param bool   $invert   - which side of the relation to search
     *
     * @return array of ObjectRelation objects filled with all relation data
     */
    public function getObjectRelations(string $type, string $object, string $relation, bool $invert = false): array
    {
        $straight = true;
        if (str_ends_with($relation, '.invert')) {
            $straight = false;
            $relation = substr($relation, 0, -7);
        }
        if ($invert) {
            $straight = ! $straight;
        }

        $cond = ['relation' => $relation];

        if ($straight) {
            $cond['source_type'] = $type;
            $cond['source_itemId'] = $object;
        } else {
            $cond['target_type'] = $type;
            $cond['target_itemId'] = $object;
        }

        $rows = $this->table->fetchAll($this->table->all(), $cond);

        $result = [];
        foreach ($rows as $row) {
            if (empty($row['source_itemId'])) {
                Feedback::error(
                    tr(
                        'Relation "%0" (#%1) to %2:%3 missing %4 source_itemId',
                        $relation,
                        $row['relationId'],
                        $type,
                        $object ?: ($row['target_itemId'] ?: ''),
                        $row['source_type']
                    )
                );
                continue;
            }
            if (empty($row['target_itemId'])) {
                Feedback::error(
                    tr(
                        'Relation "%0" (#%1) to %2:%3 missing %4 target_itemId',
                        $relation,
                        $type,
                        $object ?: $row['source_itemId'],
                        $row['target_type']
                    )
                );
                continue;
            }
            $result[] = new ObjectRelation($row, $straight);
        }

        return $result;
    }


    /**
     * Obtain a list of objects that have a given relation
     *
     */
    public function get_related_objects($relation, $orderBy = '', $max = -1)
    {

        $cond = [
            'relation' => $relation,
        ];

        $fields = [
            'source_type',
            'source_itemId',
            'target_type',
            'target_itemId',
        ];

        return $this->table->fetchAll($fields, $cond, $max, -1, $orderBy); /// resultset as an array
    }

    /**
     * This is a convenience function to get all the matching IDs from
     * get_relations_from without caring about the object type which might be assumed
     */

    public function get_object_ids_with_relations_from($type, $object, $relation = null)
    {
        $ret = [];
        $relations = $this->get_relations_from($type, $object, $relation);
        foreach ($relations as $r) {
            $ret[] = $r['itemId'];
        }
        return $ret;
    }

    /**
     * This is a convenience function to get all the matching IDs from
     * get_relations_to without caring about the object type which might be assumed
     */

    public function get_object_ids_with_relations_to($type, $object, $relation = null)
    {
        $ret = [];
        $relations = $this->get_relations_to($type, $object, $relation);
        foreach ($relations as $r) {
            $ret[] = $r['itemId'];
        }
        return $ret;
    }

    /**
     * @param $type
     * @param $object
     * @param $relation
     * @return mixed
     */
    public function get_relations_to($type, $object, $relation = '', $orderBy = '', $max = -1)
    {
        if (str_ends_with($relation, '.invert')) {
            return $this->get_relations_from($type, $object, substr($relation, 0, -7), $orderBy, $max);
        }

        $cond = [
            'target_type' => $type,
            'target_itemId' => $object
        ];

        $fields = [
            'relationId',
            'relation',
            'type' => 'source_type',
            'itemId' => 'source_itemId',
            'fieldId' => 'source_fieldId',
            'metaItemId' => 'metadata_itemId',
        ];

        $cond = $this->apply_relation_condition($relation, $cond);
        return $this->table->fetchAll($fields, $cond, $max, -1, $orderBy);
    }

    /**
     * Get transitive (multi-hop) relations via iterative traversal.
     *
     * @param string $type starting object type
     * @param string $object starting object ID
     * @param string $relation optional relation filter (wildcard supported)
     * @param int $maxDepth max hops (1-10)
     * @param array $excludeLevels levels to exclude (e.g. [1])
     * @param int $maxPerLevel limit per level
     *
     * @return array relations grouped by depth: [depth => [relations]]
     */
    public function get_transitive_relations(
        string $type,
        string $object,
        string $relation = '',
        int $maxDepth = 3,
        array $excludeLevels = [],
        int $maxPerLevel = 50
    ): array {
        // Validate parameters
        $maxDepth = max(1, min(10, (int)$maxDepth));
        $maxPerLevel = max(1, min(500, (int)$maxPerLevel));
        $excludeLevels = array_flip($excludeLevels);

        // Prepare relation filter
        $relation = TikiFilter::get('attribute_type')->filter($relation);
        $relationFilter = $relation;
        if ($relation && str_ends_with($relation, '.')) {
            $relationFilter .= '%';
        } elseif (! $relation) {
            $relationFilter = '%';
        }

        // Recursive CTE Query
        $query = "
            WITH RECURSIVE transitive_relations (depth, target_type, target_itemId, relation, relationId, path) AS (
                SELECT
                    1,
                    target_type,
                    target_itemId,
                    relation,
                    relationId,
                    CONCAT('|', source_type, ':', source_itemId, '|', target_type, ':', target_itemId, '|')
                FROM tiki_object_relations
                WHERE source_type = ? AND source_itemId = ? AND relation LIKE ?

                UNION DISTINCT

                SELECT
                    tr.depth + 1,
                    r.target_type,
                    r.target_itemId,
                    r.relation,
                    r.relationId,
                    CONCAT(tr.path, r.target_type, ':', r.target_itemId, '|')
                FROM tiki_object_relations r
                INNER JOIN transitive_relations tr ON r.source_type = tr.target_type AND r.source_itemId = tr.target_itemId
                WHERE tr.depth < ? 
                AND r.relation LIKE ?
                AND INSTR(tr.path, CONCAT('|', r.target_type, ':', r.target_itemId, '|')) = 0
            )
            SELECT * FROM transitive_relations ORDER BY depth ASC, relationId ASC
        ";

        $bindVars = [
            $type,
            $object,
            $relationFilter,
            $maxDepth,
            $relationFilter
        ];

        $rows = $this->fetchAll($query, $bindVars);

        $results = [];
        $counts = [];

        foreach ($rows as $row) {
            $depth = (int)$row['depth'];

            // Skip excluded levels
            if (isset($excludeLevels[$depth])) {
                continue;
            }

            // Enforce maxPerLevel limit in PHP
            if (! isset($counts[$depth])) {
                $counts[$depth] = 0;
            }
            if ($counts[$depth] >= $maxPerLevel) {
                continue;
            }

            // Format result to match previous output structure
            $results[$depth][] = [
                'type' => $row['target_type'],
                'itemId' => $row['target_itemId'],
                'relation' => $row['relation'],
                'relationId' => $row['relationId'],
            ];
            $counts[$depth]++;
        }

        return $results;
    }

    /**
     * The relation must contain at least two dots and only lowercase letters.
     * NAMESPACE management and relation naming.
     * Please see http://dev.tiki.org/Object+Attributes+and+Relations for guidelines on
     * relation naming, and document new tiki.*.* names that you add.
     * (also grep "add_relation" just in case there are undocumented names already used)
     */
    public function add_relation($relation, $src_type, $src_object, $target_type, $target_object, $ignoreExisting = false, $src_field_id = null, $metadata_item_id = null)
    {
        global $user;

        $relation = TikiFilter::get('attribute_type')->filter($relation);

        if (str_ends_with($relation, '.invert')) {
            return $this->add_relation(substr($relation, 0, -7), $target_type, $target_object, $src_type, $src_object, $ignoreExisting, $src_field_id, $metadata_item_id);
        }

        if ($relation) {
            if (! $id = $this->get_relation_id($relation, $src_type, $src_object, $target_type, $target_object, $src_field_id)) {
                $id = $this->table->insert(
                    [
                        'relation' => $relation,
                        'source_type' => $src_type,
                        'source_itemId' => $src_object,
                        'source_fieldId' => $src_field_id,
                        'target_type' => $target_type,
                        'target_itemId' => $target_object,
                        'metadata_itemId' => $metadata_item_id,
                    ]
                );
            } elseif ($ignoreExisting) {
                return 0;
            }

            // Array written to match event trigger that was previously in wikiplugin_addrelation
            TikiLib::events()->trigger('tiki.social.relation.add', [
                'relation' => $relation,
                'sourcetype' => $src_type,
                'sourceobject' => $src_object,
                'sourcefield' => $src_field_id,
                'type' => $target_type,
                'object' => $target_object,
                'user' => $user,
            ]);

            TikiLib::lib('tiki')->refresh_index($src_type, $src_object);
            TikiLib::lib('tiki')->refresh_index($target_type, $target_object);
            return $id;
        } else {
            return 0;
        }
    }

    /**
     * @param $relation
     * @param $src_type
     * @param $src_object
     * @param $target_type
     * @param $target_object
     * @return int
     */
    public function get_relation_id($relation, $src_type, $src_object, $target_type, $target_object, $src_field_id = null)
    {
        $relation = TikiFilter::get('attribute_type')->filter($relation);

        if (str_ends_with($relation, '.invert')) {
            return $this->get_relation_id(substr($relation, 0, -7), $target_type, $target_object, $src_type, $src_object);
        }

        $id = 0;
        if ($relation) {
            $cond = [
                'relation' => $relation,
                'source_type' => $src_type,
                'source_itemId' => $src_object,
                'target_type' => $target_type,
                'target_itemId' => $target_object,
            ];
            if ($src_field_id) {
                $cond['source_fieldId'] = $src_field_id;
            }
            $id = $this->table->fetchOne(
                'relationId',
                $cond
            );
        }
        return $id;
    }

    /**
     * @param $relation_prefix
     * @param $src_type
     * @param $src_object
     * @param $target_type
     * @param $target_object
     * @return array
     */
    public function get_relations_by_prefix($relation_prefix, $src_type, $src_object, $target_type, $target_object)
    {
        $ids = [];
        if ($relation_prefix) {
            $ids = $this->table->fetchAll(
                [],
                [
                    'relation' => $this->table->like($relation_prefix . ".%"),
                    'source_type' => $src_type,
                    'source_itemId' => $src_object,
                    'target_type' => $target_type,
                    'target_itemId' => $target_object,
                ]
            );
        }
        return $ids;
    }

    /**
     * @param $relation
     * @param $type
     * @param $object
     * @param $get_invert default=false
     * @return int
     */
    public function get_relation_count($relation, $type, $object = null, $get_invert = false)
    {
        $relation = TikiFilter::get('attribute_type')->filter($relation);

        if (! $relation) {
            return 0;
        }

        if ($get_invert) {
            $count = $this->table->fetchCount(
                array_filter([
                    'relation' => $relation,
                    'source_type' => $type,
                    'source_itemId' => $object,
                ])
            );
        } else {
            $count = $this->table->fetchCount(
                array_filter([
                    'relation' => $relation,
                    'target_type' => $type,
                    'target_itemId' => $object,
                ])
            );
        }
        return $count;
    }

    public function relation_exists($relation, $type)
    {
        return $this->get_relation_count($relation, $type) || $this->get_relation_count($relation, $type, null, true);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function get_relation($id)
    {
        return $this->table->fetchFullRow(
            [
                'relationId' => $id,
            ]
        );
    }

    /**
     * @param $id
     */
    public function remove_relation($id)
    {
        global $user;

        $relation_info = $this->get_relation($id);
        $this->table->delete(
            [
                'relationId' => $id,
            ]
        );
        $this->table('tiki_object_attributes')->deleteMultiple(
            [
                'type' => 'relation',
                'itemId' => $id,
            ]
        );

        if (! empty($relation_info['metadata_itemId'])) {
            TikiLib::lib('trk')->remove_tracker_item($relation_info['metadata_itemId'], true);
        }

        TikiLib::events()->trigger('tiki.social.relation.remove', [
            'relation' => $relation_info['relation'],
            'sourcetype' => $relation_info['source_type'],
            'sourceobject' => $relation_info['source_itemId'],
            'sourcefield' => $relation_info['source_fieldId'],
            'type' => $relation_info['target_type'],
            'object' => $relation_info['target_itemId'],
            'user' => $user,
        ]);

        TikiLib::lib('tiki')->refresh_index($relation_info['source_type'], $relation_info['source_itemId']);
        TikiLib::lib('tiki')->refresh_index($relation_info['target_type'], $relation_info['target_itemId']);
    }

    /**
     * Remove all relations from that type and source items belonging to that tracker
     * @param $relation - the relation type
     * @param $fieldId - the field where this relation was used
     */
    public function remove_relation_type($relation, $fieldId)
    {
        $results = $this->table->fetchAll(['metadata_itemId'], [
            'relation' => $relation,
            'source_fieldId' => $fieldId,
            'metadata_itemId' => $this->table->not(''),
        ]);
        foreach ($results as $row) {
            TikiLib::lib('trk')->remove_tracker_item($row['metadata_itemId'], true);
        }
        return $this->query("DELETE FROM tiki_object_relations
            WHERE relation = ?
            AND source_type = 'trackeritem'
            AND source_fieldId = ?", [$relation, $fieldId]);
    }

    /**
     * Remove all relations of a type for single object
     * @param $fromType - object type
     * @param $fromId - object itemId
     * @param $relationType - relation type originating from that object
     */
    public function remove_relations_from($fromType, $fromId, $relationType)
    {
        $results = $this->table->fetchAll(['metadata_itemId'], [
            'relation' => $relationType,
            'source_type' => $fromType,
            'source_itemId' => $fromId,
            'metadata_itemId' => $this->table->not(''),
        ]);
        foreach ($results as $row) {
            TikiLib::lib('trk')->remove_tracker_item($row['metadata_itemId'], true);
        }
        return $this->table->deleteMultiple(
            [
                'relation' => $relationType,
                'source_type' => $fromType,
                'source_itemId' => $fromId
            ]
        );
    }

    /**
     * Changes to relation name should update existing relation table entries
     *
     * @param $from - old relation name
     * @param $to - new relation name
     * @param $fieldId - the field using this relation
     */
    public function update_relation($from, $to, $fieldId)
    {
        $this->table->updateMultiple([
            'relation' => $to
        ], [
            'relation' => $from,
            'source_fieldId' => $fieldId
        ]);
    }

    public function updateMetadataItemId(int $id, int $metadata_item_id)
    {
        return $this->table->update(['metadata_itemId' => $metadata_item_id], ['relationId' => $id]);
    }

    public function getAllRelatedTrackerItems()
    {
        $rows = $this->table->fetchAll(['source_itemId', 'target_itemId'], ['source_type' => 'trackeritem', 'target_type' => 'trackeritem']);
        return array_unique(array_merge(array_column($rows, 'source_itemId'), array_column($rows, 'target_itemId')));
    }

    /**
     * @param $relation
     * @param $cond
     * @param $vars
     */
    private function apply_relation_condition($relation, $cond)
    {
        $relation = TikiFilter::get('attribute_type')->filter($relation);

        if ($relation) {
            if (str_ends_with($relation, '.')) {
                $relation .= '%';
            }

            $cond['relation'] = $this->table->like($relation);
        }

        return $cond;
    }
}
