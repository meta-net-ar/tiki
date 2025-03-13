<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Tests;

use PdfGenerator;
use PHPUnit\Framework\TestCase;

class PdfLibTest extends TestCase
{
    public function testProcessHyperlinksInFootnotes()
    {
        global $base_url, $tikipath;

        $tikipath = dirname(__DIR__, 3) . '/';

        $base_url = 'https://test.tiki.org/';

        $mock = $this->createPartialMock(PdfGenerator::class, []);

        $content = <<<HTML
<a href="HomePage" title="HomePage" class="wiki wiki_page">HomePage</a> - this link should be clickable in the pdf, and link to the full url, which should be listed in the footnotes also in between of the external links, imho.
<a class="wiki external" target="_blank" href="https://doc.tiki.org" rel="external">Tiki Documentation</a><span class="icon icon-link-external fas fa-external-link-alt "></span> - this is an external link (with [])
<a target="_blank" class="wiki external" href="https://dev.tiki.org">https://dev.tiki.org<span class="icon icon-link-external fas fa-external-link-alt "></span></a> - this is another external link (without [])
<a target="_blank" class="wiki external" href="ftp://example.tiki.org">FTP connection<span class="icon icon-link-external fas fa-external-link-alt "></span></a> - Only HTTP and HTTPS schemas are added
HTML;

        $output = $mock->processHyperlinks($content, 'footnote', null);

        $this->assertStringContainsString('<span class="wiki wiki_page">HomePage<sup><a href="#lnk1">[1]</a></sup></span>', $output);
        $this->assertStringContainsString('<span class="wiki external">Tiki Documentation<sup><a href="#lnk2">[2]</a></sup></span>', $output);
        $this->assertStringContainsString('<span class="wiki external">https://dev.tiki.org<sup><a href="#lnk3">[3]</a></sup></span>', $output);
        $this->assertStringContainsString('<sup>&nbsp;[1]&nbsp;</sup><a name="lnk1">' . $base_url . 'HomePage</a>', $output);
        $this->assertStringContainsString('<sup>&nbsp;[2]&nbsp;</sup><a name="lnk2">https://doc.tiki.org</a>', $output);
        $this->assertStringContainsString('<sup>&nbsp;[3]&nbsp;</sup><a name="lnk3">https://dev.tiki.org</a>', $output);

        // There will be no links for other schemas like ftp://
        $this->assertStringNotContainsString('<sup>&nbsp;[4]&nbsp;</sup><a name="lnk4">ftp://example.tiki.org</a>', $output);
        $this->assertStringNotContainsString('href="ftp://example.tiki.org"', $output);
    }

    public function testEncodeCharacters()
    {
        $generator = new PdfGenerator();
        //Sample unicode character example
        $content = '&Delta;';
        $inData = 'Δ';
        $generator->_parseHTML($inData);
        $this->assertStringContainsString($content, $inData);
    }
}
