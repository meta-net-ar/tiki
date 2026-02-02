<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\DescriptionList\DescriptionListExtension;
use League\CommonMark\Extension\Footnote\FootnoteExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\Table\Table;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\Table\TableRenderer;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Renderer\Block\FencedCodeRenderer;
use Tiki\WikiParser\Markdown\Extension as TikiExtension;

class WikiParser_ParsableMarkdown extends ParserLib
{
    public function wikiParse($data, $noparsed = [])
    {
        global $prefs;

        // let's define our configurationon
        $config = [
            // allow html because of the wiki plugins and any other allowed thing that generate html
            // we can't strip it here, so we rely on the parser to protectSpecialChars
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 100,
            'heading_permalink' => [
                'symbol' => 'removeme-' . uniqid(),
                'fragment_prefix' => 'tikitoc',
            ],
            'renderer' => [
                'soft_break'      => "<br/>\n",
            ],
        ];
        $environment = new Environment($config);

        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new AttributesExtension());
        $environment->addExtension(new TableExtension());
        $environment->addExtension(new DescriptionListExtension());
        $environment->addExtension(new FootnoteExtension());
        $environment->addExtension(new TikiExtension());

        if ($this->option['autotoc']) {
            $environment->addExtension(new HeadingPermalinkExtension());
        }

        if ($prefs['markdown_gfm'] === 'y') {
            $environment->addExtension(new GithubFlavoredMarkdownExtension());
        }

        // add default class to code blocks -> <pre class="codelisting">
        $environment->addRenderer(
            FencedCode::class,
            new class implements NodeRendererInterface {
                public function render(Node $node, ChildNodeRendererInterface $childRenderer)
                {
                    $htmlEl = (new FencedCodeRenderer())->render($node, $childRenderer);
                    $class = $htmlEl->getAttribute('class') ?: 'codelisting';
                    $htmlEl->setAttribute('class', $class);
                    return $htmlEl;
                }
            },
            10
        );

        // add default class to table -> <table class="wikitable table table-striped table-hover">
        $environment->addRenderer(
            Table::class,
            new class implements NodeRendererInterface {
                public function render(Node $node, ChildNodeRendererInterface $childRenderer)
                {
                    $htmlEl = (new TableRenderer())->render($node, $childRenderer);
                    $class = $htmlEl->getAttribute('class') ?: 'wikitable table table-striped table-hover';
                    $htmlEl->setAttribute('class', $class);
                    return $htmlEl;
                }
            },
            10
        );

        $converter = new MarkdownConverter($environment);

        // autolinking, wiki links and external links go first, otherwise GFM converts the links without Tiki additions (e.g. external icon, target=_blank, semantic)
        if ($prefs['feature_autolinks'] == 'y') {
            $data = $this->autolinks($data);
        }

        // wiki page links and external links are handled in Tiki-syntax to allow sister sites and other semantic linking
        $data = $this->parse_data_wikilinks($data, false, $this->option['wysiwyg']);
        $data = $this->parse_data_externallinks($data, false);
        $data = preg_replace(';~hc~(.*?)~/hc~;s', '<!-- $1 -->', $data);

        // converter/parser expects UTF-8, try to cleanup invalid characters
        $data = mb_convert_encoding($data, 'UTF-8', 'UTF-8');

        $data = $converter->convert($data)->getContent();

        // markdown permalinks conflict with header links in Tiki kept for backwards compatibility, thus remove the markdown ones
        // unfortunately, heading permalinks extension classes are final and cannot be extended and reused...
        $data = preg_replace('/<a[^>]*>' . $config['heading_permalink']['symbol'] . '<\/a>/', '', $data);
        $data = preg_replace_callback('/<table([^>]*)>(.*?)<\/table>/s', function ($m) {
            $ret = preg_replace("(<table(?:\s+\w+(?:=\w+|\"[^\"]+\"|'[^']+')?)*>.*?</table>(*SKIP)(*FAIL)|<br/>)is", "", $m[0]);
            return $ret;
        }, $data);

        // TODO: use Mention extension for autolinking @username mentions and other jit expansions

        if ($prefs['wiki_heading_links'] == 'y') {
            $data = $this->addHeadingLinks($data);
        }

        if ($this->option['autotoc']) {
            $data = $this->fixAutoTocAnchors($data);
        }

        return $data;
    }

    private function addHeadingLinks($data)
    {
        $smarty = TikiLib::lib('smarty');
        $icon = smarty_function_icon(['name' => 'link'], $smarty->getEmptyInternalTemplate());
        $all_anchors = [];

        $data = preg_replace_callback('#<h([1-6])>(.+?)</h\1>#is', function ($matches) use ($icon, $all_anchors) {
            $anchor = $this->getCleanAnchor($matches[2], $all_anchors);
            return '<h' . $matches[1] . ' class="showhide_heading" id="' . $anchor . '">' . $matches[2] . '<a href="#' . $anchor . '" class="heading-link">' . $icon . '</a></h' . $matches[1] . '>';
        }, $data);

        return $data;
    }

    private function fixAutoTocAnchors($data)
    {
        $all_anchors = [];
        $data = preg_replace_callback('/<a(.+?)?href="#tikitoc-(.+?)"(.+?)?>(.+?)<\/a>/', function ($matches) use ($all_anchors) {
            $anchor = $this->getCleanAnchor($matches[4], $all_anchors);
            return '<a' . $matches[1] . 'href="#' . $anchor . '"' . $matches[3] . '>' . $matches[4] . '</a>';
        }, $data);

        return $data;
    }
}
