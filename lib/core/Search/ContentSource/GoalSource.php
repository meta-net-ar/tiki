<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Search\ContentSource;

use Search_ContentSource_Interface;
use Search_Type_Factory_Interface;
use TikiDb;

class GoalSource implements Search_ContentSource_Interface
{
    private $table;

    public function __construct()
    {
        $this->table = TikiDb::get()->table('tiki_goals');
    }

    public function getDocuments()
    {
        return $this->table->fetchColumn('goalId', []);
    }

    public function getDocument($objectId, Search_Type_Factory_Interface $typeFactory): array|false
    {
        global $prefs;

        $goal = $this->table->fetchRow(['name', 'type', 'description', 'enabled', 'daySpan', 'from', 'to', 'eligible', 'conditions', 'rewards'], [
            'goalId' => $objectId,
        ]);

        if ($goal) {
            return [
                'title' => $typeFactory->sortable($goal['name']),
                'description' => $typeFactory->plaintext($goal['description']),
                'goal_type' => $typeFactory->identifier($goal['type']),
                'enabled' => $typeFactory->numeric($goal['enabled']),
                'day_span' => $typeFactory->numeric($goal['daySpan']),
                'span_from' => $typeFactory->timestamp($goal['from'] ? strtotime($goal['from'] . ' UTC') : null),
                'span_to' => $typeFactory->timestamp($goal['to'] ? strtotime($goal['to'] . ' UTC') : null),
                'eligible' => $typeFactory->json(json_decode($goal['eligible'])),
                'conditions' => $typeFactory->json(json_decode($goal['conditions'])),
                'rewards' => $typeFactory->json(json_decode($goal['rewards'])),
                'allowed_groups' => $typeFactory->multivalue(['Anonymous', 'Registered']),
            ];
        } else {
            return false;
        }
    }

    public function getProvidedFields(): array
    {
        return ['title', 'description', 'goal_type', 'enabled', 'day_span', 'span_from', 'span_to', 'eligible', 'conditions', 'rewards'];
    }

    public function getProvidedFieldTypes(): array
    {
        return [
            'title' => 'sortable',
            'description' => 'plaintext',
            'goal_type' => 'identifier',
            'enabled' => 'numeric',
            'day_span' => 'numeric',
            'span_from' => 'timestamp',
            'span_to' => 'timestamp',
            'eligible' => 'json',
            'conditions' => 'json',
            'rewards' => 'json',
        ];
    }

    public function getGlobalFields(): array
    {
        return [
            'title' => true,
            'description' => true,
        ];
    }
}
