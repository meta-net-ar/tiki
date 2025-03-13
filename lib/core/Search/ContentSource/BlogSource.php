<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_ContentSource_BlogSource implements Search_ContentSource_Interface
{
    private $db;

    public function __construct()
    {
        $this->db = TikiDb::get();
    }

    public function getDocuments()
    {
        return $this->db->table('tiki_blogs')->fetchColumn('blogId', []);
    }

    public function getDocument($objectId, Search_Type_Factory_Interface $typeFactory): array|false
    {
        $bloglib = TikiLib::lib('blog');

        $blog = $bloglib->get_blog($objectId);

        if (! $blog) {
            return false;
        }

        $data = [
            'title' => $typeFactory->sortable($blog['title']),
            'language' => $typeFactory->identifier('unknown'),
            'creation_date' => $typeFactory->timestamp($blog['created']),
            'modification_date' => $typeFactory->timestamp($blog['lastModif']),
            'contributors' => $typeFactory->multivalue([$blog['user']]),

            'blog_id' => $typeFactory->identifier($blog['blogId']),
            'blog_description' => $typeFactory->plaintext($blog['description']),
            'blog_posts' => $typeFactory->numeric($blog['posts']),

            'view_permission' => $typeFactory->identifier('tiki_p_read_blog'),
        ];

        return $data;
    }

    public function getProvidedFields(): array
    {
        return [
            'title',
            'language',
            'creation_date',
            'modification_date',
            'contributors',

            'blog_id',
            'blog_description',
            'blog_posts',

            'view_permission',
        ];
    }

    public function getProvidedFieldTypes(): array
    {
        return [
            'title' => 'sortable',
            'language' => 'identifier',
            'creation_date' => 'timestamp',
            'modification_date' => 'timestamp',
            'contributors' => 'multivalue',

            'blog_id' => 'identifier',
            'blog_description' => 'plaintext',
            'blog_posts' => 'numeric',

            'view_permission' => 'identifier',
        ];
    }

    public function getGlobalFields(): array
    {
        return [
            'title' => true,
            'blog_description' => true,
        ];
    }
}
