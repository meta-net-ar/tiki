<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_ContentSource_StructureSource implements Search_ContentSource_Interface
{
    private $db;

    public function __construct()
    {
        $this->db = TikiDb::get();
    }

    public function getDocuments()
    {
        global $prefs;
        if ($prefs['feature_wiki_structure'] !== 'y') {
            return [];
        }
        return $this->db->table('tiki_structures')->fetchColumn('page_ref_id', []);
    }

    public function getDocument($objectId, Search_Type_Factory_Interface $typeFactory): array|false
    {
        global $prefs;
        if ($prefs['feature_wiki_structure'] !== 'y') {
            return false;
        }
        $structlib = TikiLib::lib('struct');
        $structure = $structlib->s_get_page_info($objectId);

        if (! $structure) {
            return false;
        }

        $data = [
            'title' => $typeFactory->plaintext(! empty($structure['page_alias']) ? $structure['page_alias'] : $structure['pageName']),
            'structure_page_ref_id' => $typeFactory->identifier($structure['page_ref_id']),
            'structure_id' => $typeFactory->identifier($structure['structure_id']),
            'structure_parent_id' => $typeFactory->identifier($structure['parent_id']),
            'structure_page_id' => $typeFactory->identifier($structure['page_id']),
            'structure_page_version' => $typeFactory->numeric(0),
            'structure_page_alias' => $typeFactory->plaintext($structure['page_alias'] ?? ''),
            'structure_pos' => $typeFactory->numeric($structure['pos']),
            'structure_name' => $typeFactory->plaintext($structure['pageName']),

            'view_permission' => $typeFactory->identifier('tiki_p_view'),
        ];

        return $data;
    }

    public function getProvidedFields(): array
    {
        return [
            'title',
            'structure_page_ref_id',
            'structure_id',
            'structure_parent_id',
            'structure_page_id',
            'structure_page_version',
            'structure_page_alias',
            'structure_pos',
            'structure_name',
            'view_permission',
        ];
    }

    public function getProvidedFieldTypes(): array
    {
        return [
            'structure_page_ref_id' => 'identifier',
            'structure_id' => 'identifier',
            'structure_parent_id' => 'identifier',
            'structure_page_id' => 'identifier',
            'structure_page_version' => 'numeric',
            'structure_page_alias' => 'plaintext',
            'structure_pos' => 'numeric',
            'view_permission' => 'identifier',
        ];
    }

    public function getGlobalFields(): array
    {
        return [
            'title' => true,
        ];
    }
}
