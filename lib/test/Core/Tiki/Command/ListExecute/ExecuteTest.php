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
    protected $oldprefs;

    protected function setUp(): void
    {
        global $testhelpers, $prefs;

        $this->oldprefs = $prefs;
        $prefs['fallbackBaseUrl'] = 'https://tiki.org/';

        $testhelpers = new TestHelpers();

        parent::setUp();

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
        global $testhelpers, $prefs;

        $prefs = $this->oldprefs;

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

        $pageName = self::PAGE_NAME;
        $actual = trim(preg_replace('/\s+/', ' ', $this->commandTester->getDisplay()));
        $expected = "[ERROR] Action action failed on page {$pageName}. ListExecute plugin is pending for approval.";
        $this->assertStringContainsString($expected, $actual);
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

        $pageName = self::PAGE_NAME;
        $actual = trim(preg_replace('/\s+/', ' ', $this->commandTester->getDisplay()));
        $expected = "[ERROR] Action action failed on page {$pageName}. ListExecute plugin was rejected.";
        $this->assertStringContainsString($expected, $actual);
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

        $actual = trim($this->commandTester->getDisplay());
        $pageName = self::PAGE_NAME;

        $expected = "[OK] Action action executed on page {$pageName}.";
        $this->assertEquals($expected, $actual);
        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    /**
     * Test responsible for executing a list execute command check fallbackBaseUrl warning
     *
     * @throws Exception
     */
    public function testListExecuteOnFallbackBaseUrl()
    {
        $this->updatePluginStatus('accept');

        global  $prefs;
        $prefValue = $prefs['fallbackBaseUrl'];
        $prefs['fallbackBaseUrl'] = '';
        $this->commandTester->execute(
            [
                 'page'   => self::PAGE_NAME,
                 'action' => 'action',
            ]
        );

        $pageName = self::PAGE_NAME;
        $actualString = trim(preg_replace('/\s+/', ' ', $this->commandTester->getDisplay()));
        $expectedString = "[WARNING] Some commands may need to determine the URL of the website and will not be able to do so reliably because fallbackBaseUrl is not set in the admin. [OK] Action action executed on page {$pageName}.";

        $this->assertStringContainsString($expectedString, $actualString);
        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $prefs['fallbackBaseUrl'] = $prefValue;
    }

    /**
     * Test responsible for executing a list execute command restricted to specific objects
     *
     * @throws Exception
     */
    public function testListExecuteOnSpecificObjects()
    {
        $this->updatePluginStatus('accept');

        $this->commandTester->execute(
            [
                'page'      => self::PAGE_NAME,
                'action'    => 'action',
                '--objects' => ['trackeritem:1', 'trackeritem:2'],
            ]
        );

        $pageName = self::PAGE_NAME;
        $actual = trim(preg_replace('/\s+/', ' ', $this->commandTester->getDisplay()));
        $expected = "[OK] Action action executed on page {$pageName} for trackeritem:1, trackeritem:2.";
        $this->assertStringContainsString($expected, $actual);
        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    /**
     * Test responsible for rejecting object identifiers not following the object_type:object_id format
     *
     * @throws Exception
     */
    public function testListExecuteOnMalformedObjects()
    {
        $this->updatePluginStatus('accept');

        $this->commandTester->execute(
            [
                'page'      => self::PAGE_NAME,
                'action'    => 'action',
                '--objects' => ['trackeritem:1', 'trackeritem', ':3'],
            ]
        );

        $actual = trim(preg_replace('/\s+/', ' ', $this->commandTester->getDisplay()));
        $expected = '[ERROR] Invalid object identifier(s): trackeritem, :3. Expected format is object_type:object_id.';
        $this->assertStringContainsString($expected, $actual);
        $this->assertStringNotContainsString('executed on page', $actual);
        $this->assertEquals(1, $this->commandTester->getStatusCode());
    }

    /**
     * Test responsible for executing a list execute command restricted to a single page of results
     *
     * @throws Exception
     */
    public function testListExecuteOnResultsPage()
    {
        $this->updatePluginStatus('accept');

        $this->commandTester->execute(
            [
                'page'           => self::PAGE_NAME,
                'action'         => 'action',
                '--results-page' => 2,
            ]
        );

        $pageName = self::PAGE_NAME;
        $actual = trim(preg_replace('/\s+/', ' ', $this->commandTester->getDisplay()));
        $expected = "[OK] Action action executed on page {$pageName} for results page 2.";
        $this->assertStringContainsString($expected, $actual);
        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    /**
     * Test responsible for rejecting results pages that are not a page number
     *
     * @throws Exception
     */
    public function testListExecuteOnInvalidResultsPage()
    {
        $this->updatePluginStatus('accept');

        $this->commandTester->execute(
            [
                'page'           => self::PAGE_NAME,
                'action'         => 'action',
                '--results-page' => 0,
            ]
        );

        $actual = trim(preg_replace('/\s+/', ' ', $this->commandTester->getDisplay()));
        $this->assertStringContainsString('[ERROR] Invalid results page: 0.', $actual);
        $this->assertStringNotContainsString('executed on page', $actual);
        $this->assertEquals(1, $this->commandTester->getStatusCode());
    }

    /**
     * Test responsible for rejecting a selection of objects combined with a page of results
     *
     * @throws Exception
     */
    public function testListExecuteOnObjectsCombinedWithResultsPage()
    {
        $this->updatePluginStatus('accept');

        $this->commandTester->execute(
            [
                'page'           => self::PAGE_NAME,
                'action'         => 'action',
                '--objects'      => ['trackeritem:1'],
                '--results-page' => 1,
            ]
        );

        $actual = trim(preg_replace('/\s+/', ' ', $this->commandTester->getDisplay()));
        $this->assertStringContainsString('[ERROR] The objects and results-page options cannot be combined', $actual);
        $this->assertStringNotContainsString('executed on page', $actual);
        $this->assertEquals(1, $this->commandTester->getStatusCode());
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
