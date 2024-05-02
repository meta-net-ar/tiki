<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_ContentSource_ForumSource implements Search_ContentSource_Interface
{
    private $db;

    public function __construct()
    {
        $this->db = TikiDb::get();
    }

    public function getDocuments()
    {
        return $this->db->table('tiki_forums')->fetchColumn('forumId', []);
    }

    public function getDocument($objectId, Search_Type_Factory_Interface $typeFactory): array|false
    {
        $lib = TikiLib::lib('comments');

        $item = $lib->get_forum($objectId);

        if (! $item) {
            return false;
        }

        $data = [
            'title' => $typeFactory->sortable($item['name']),
            'creation_date' => $typeFactory->timestamp($item['created']),
            'date' => $typeFactory->timestamp($item['created']),
            'description' => $typeFactory->plaintext($item['description']),
            'language' => $typeFactory->identifier($item['forumLanguage'] ?: 'unknown'),

            'forum_section' => $typeFactory->identifier($item['section']),

            'view_permission' => $typeFactory->identifier('tiki_p_forum_read'),
        ];

        return $data;
    }

    public function getProvidedFields(): array
    {
        return [
            'title',
            'creation_date',
            'date',
            'description',
            'language',

            'forum_section',

            'view_permission',
        ];
    }

    public function getProvidedFieldTypes(): array
    {
        return [
            'title' => 'sortable',
            'creation_date' => 'timestamp',
            'date' => 'timestamp',
            'description' => 'plaintext',
            'language' => 'identifier',

            'forum_section' => 'identifier',

            'view_permission' => 'identifier',
        ];
    }

    public function getGlobalFields(): array
    {
        return [
            'title' => true,
            'description' => true,
            'date' => true,
        ];
    }
}
