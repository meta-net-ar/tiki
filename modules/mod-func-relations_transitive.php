<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * @return array
 */
function module_relations_transitive_info()
{
    return [
        'name' => tra('Transitive Relations'),
        'description' => tra('Shows objects related through multi-hop connections (relations of relations).'),
        'prefs' => [],
        'params' => [
            'type' => [
                'required' => false,
                'name' => tra('Object Type'),
                'description' => tra('Type of object to show relations for (e.g. trackeritem). If not provided, uses current page context.'),
                'filter' => 'text',
                'default' => '',
            ],
            'object' => [
                'required' => false,
                'name' => tra('Object ID'),
                'description' => tra('ID of the object to show relations for. If not provided, uses current page context.'),
                'filter' => 'text',
                'default' => '',
            ],
            'relation' => [
                'required' => false,
                'name' => tra('Relation'),
                'description' => tra('Relation qualifier to filter by (supports wildcard with trailing dot). Leave empty for all relations.'),
                'filter' => 'text',
                'default' => '',
            ],
            'maxdepth' => [
                'required' => false,
                'name' => tra('Maximum Depth'),
                'description' => tra('Maximum number of hops to traverse (1-10).'),
                'filter' => 'int',
                'default' => 3,
            ],
            'excludelevels' => [
                'required' => false,
                'name' => tra('Exclude Levels'),
                'description' => tra('Comma-separated list of depth levels to exclude. For example, "1" excludes direct (1st-level) relations.'),
                'filter' => 'text',
                'default' => '',
            ],
            'maxperlevel' => [
                'required' => false,
                'name' => tra('Max Per Level'),
                'description' => tra('Maximum number of results to show per depth level.'),
                'filter' => 'int',
                'default' => 50,
            ],
        ],
        'common_params' => ['nonums', 'rows']
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_relations_transitive($mod_reference, $module_params)
{
    $smarty = TikiLib::lib('smarty');
    $relationlib = TikiLib::lib('relation');
    $objectlib = TikiLib::lib('object');

    // Determine context object
    $objectType = ! empty($module_params['type']) ? $module_params['type'] : '';
    $objectId = ! empty($module_params['object']) ? $module_params['object'] : '';

    if (empty($objectType) || empty($objectId)) {
        $object = current_object();
        if (! empty($object)) {
            $objectType = $object['type'];
            $objectId = $object['object'];
        }
    }

    if (empty($objectType) || empty($objectId)) {
        $smarty->assign('mod_transitive_relations', []);
        $smarty->assign('mod_transitive_has_results', false);
        $smarty->assign('mod_transitive_error', tra('No object specified. Use type and object parameters or view this module on an object page.'));
        return;
    }


    $relation = isset($module_params['relation']) ? $module_params['relation'] : '';
    $maxDepth = isset($module_params['maxdepth']) ? (int)$module_params['maxdepth'] : 3;
    $maxPerLevel = isset($module_params['maxperlevel']) ? (int)$module_params['maxperlevel'] : 50;


    $excludeLevels = [];
    if (! empty($module_params['excludelevels'])) {
        $excludeLevels = array_map('intval', array_map('trim', explode(',', $module_params['excludelevels'])));
    }

    // Get transitive relations
    $transitiveRelations = $relationlib->get_transitive_relations(
        $objectType,
        $objectId,
        $relation,
        $maxDepth,
        $excludeLevels,
        $maxPerLevel
    );

    // Collect all objects to fetch titles in batch
    $objectsToFetch = [];
    foreach ($transitiveRelations as $depth => $relations) {
        foreach ($relations as $rel) {
            $objectsToFetch[] = [
                'type' => $rel['type'],
                'id' => $rel['itemId'],
            ];
        }
    }

    $titles = $objectlib->get_titles($objectsToFetch, '');

    // Format for display
    $enrichedResults = [];
    $totalCount = 0;

    foreach ($transitiveRelations as $depth => $relations) {
        $enrichedLevel = [];

        foreach ($relations as $rel) {
            $key = $rel['type'] . ':' . $rel['itemId'];
            $title = $titles[$key] ?? '';

            $enrichedLevel[] = [
                'type' => $rel['type'],
                'itemId' => $rel['itemId'],
                'title' => $title,
                'relation' => $rel['relation'],
                'relationId' => $rel['relationId'],
            ];

            $totalCount++;
        }

        if (! empty($enrichedLevel)) {
            $enrichedResults[$depth] = [
                'level' => $depth,
                'label' => $depth == 1 ? tra('Direct Relations') :
                           ($depth == 2 ? tra('2nd Level Relations') :
                           ($depth == 3 ? tra('3rd Level Relations') :
                            tr('%0th Level Relations', $depth))),
                'items' => $enrichedLevel,
                'count' => count($enrichedLevel),
            ];
        }
    }

    $smarty->assign('mod_transitive_relations', $enrichedResults);
    $smarty->assign('mod_transitive_has_results', $totalCount > 0);
    $smarty->assign('mod_transitive_total_count', $totalCount);
    $smarty->assign('mod_transitive_max_depth', $maxDepth);
}
