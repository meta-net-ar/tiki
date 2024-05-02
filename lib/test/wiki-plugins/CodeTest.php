<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
require_once(__DIR__ . '/../../wiki-plugins/wikiplugin_code.php');

class WikiPlugin_CodeTest extends PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider provider
     * @param $data
     * @param $expectedOutput
     * @param array $params
     */
    public function testWikiPluginCode($data, $expectedOutput, $params = []): void
    {
        $this->assertEquals($expectedOutput, wikiplugin_code($data, $params));
    }

    public static function provider(): array
    {
        return [
            ['', '<div class="codelisting_container"><div class="icon_copy_code far fa-clipboard" tabindex="0"  data-clipboard-target="#codebox1" ><span class="copy_code_tooltiptext">Copy to clipboard</span></div><pre class="codelisting"  data-theme="off"  data-wrap="1"  dir="ltr"  style="white-space:pre-wrap; overflow-wrap: break-word; word-wrap: break-word;" id="codebox1" ><div class="code"></div></pre></div>'],
            ['<script>alert(document.cookie);</script>', '<div class="codelisting_container"><div class="icon_copy_code far fa-clipboard" tabindex="0"  data-clipboard-target="#codebox2" ><span class="copy_code_tooltiptext">Copy to clipboard</span></div><pre class="codelisting"  data-theme="off"  data-wrap="1"  dir="ltr"  style="white-space:pre-wrap; overflow-wrap: break-word; word-wrap: break-word;" id="codebox2" ><div class="code"><script>alert(document.cookie);</script></div></pre></div>', ['ishtml' => 1]],
            ['~np~~tc~{img fileId="42"}~/tc~~/np~', '<div class="codelisting_container"><div class="icon_copy_code far fa-clipboard" tabindex="0"  data-clipboard-target="#codebox3" ><span class="copy_code_tooltiptext">Copy to clipboard</span></div><pre class="codelisting"  data-theme="off"  data-wrap="1"  dir="ltr"  style="white-space:pre-wrap; overflow-wrap: break-word; word-wrap: break-word;" id="codebox3" ><div class="code">~np~~tc~{img fileId="42"}~/tc~~/np~</div></pre></div>']
        ];
    }
}
