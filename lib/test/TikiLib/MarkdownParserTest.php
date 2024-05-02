<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @group integration
 *
 */

class TikiLib_MarkdownParserTest extends TikiTestCase
{
    protected $oldprefs;
    protected $olduser;

    public function __construct($name = null)
    {
        $this->setPageRegex();
        parent::__construct($name);
    }

    protected function setUp(): void
    {
        global $prefs, $user;

        $this->oldprefs = $prefs;
        $this->olduser = $user;
    }


    /**
    * remove the external Wikis defined in the tests
    */
    protected function tearDown(): void
    {
        global $prefs, $user;

        $prefs = $this->oldprefs;
        $user = $this->olduser;
    }

    /**
     * @param $input
     * @param $output
     * @param array $options
     * @throws Exception
     */
    public function testMarkdownParser(): void
    {
        global $prefs, $user;

        $user = 'admin';
        $prefs['markdown_enabled'] = 'y';
        $prefs['feature_wiki_argvariable'] = 'y';
        $heading_links_pref = $prefs['wiki_heading_links'];
        $prefs['wiki_heading_links'] = 'n';

        $this->assertEquals($this->html(), TikiLib::lib('parser')->parse_data('{syntax type=markdown}' . $this->markdown()));

        $prefs['wiki_heading_links'] = $heading_links_pref;
    }

    public function testMarkdownParserWithHeadingLinks(): void
    {
        global $prefs, $user;

        $user = 'admin';
        $prefs['markdown_enabled'] = 'y';
        $prefs['feature_wiki_argvariable'] = 'y';
        $heading_links_pref = $prefs['wiki_heading_links'];
        $prefs['wiki_heading_links'] = 'y';

        $this->assertEquals($this->html(true), TikiLib::lib('parser')->parse_data('{syntax type=markdown}' . $this->markdown()));

        $prefs['wiki_heading_links'] = $heading_links_pref;
    }

    public function testReplaceLinks(): void
    {
        $template = "Link to ((%s)) in wiki syntax, as a ((%s|description)) and in [markdown](%s) syntax.";
        $content = sprintf($template, 'OldPageName', 'OldPageName', 'OldPageName');
        $replaced = TikiLib::lib('parser')->replace_links($content, 'OldPageName', 'NewPageName');
        $this->assertEquals(sprintf($template, 'NewPageName', 'NewPageName', 'NewPageName'), $replaced);
    }

    public function testGetPages(): void
    {
        global $prefs;
        $prefs['feature_wikiwords'] = 'y';

        $content = "PageWikiWord
((The Wiki Way))
(alias(The Milky Way))
[markdown](markdown-page)";
        $pages = TikiLib::lib('parser')->get_pages($content);
        $this->assertEquals(['The Wiki Way', 'The Milky Way', 'PageWikiWord', 'markdown-page'], $pages);
    }

    protected function markdown(): string
    {
        return "---
User: {{user}}
---
Plugin test: {SUP()}**12**{SUP}
---

# h1 Heading 8-)

**This is bold text**

__This is bold text__

*This is italic text*

_This is italic text_

~~Strikethrough~~

> Blockquotes can also be nested...
>> ...by using additional greater-than signs right next to each other...
> > > ...or with spaces between arrows.

+ Create a list by starting a line with `+`, `-`, or `*`
+ Sub-lists are made by indenting 2 spaces:
  - Marker character change forces new list start:
    * Ac tristique libero volutpat at
    + Facilisis in pretium nisl aliquet
    - Nulla volutpat aliquam velit
+ Very easy!

1. Lorem ipsum dolor sit amet
2. Consectetur adipiscing elit
3. Integer molestie lorem at massa
1. You can use sequential numbers...
1. ...or keep all the numbers as `1.`

Start numbering with offset:

57. foo
1. bar

Inline `code`

    // Some comments
    line 1 of code
    line 2 of code
    line 3 of code

```
Sample text here...
```

| Option | Description |
| ------ | ----------- |
| data   | path to data files to supply the data that will be passed into templates. |
| engine | engine to be used for processing templates. Handlebars is the default. |
| ext    | extension to be used for dest files. |

Right aligned columns

| Option | Description |
| ------:| -----------:|
| data   | path to data files to supply the data that will be passed into templates. |
| engine | engine to be used for processing templates. Handlebars is the default. |
| ext    | extension to be used for dest files. |

[link text](http://example.org)

[link with title](http://example.org/ \"title text!\")

Autoconverted link https://example.org (enable linkify to see)

Footnote 1 link[^first].

Footnote 2 link[^second].

Inline footnote^[Text of inline footnote] definition.

Duplicated footnote reference[^second].

[^first]: Footnote **can have markup**

    and multiple paragraphs.

[^second]: Footnote text.
";
    }

    protected function html($with_heading_links = false): string
    {
        if ($with_heading_links) {
            $res = '<hr />
<h2 class="showhide_heading" id="User:_admin">User: admin<a href="#User:_admin" class="heading-link"><img src="img/icons/green_question.png" alt="Question" width="16" height="16" name="link" title="Question" class="icon" /></a></h2>
<h2 class="showhide_heading" id="Plugin_test:_12">Plugin test: <sup><strong>12</strong></sup><a href="#Plugin_test:_12" class="heading-link"><img src="img/icons/green_question.png" alt="Question" width="16" height="16" name="link" title="Question" class="icon" /></a></h2>
<h1 class="showhide_heading" id="h1_Heading_8-_">h1 Heading 😎<a href="#h1_Heading_8-_" class="heading-link"><img src="img/icons/green_question.png" alt="Question" width="16" height="16" name="link" title="Question" class="icon" /></a></h1>';
        } else {
            $res = '<hr />
<h2>User: admin</h2>
<h2>Plugin test: <sup><strong>12</strong></sup></h2>
<h1>h1 Heading 😎</h1>';
        }

        $res .= '
<p><strong>This is bold text</strong></p>
<p><strong>This is bold text</strong></p>
<p><em>This is italic text</em></p>
<p><em>This is italic text</em></p>
<p><del>Strikethrough</del></p>
<blockquote>
<p>Blockquotes can also be nested...</p>
<blockquote>
<p>...by using additional greater-than signs right next to each other...</p>
<blockquote>
<p>...or with spaces between arrows.</p>
</blockquote>
</blockquote>
</blockquote>
<ul>
<li>Create a list by starting a line with <code>+</code>, <code>-</code>, or <code>*</code>
</li>
<li>Sub-lists are made by indenting 2 spaces:
<ul>
<li>Marker character change forces new list start:
<ul>
<li>Ac tristique libero volutpat at</li>
</ul>
<ul>
<li>Facilisis in pretium nisl aliquet</li>
</ul>
<ul>
<li>Nulla volutpat aliquam velit</li>
</ul>
</li>
</ul>
</li>
<li>Very easy!</li>
</ul>
<ol>
<li>Lorem ipsum dolor sit amet</li>
<li>Consectetur adipiscing elit</li>
<li>Integer molestie lorem at massa</li>
<li>You can use sequential numbers...</li>
<li>...or keep all the numbers as <code>1.</code>
</li>
</ol>
<p>Start numbering with offset:</p>
<ol start="57">
<li>foo</li>
<li>bar</li>
</ol>
<p>Inline <code>code</code></p>
<pre><code>// Some comments
line 1 of code
line 2 of code
line 3 of code
</code></pre>
<pre><code>Sample text here...
</code></pre>
<table class="wikitable table table-striped table-hover">
<thead>
<tr>
<th>Option</th>
<th>Description</th>
</tr>
</thead>
<tbody>
<tr>
<td>data</td>
<td>path to data files to supply the data that will be passed into templates.</td>
</tr>
<tr>
<td>engine</td>
<td>engine to be used for processing templates. Handlebars is the default.</td>
</tr>
<tr>
<td>ext</td>
<td>extension to be used for dest files.</td>
</tr>
</tbody>
</table>
<p>Right aligned columns</p>
<table class="wikitable table table-striped table-hover">
<thead>
<tr>
<th align="right">Option</th>
<th align="right">Description</th>
</tr>
</thead>
<tbody>
<tr>
<td align="right">data</td>
<td align="right">path to data files to supply the data that will be passed into templates.</td>
</tr>
<tr>
<td align="right">engine</td>
<td align="right">engine to be used for processing templates. Handlebars is the default.</td>
</tr>
<tr>
<td align="right">ext</td>
<td align="right">extension to be used for dest files.</td>
</tr>
</tbody>
</table>
<p><a href="http://example.org">link text</a></p>
<p><a href="http://example.org/" title="title text!">link with title</a></p>
<p>Autoconverted link <a target="_blank" class="wiki external"  href="https://example.org">https://example.org<img src="img/icons/green_question.png" alt="Question" width="16" height="16" name="link-external" title="Question" class="icon" /></a> (enable linkify to see)</p>
<p>Footnote 1 link<sup id="fnref:first"><a class="footnote-ref" href="#fn:first" role="doc-noteref">1</a></sup>.</p>
<p>Footnote 2 link<sup id="fnref:second"><a class="footnote-ref" href="#fn:second" role="doc-noteref">2</a></sup>.</p>
<p>Inline footnote<sup id="fnref:text-of-inline-footn"><a class="footnote-ref" href="#fn:text-of-inline-footn" role="doc-noteref">3</a></sup> definition.</p>
<p>Duplicated footnote reference<sup id="fnref:second__2"><a class="footnote-ref" href="#fn:second" role="doc-noteref">2</a></sup>.</p>
<div class="footnotes" role="doc-endnotes"><hr /><ol><li class="footnote" id="fn:first" role="doc-endnote"><p>Footnote <strong>can have markup</strong></p>
<p>and multiple paragraphs.&nbsp;<a class="footnote-backref" rev="footnote" href="#fnref:first" role="doc-backlink">↩</a></p></li>
<li class="footnote" id="fn:second" role="doc-endnote"><p>Footnote text.&nbsp;<a class="footnote-backref" rev="footnote" href="#fnref:second" role="doc-backlink">↩</a>&nbsp;<a class="footnote-backref" rev="footnote" href="#fnref:second__2" role="doc-backlink">↩</a></p></li>
<li class="footnote" id="fn:text-of-inline-footn" role="doc-endnote"><p>Text of inline footnote&nbsp;<a class="footnote-backref" rev="footnote" href="#fnref:text-of-inline-footn" role="doc-backlink">↩</a></p></li></ol></div>
';

        return $res;
    }
}
