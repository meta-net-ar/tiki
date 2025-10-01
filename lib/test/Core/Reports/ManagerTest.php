<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Reports_ManagerTest extends TikiTestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Reports_Send
     */
    public $reportsSend;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\UsersLib
     */
    public $usersLib;
    protected $obj;

    protected $reportsUsers;

    protected $reportsCache;

    protected function setUp(): void
    {
        $this->reportsUsers = $this->getMockBuilder('Reports_Users')->disableOriginalConstructor()->getMock();
        $this->reportsCache = $this->getMockBuilder('Reports_Cache')->disableOriginalConstructor()->getMock();
        $this->reportsSend = $this->getMockBuilder('Reports_Send')->disableOriginalConstructor()->getMock();
        $this->usersLib = $this->getMockBuilder('UsersLib')->disableOriginalConstructor()->getMock();

        $this->obj = new Reports_Manager($this->reportsUsers, $this->reportsCache, $this->reportsSend, $this->usersLib);
    }

    public function testDeleteShouldCallMethodToDeleteUserPreferenceAndMethodToDeleteCache()
    {
        $user = 'test';

        $this->reportsUsers->expects($this->once())->method('delete')->with($user);
        $this->reportsCache->expects($this->once())->method('delete')->with($user);

        $this->obj->delete($user);
    }

    public function testAddToCacheShouldGetUsersUsingPeriodicReportsAndCallMethodToAddToCache()
    {
        $watches = [
            ['user' => 'admin'],
            ['user' => 'test'],
            ['user' => 'notUsingPeriodicReports']
        ];

        $data = ['event' => 'wiki_page_changed'];

        $users = ['admin', 'test'];

        $this->reportsUsers->expects($this->once())->method('getAllUsers')
            ->willReturn($users);

        $this->reportsCache->expects($this->once())->method('add')->with($watches, $data, $users);

        $this->obj->addToCache($watches, $data);
    }

    public function testSaveShouldCallReportsUsersSave()
    {
        $user = 'admin';
        $interval = 'daily';
        $view = 'detailed';
        $type = 'html';
        $always_email = 1;

        $this->reportsUsers->expects($this->once())->method('save')
            ->with($this->equalTo($user), $this->equalTo($interval), $this->equalTo($view), $this->equalTo($type), $this->equalTo($always_email));

        $this->obj->save($user, $interval, $view, $type, $always_email);
    }
}
