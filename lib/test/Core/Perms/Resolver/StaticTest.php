<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @group unit
 *
 */

class Perms_Resolver_StaticTest extends TikiTestCase
{
    public function testGroupNotDefined()
    {
        $static = new Perms_Resolver_Static([]);

        $this->assertFalse($static->check('view', []));
        $this->assertEquals([], $static->applicableGroups());
    }

    public function testNotRightGroup()
    {
        $static = new Perms_Resolver_Static(
            ['Registered' => ['view', 'edit'],]
        );

        $this->assertFalse($static->check('view', ['Anonymous']));
        $this->assertEquals(['Registered'], $static->applicableGroups());
    }

    public function testRightGroup()
    {
        $static = new Perms_Resolver_Static(
            [
                'Anonymous' => ['view'],
                'Registered' => ['view', 'edit'],
            ]
        );

        $this->assertTrue($static->check('edit', ['Anonymous', 'Registered']));
        $this->assertEquals(['Anonymous', 'Registered'], $static->applicableGroups());
    }

    public function testMergeStatic()
    {
        $static = new Perms_Resolver_Static(
            ['Registered' => ['view']]
        );
        $another = new Perms_Resolver_Static([
            'Anonymous' => ['view'],
            'Registered' => ['edit']
        ]);

        $static->merge($another);

        $this->assertTrue($static->check('edit', ['Anonymous', 'Registered']));
        $this->assertEquals(['Anonymous' => ['view' => true], 'Registered' => ['view' => true, 'edit' => true]], $static->known());
    }

    public function testMergeStaticAndDefault()
    {
        $static = new Perms_Resolver_Static(
            ['Registered' => ['view']]
        );
        $default = new Perms_Resolver_Default(true);

        $static->merge($default);

        $this->assertFalse($static->check('edit', ['Anonymous', 'Registered']));
    }
}
