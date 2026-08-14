<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @group unit
 *
 */

class Perms_ApplierTest extends TikiTestCase
{
    public function testApplyFromNothing()
    {
        $global = new Perms_Reflection_PermissionSet();
        $global->add('Anonymous', 'view');
        $object = new Perms_Reflection_PermissionSet();

        $newSet = new Perms_Reflection_PermissionSet();
        $newSet->add('Registered', 'view');
        $newSet->add('Registered', 'edit');

        $target = $this->createMock('Perms_Reflection_Container');

        // Set up consecutive return values for 'getDirectPermissions' method
        $target->method('getDirectPermissions')
            ->willReturnOnConsecutiveCalls($object, null);
        // Set up consecutive return values for 'getParentPermissions' method
        $target->method('getParentPermissions')
            ->willReturnOnConsecutiveCalls($global, null);
        // Define a callback function for 'add' method calls
        $addCallback = $this->returnCallback(function ($arg1, $arg2) {
            return null;
        });

        // Set expectations for 'add' method calls
        $target->expects($this->exactly(2))
            ->method('add')
            ->with($this->logicalOr(
                $this->equalTo('Registered', 'view'),
                $this->equalTo('Registered', 'edit')
            ))
            ->will($addCallback);
        $applier = new Perms_Applier();
        $applier->addObject($target);
        $applier->apply($newSet);
    }

    public function testFromExistingSet()
    {
        $global = new Perms_Reflection_PermissionSet();
        $global->add('Anonymous', 'view');

        $object = new Perms_Reflection_PermissionSet();
        $object->add('Registered', 'view');
        $object->add('Registered', 'edit');

        $newSet = new Perms_Reflection_PermissionSet();
        $newSet->add('Registered', 'view');
        $newSet->add('Editor', 'edit');
        $newSet->add('Editor', 'view_history');

        $target = $this->createMock('Perms_Reflection_Container');

        // Set up consecutive return values for 'getDirectPermissions' method
        $target->method('getDirectPermissions')
            ->willReturnOnConsecutiveCalls($object);
        // Set up consecutive return values for 'getParentPermissions' method
        $target->method('getParentPermissions')
            ->willReturnOnConsecutiveCalls($global);
        // Set expectations for 'add' method calls without using 'with()'
        $target->expects($this->exactly(2))
            ->method('add')
            ->willReturnMap([
                ['Editor', 'edit', null],
                ['Editor', 'view_history', null],
            ]);
        // Set expectations for 'remove' method call without using 'with()'
        $target->expects($this->once())
            ->method('remove')
            ->willReturnMap([
                ['Registered', 'edit', null],
            ]);
        $applier = new Perms_Applier();
        $applier->addObject($target);
        $applier->apply($newSet);
    }

    public function testAsParent()
    {
        $global = new Perms_Reflection_PermissionSet();
        $global->add('Anonymous', 'view');
        $object = new Perms_Reflection_PermissionSet();
        $object->add('Registered', 'view');
        $object->add('Registered', 'edit');

        $newSet = new Perms_Reflection_PermissionSet();
        $newSet->add('Anonymous', 'view');

        $target = $this->createMock('Perms_Reflection_Container');

        $target->method('getDirectPermissions')
            ->willReturn($object);
        $target->method('getParentPermissions')
            ->willReturn($global);
        // Capture the arguments passed to the 'remove' method
        $capturedArgs = [];

        $target->expects($this->exactly(2))
            ->method('remove')
            ->willReturnCallback(function ($arg1, $arg2) use (&$capturedArgs) {
                $capturedArgs[] = [$arg1, $arg2];
            });
        $applier = new Perms_Applier();
        $applier->addObject($target);
        $applier->apply($newSet);
        // Assert on the captured arguments
        $this->assertEquals([['Registered', 'view'], ['Registered', 'edit']], $capturedArgs);
    }

    public function testParentNotAvailable()
    {
        $global = new Perms_Reflection_PermissionSet();
        $global->add('Anonymous', 'view');

        $newSet = new Perms_Reflection_PermissionSet();
        $newSet->add('Anonymous', 'view');
        $newSet->add('Registered', 'edit');
        $target = $this->createMock('Perms_Reflection_Container');
        $target->method('getDirectPermissions')
            ->willReturn($global);
        $target->method('getParentPermissions')
            ->willReturn(null);
        $target->expects($this->once())
            ->method('add')
            ->with($this->equalTo('Registered'), $this->equalTo('edit'));
        $applier = new Perms_Applier();
        $applier->addObject($target);
        $applier->apply($newSet);
    }

    public function testMultipleTargets()
    {
        $global = new Perms_Reflection_PermissionSet();
        $global->add('Anonymous', 'view');
        $newSet = new Perms_Reflection_PermissionSet();
        $newSet->add('Anonymous', 'view');
        $newSet->add('Registered', 'edit');

        $targets = [];

        $target1 = $this->createMock('Perms_Reflection_Container');
        $target1->method('getDirectPermissions')
            ->willReturn($global);
        $target1->method('getParentPermissions')
            ->willReturn(null);
        // Capture the arguments passed to the 'add' method
        $capturedArgs1 = [];
        $target1->expects($this->once())
            ->method('add')
            ->willReturnCallback(function ($arg1, $arg2) use (&$capturedArgs1) {
                $capturedArgs1[] = [$arg1, $arg2];
            });
        $targets[] = $target1;

        $target2 = $this->createMock('Perms_Reflection_Container');
        $target2->method('getDirectPermissions')
            ->willReturn(new Perms_Reflection_PermissionSet());
        $target2->method('getParentPermissions')
            ->willReturn(null);
        // Capture the arguments passed to the 'add' method
        $capturedArgs2 = [];
        $target2->expects($this->exactly(2))
            ->method('add')
            ->willReturnCallback(function ($arg1, $arg2) use (&$capturedArgs2) {
                $capturedArgs2[] = [$arg1, $arg2];
            });
        $targets[] = $target2;

        $applier = new Perms_Applier();
        foreach ($targets as $target) {
            $applier->addObject($target);
        }
        $applier->apply($newSet);

        // Assert on the captured arguments
        $this->assertEquals([['Registered', 'edit']], $capturedArgs1);
        $this->assertEquals([['Anonymous', 'view'], ['Registered', 'edit']], $capturedArgs2);
    }

    public function testRestrictChangedPermissions()
    {
        $before = new Perms_Reflection_PermissionSet();
        $before->add('Admin', 'admin');
        $before->add('Registered', 'edit');
        $before->add('Registered', 'view');

        $target = $this->createMock('Perms_Reflection_Container');
        $target->expects($this->once())
            ->method('getDirectPermissions')
            ->willReturn($before);
        $target->expects($this->once())
            ->method('getParentPermissions')
            ->willReturn(new Perms_Reflection_PermissionSet());
        $target->expects($this->once())
            ->method('add')
            ->with($this->equalTo('Registered'), $this->equalTo('view_history'));

        $newSet = new Perms_Reflection_PermissionSet();
        $newSet->add('Registered', 'edit');
        $newSet->add('Registered', 'view');
        $newSet->add('Registered', 'view_history');
        $newSet->add('Registered', 'admin');

        $applier = new Perms_Applier();
        $applier->addObject($target);
        $applier->restrictPermissions(['view', 'view_history', 'edit']);
        $applier->apply($newSet);
    }

    public function testNoRevertToParentWithRestrictions()
    {
        $current = new Perms_Reflection_PermissionSet();
        $current->add('Anonymous', 'view');

        $parent = new Perms_Reflection_PermissionSet();
        $parent->add('Anonymous', 'view');
        $parent->add('Registered', 'edit');
        $parent->add('Admins', 'admin');

        $newSet = new Perms_Reflection_PermissionSet();
        $newSet->add('Anonymous', 'view');
        $newSet->add('Registered', 'edit');
        $newSet->add('Admins', 'admin');

        $target = $this->createMock('Perms_Reflection_Container');
        $target->expects($this->once())
            ->method('getDirectPermissions')
            ->willReturn($current);
        $target->expects($this->once())
            ->method('getParentPermissions')
            ->willReturn($parent);
        $target->expects($this->once())
            ->method('add')
            ->with($this->equalTo('Registered'), $this->equalTo('edit'));

        $applier = new Perms_Applier();
        $applier->addObject($target);
        $applier->restrictPermissions(['view', 'edit']);
        $applier->apply($newSet);
    }

    public function testRevertIfWithinBounds()
    {
        $current = new Perms_Reflection_PermissionSet();
        $current->add('Anonymous', 'view');

        $parent = new Perms_Reflection_PermissionSet();
        $parent->add('Anonymous', 'view');
        $parent->add('Registered', 'edit');
        $parent->add('Admins', 'admin');

        $newSet = new Perms_Reflection_PermissionSet();
        $newSet->add('Anonymous', 'view');
        $newSet->add('Registered', 'edit');
        $newSet->add('Admins', 'admin');

        $target = $this->createMock('Perms_Reflection_Container');
        $target->expects($this->once())
            ->method('getDirectPermissions')
            ->willReturn($current);
        $target->expects($this->once())
            ->method('getParentPermissions')
            ->willReturn($parent);
        $target->expects($this->once())
            ->method('remove')
            ->with($this->equalTo('Anonymous'), $this->equalTo('view'));

        $applier = new Perms_Applier();
        $applier->addObject($target);
        $applier->restrictPermissions(['view', 'edit', 'admin']);
        $applier->apply($newSet);
    }
}
