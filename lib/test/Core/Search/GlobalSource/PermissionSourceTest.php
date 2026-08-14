<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_GlobalSource_PermissionSourceTest extends PHPUnit\Framework\TestCase
{
    private $indexer;
    private $index;
    private $perms;

    protected function setUp(): void
    {
        $perms = new Perms();
        $perms->setCheckSequence(
            [
                $globalAlternate = new Perms_Check_Alternate('admin'),
                new Perms_Check_Direct(),
            ]
        );
        $perms->setResolverFactories(
            [
                new Perms_ResolverFactory_StaticFactory(
                    'global',
                    new Perms_Resolver_Static(
                        [
                            'Anonymous' => ['tiki_p_view', 'tiki_p_read_comments', 'tiki_p_tracker_view_comments'],
                            'Registered' => ['tiki_p_view', 'tiki_p_read_article', 'tiki_p_view_trackers', 'tiki_p_read_comments', 'tiki_p_tracker_view_comments'],
                        ]
                    )
                ),
            ]
        );

        $index = new Search_Index_Memory();
        $indexer = new Search_Indexer($index);

        $this->indexer = $indexer;
        $this->index = $index;
        $this->perms = $perms;
    }

    public function testSingleGroup()
    {
        $contentSource = new Search_ContentSource_Static(
            [
                'HomePage' => ['view_permission' => 'tiki_p_read_article'],
            ],
            ['view_permission' => 'identifier']
        );

        $this->indexer->addGlobalSource(new Search_GlobalSource_PermissionSource($this->perms));
        $this->indexer->addContentSource('wiki page', $contentSource);
        $this->indexer->rebuild();

        $document = $this->index->getDocument(0);

        $typeFactory = $this->index->getTypeFactory();
        $this->assertEquals($typeFactory->multivalue(['Registered']), $document['allowed_groups']);
    }

    public function testMultipleGroup()
    {
        $contentSource = new Search_ContentSource_Static(
            ['HomePage' => ['view_permission' => 'tiki_p_view'],],
            ['view_permission' => 'identifier']
        );

        $this->indexer->addGlobalSource(new Search_GlobalSource_PermissionSource($this->perms));
        $this->indexer->addContentSource('wiki page', $contentSource);
        $this->indexer->rebuild();

        $document = $this->index->getDocument(0);

        $typeFactory = $this->index->getTypeFactory();
        $this->assertEquals($typeFactory->multivalue(['Anonymous', 'Registered']), $document['allowed_groups']);
    }

    public function testNoMatches()
    {
        $contentSource = new Search_ContentSource_Static(
            ['HomePage' => ['view_permission' => 'tiki_p_do_stuff'],],
            ['view_permission' => 'identifier']
        );

        $this->indexer->addGlobalSource(new Search_GlobalSource_PermissionSource($this->perms));
        $this->indexer->addContentSource('wiki page', $contentSource);
        $this->indexer->rebuild();

        $document = $this->index->getDocument(0);

        $typeFactory = $this->index->getTypeFactory();
        $this->assertEquals($typeFactory->multivalue([]), $document['allowed_groups']);
    }

    public function testUndeclaredPermission()
    {
        $contentSource = new Search_ContentSource_Static(
            [
                'HomePage' => [],
            ],
            ['view_permission' => 'identifier']
        );

        $this->indexer->addGlobalSource(new Search_GlobalSource_PermissionSource($this->perms));
        $this->indexer->addContentSource('wiki page', $contentSource);
        $this->indexer->rebuild();

        $document = $this->index->getDocument(0);

        $typeFactory = $this->index->getTypeFactory();
        $this->assertEquals($typeFactory->multivalue([]), $document['allowed_groups']);
    }

    public function testCommentParentPermission()
    {
        $contentSource = new Search_ContentSource_Static(
            [
                'HomePage' => ['view_permission' => 'tiki_p_tracker_view_comments', 'parent_view_permission' => 'tiki_p_view_trackers', 'global_view_permission' => 'tiki_p_read_comments'],
            ],
            ['view_permission' => 'identifier', 'parent_view_permission' => 'identifier', 'global_view_permission' => 'identifier']
        );

        $this->indexer->addGlobalSource(new Search_GlobalSource_PermissionSource($this->perms));
        $this->indexer->addContentSource('trackeritem', $contentSource);
        $this->indexer->rebuild();

        $document = $this->index->getDocument(0);

        $typeFactory = $this->index->getTypeFactory();
        $this->assertEquals($typeFactory->multivalue(['Registered']), $document['allowed_groups']);
    }
}
