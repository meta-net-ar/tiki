<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

class Search_ContentSource_PollSource implements Search_ContentSource_Interface
{
    private $db;

    public function __construct()
    {
        $this->db = TikiDb::get();
    }

    public function getDocuments()
    {
        return $this->db->table('tiki_polls')->fetchColumn('pollId', []);
    }

    public function getDocument($objectId, Search_Type_Factory_Interface $typeFactory): array|false
    {
        $poll = $this->db->table('tiki_polls')->fetchRow([], ['pollId' => $objectId]);
        if (! $poll) {
            return false;
        }

        return [
            'title' => $typeFactory->plaintext($poll['title']),
            'poll_votes' => $typeFactory->numeric($poll['votes']),
            'poll_active' => $typeFactory->plaintext($poll['active']),
            'poll_publish_date' => $typeFactory->timestamp($poll['publishDate']),
            'poll_consideration_span' => $typeFactory->numeric($poll['voteConsiderationSpan']),
            'view_permission' => $typeFactory->identifier('tiki_p_vote_poll'),
        ];
    }

    public function getProvidedFields(): array
    {
        return [
            'title',
            'poll_votes',
            'poll_active',
            'poll_publish_date',
            'poll_consideration_span',
            'view_permission',
        ];
    }

    public function getGlobalFields(): array
    {
        return [
            'title' => true,
        ];
    }

    public function getProvidedFieldTypes(): array
    {
        return [
            'title' => 'plaintext',
            'poll_votes' => 'numeric',
            'poll_active' => 'plaintext',
            'poll_publish_date' => 'timestamp',
            'poll_consideration_span' => 'numeric',
            'view_permission' => 'identifier',
        ];
    }
}
