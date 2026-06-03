<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class WikiParser_PluginOutputTest extends PHPUnit\Framework\TestCase
{
    public function testWikiToWikiOutput()
    {
        $output = WikiParser_PluginOutput::wiki('^Hello world!^');

        $this->assertEquals('^Hello world!^', $output->toWiki());
    }

    public function testWikiToHtmlOutput()
    {
        $output = WikiParser_PluginOutput::wiki('^Hello world!^');

        $this->assertStringContainsString('<div class="card bg-light"><div class="card-body">Hello world!</div></div>', $output->toHtml());
    }

    public function testHtmlToWikiOutput()
    {
        $output = WikiParser_PluginOutput::html('<div>Hello</div>');

        $this->assertEquals('~np~<div>Hello</div>~/np~', $output->toWiki());
    }

    public function testHtmlToHtmlOutput()
    {
        $output = WikiParser_PluginOutput::html('<div>Hello</div>');

        $this->assertEquals('<div>Hello</div>', $output->toHtml());
    }

    public function testInternalError()
    {
        $output = WikiParser_PluginOutput::internalError(tra('Unknown conversion'));

        $this->assertStringContainsString('Unknown conversion', $output->toHtml());
    }

    public function testMissingArguments()
    {
        $output = WikiParser_PluginOutput::argumentError(['id', 'test']);

        $this->assertStringContainsString('<li>id</li>', $output->toHtml());
    }

    public function testMissingArgumentsDoesNotNestNoparseMarkersInRemarksboxTitle()
    {
        $parserlib = TikiLib::lib('parser');
        $previousWikiContext = $parserlib->option['wiki_parse_context'] ?? null;
        $parserlib->option['wiki_parse_context'] = true;

        try {
            $output = WikiParser_PluginOutput::argumentError(['begin', 'end', 'title']);
            $wiki = $output->toWiki();

            $this->assertStringContainsString('User error', $wiki);
            $this->assertStringContainsString('Plugin argument(s) missing or invalid:', $wiki);
            $this->assertStringNotContainsString('~np~User error', $wiki);
            $this->assertStringNotContainsString('User error~/np~', $wiki);
        } finally {
            if ($previousWikiContext === null) {
                unset($parserlib->option['wiki_parse_context']);
            } else {
                $parserlib->option['wiki_parse_context'] = $previousWikiContext;
            }
        }
    }
}
