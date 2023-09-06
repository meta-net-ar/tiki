<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_federated_list()
{
    return [
        'federated_enabled' => [
            'name' => tr('Federated search'),
            'description' => tr('Search through alternate site indices.'),
            'type' => 'flag',
            'default' => 'n',
            'hint' => tr('Elasticsearch or Manticore Search is required'),
            'dependencies' => ['feature_search'],
        ],
        'federated_elastic_url' => [
            'name' => tra('Elasticsearch tribe node URL'),
            'description' => tra('URL of the tribe client node accessing multiple clusters.'),
            'type' => 'text',
            'filter' => 'url',
            'default' => '',
            'size' => 40,
        ],
        'federated_manticore_index_prefix' => [
            'name' => tra('Manticore distributed index prefix'),
            'description' => tra('The prefix used when creating distributed index in Manticore. This needs to be the same for all sites participating in the federation.'),
            'type' => 'text',
            'default' => 'tiki_',
            'size' => 40,
        ],
    ];
}
