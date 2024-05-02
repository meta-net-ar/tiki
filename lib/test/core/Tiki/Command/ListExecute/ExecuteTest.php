<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Symfony\Component\Console\Tester\CommandTester;
use Tiki\Command\Application;
use Tiki\Command\ListExecuteCommand;

class Tiki_Command_ListExecute_ExecuteTest extends TikiTestCase
{
    public $pluginSecurity;

    private const PAGE_NAME = 'pagetest';
    private const FINGERPRINT = 'listexecute-f8c66d0e749de7b51ca6d84d577db62b-0df3f8fa6a273b5283894cce2d1e8455-318000-200000';
    private const CONTENT_FOR_FINGERPRINT = '{LISTEXECUTE()}
    {filter type="trackeritem"}

    {ACTION(name="action" group="Triage Team")}
        {step action="change_status" from="p" to="c"}
        {step action="email" subject="Issue closed" content_field="email_content" to_field="tracker_field_senderEmail"}
    {ACTION}
    
    {FORMAT(name="email_content")}
{FORMAT}
{LISTEXECUTE}';

    protected CommandTester $commandTester;

    protected function setUp(): void
    {
        $testhelpers = new TestHelpers();

        parent::setUp();

        require_once(__DIR__ . '/../../../../TestHelpers.php');

        $testhelpers->simulateTikiScriptContext();

        TikiLib::lib('tiki')->query('TRUNCATE TABLE tiki_plugin_security');
        $testhelpers->removeAllVersions(self::PAGE_NAME);

        $application = new Application();
        $application->add(new ListExecuteCommand());

        $command = $application->find('list:execute');
        $this->commandTester = new CommandTester($command);

        $testhelpers->createPage(self::PAGE_NAME, 0, self::CONTENT_FOR_FINGERPRINT);
    }

    protected function tearDown(): void
    {
        $testhelpers = new TestHelpers();

        parent::tearDown();

        $testhelpers->resetAll();
    }

    /**
     * Test responsible for executing a list execute command on a listexecute plugin waiting for approval
     *
     * @throws Exception
     */
    public function testListExecuteCommandOnPluginWaitingForApproval()
    {
        $this->updatePluginStatus('pending');

        $this->commandTester->execute(
            [
                'page'   => self::PAGE_NAME,
                'action' => 'action',
            ]
        );

        $this->assertStringContainsString('ListExecute plugin is pending for approval.', $this->commandTester->getDisplay());
        $this->assertEquals(1, $this->commandTester->getStatusCode());
    }

    /**
     * Test responsible for executing a list execute command on a rejected listexecute plugin
     *
     * @throws Exception
     */
    public function testListExecuteCommandOnRejectedPlugin()
    {
        $this->updatePluginStatus('reject');

        $this->commandTester->execute(
            [
                'page'   => self::PAGE_NAME,
                'action' => 'action',
            ]
        );

        $this->assertStringContainsString('ListExecute plugin was rejected.', $this->commandTester->getDisplay());
        $this->assertEquals(1, $this->commandTester->getStatusCode());
    }

    /**
     * Test responsible for executing a list execute command on an approved listexecute plugin
     *
     * @throws Exception
     */
    public function testListExecuteOnApprovedPlugin()
    {
        $this->updatePluginStatus('accept');

        $this->commandTester->execute(
            [
                'page'   => self::PAGE_NAME,
                'action' => 'action',
            ]
        );

        $this->assertEquals("Action action executed on page " . self::PAGE_NAME . ".\n", $this->commandTester->getDisplay());
        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    protected function updatePluginStatus($status)
    {
        $this->pluginSecurity = TikiLib::get()->table('tiki_plugin_security');

        $this->pluginSecurity->insert(
            [
                'fingerprint'     => self::FINGERPRINT,
                'status'          => $status,
                'added_by'        => 'user',
                'last_objectType' => 'wiki page',
                'last_objectId'   => TikiLib::lib('tiki')->get_page_id_from_name(self::PAGE_NAME),
                'body' => trim(preg_replace('/{LISTEXECUTE\(?\)?}/', '', self::CONTENT_FOR_FINGERPRINT)),
                'arguments' => 'a:0:{}',
            ]
        );
    }
}
