<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\Core\Tiki\Command\ProfileExport;

use Exception;
use Symfony\Component\Yaml\Yaml;
use Tiki_Profile;
use Tiki_Profile_InstallHandler_PluginAlias;
use Tiki_Profile_Object;
use Tiki_Profile_Writer;
use TikiTestCase;

/*
 * @group unit
 */

class PluginAliasTest extends TikiTestCase
{
    protected $writer;

    private $prefKeys = [];

    protected function setUp(): void
    {
        // create a sample writer that will never write to disk (save is mocked)
        // 'plugin_alias_test' has no corresponding fixture file on disk, so the writer starts empty
        // (unlike 'test', which TrackerItemTest pre-populates with tracker objects via Fixtures/test.yml)
        $this->writer = $this->getMockBuilder(Tiki_Profile_Writer::class)
            ->onlyMethods(['save'])
            ->setConstructorArgs([__DIR__ . "/Fixtures", 'plugin_alias_test'])
            ->getMock();

        $this->writer->method('save')->will(
            $this->throwException(new Exception('Tiki_Profile_Writer::save() should not be called during tests'))
        );

        parent::setUp();
    }

    protected function tearDown(): void
    {
        foreach ($this->prefKeys as $prefKey) {
            unset($GLOBALS['prefs'][$prefKey]);
        }
        $this->prefKeys = [];

        parent::tearDown();
    }

    /**
     * Stores plugin alias data directly under the preference key read by
     * WikiPlugin_Negotiator_Wiki_Alias::info(), without needing the DB.
     */
    private function definePluginAlias($name, array $data)
    {
        $prefKey = 'pluginalias_' . mb_strtolower($name);
        $GLOBALS['prefs'][$prefKey] = serialize($data);
        $this->prefKeys[] = $prefKey;
    }

    /**
     * Assertion test, uses a YAML file from disk and a YAML string, converts both to arrays to compare
     */
    protected function assertYamlFileMatchYamlString($yamlFile, $yamlString)
    {
        $expected = Yaml::parse(file_get_contents($yamlFile));
        $result = Yaml::parse($yamlString);

        $this->assertEquals($expected, $result);
    }

    public function testExport()
    {
        $this->definePluginAlias('test_alias', [
            'implementation' => 'html',
            'description' => [
                'name' => 'Test Alias',
                'description' => 'A test alias for unit tests',
                'prefs' => ['wikiplugin_test_alias'],
                'validate' => 'none',
                'filter' => 'xss',
                'inline' => false,
                'params' => [],
            ],
            'body' => [
                'input' => 'ignore',
                'default' => '',
                'params' => [],
            ],
            'params' => [
                'foo' => 'bar',
            ],
            // Internal meta-key added by WikiPlugin_Negotiator_Wiki_Alias::store(), must not leak into the export
            'plugin_name' => 'test_alias',
        ]);

        $result = Tiki_Profile_InstallHandler_PluginAlias::export($this->writer, 'test_alias');

        $this->assertTrue($result);
        $this->assertYamlFileMatchYamlString(
            __DIR__ . '/Fixtures/testPluginAliasExportResult.yml',
            $this->writer->dump()
        );
    }

    public function testExportNormalizesNameCase()
    {
        $this->definePluginAlias('mixed_case_alias', [
            'implementation' => 'html',
            'description' => [],
        ]);

        Tiki_Profile_InstallHandler_PluginAlias::export($this->writer, 'Mixed_Case_Alias');

        $data = Yaml::parse($this->writer->dump());
        $this->assertSame('mixed_case_alias', $data['objects'][0]['data']['name']);
    }

    public function testExportReturnsFalseWhenAliasNotFound()
    {
        $result = Tiki_Profile_InstallHandler_PluginAlias::export($this->writer, 'does_not_exist');

        $this->assertFalse($result);
        $data = Yaml::parse($this->writer->dump());
        $this->assertEmpty($data['objects']);
    }

    public function testExportReturnsFalseWhenImplementationIsMissing()
    {
        $this->definePluginAlias('no_implementation', [
            'description' => ['name' => 'No implementation'],
        ]);

        $result = Tiki_Profile_InstallHandler_PluginAlias::export($this->writer, 'no_implementation');

        $this->assertFalse($result);
    }

    public function testExportReturnsFalseWhenImplementationIsEmpty()
    {
        $this->definePluginAlias('empty_implementation', [
            'implementation' => '',
            'description' => ['name' => 'Empty implementation'],
        ]);

        $result = Tiki_Profile_InstallHandler_PluginAlias::export($this->writer, 'empty_implementation');

        $this->assertFalse($result);
    }

    /**
     * A minimal alias (no description content stored) must still export something that
     * Tiki_Profile_InstallHandler_PluginAlias::canInstall() accepts on re-import: it requires
     * name/implementation/description to be present, so description must never be omitted.
     */
    public function testMinimalAliasExportPassesCanInstall()
    {
        $this->definePluginAlias('minimal_alias', [
            'implementation' => 'bold',
        ]);

        $result = Tiki_Profile_InstallHandler_PluginAlias::export($this->writer, 'minimal_alias');
        $this->assertTrue($result);

        $this->assertYamlFileMatchYamlString(
            __DIR__ . '/Fixtures/testPluginAliasExportMinimalResult.yml',
            $this->writer->dump()
        );

        $exported = Yaml::parse($this->writer->dump());
        $objectData = $exported['objects'][0]['data'];

        $profile = Tiki_Profile::fromString('dummy', '');
        $rawObject = ['type' => 'plugin_alias', 'data' => $objectData];
        $profileObject = new Tiki_Profile_Object($rawObject, $profile);
        $handler = new Tiki_Profile_InstallHandler_PluginAlias($profileObject, []);

        $this->assertTrue($handler->canInstall());
    }

    public function testDumpExportReturnsYaml()
    {
        $this->definePluginAlias('dump_alias', [
            'implementation' => 'html',
            'description' => ['name' => 'Dump Alias'],
        ]);

        $yaml = Tiki_Profile_InstallHandler_PluginAlias::dumpExport('dump_alias');

        $this->assertIsString($yaml);
        $data = Yaml::parse($yaml);
        $this->assertSame('dump_alias', $data['objects'][0]['data']['name']);
    }

    public function testDumpExportReturnsFalseWhenAliasNotFound()
    {
        $result = Tiki_Profile_InstallHandler_PluginAlias::dumpExport('does_not_exist');

        $this->assertFalse($result);
    }
}
