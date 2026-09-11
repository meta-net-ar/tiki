<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\WikiParser\Markdown\Renderer;

use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;

/**
 * Custom renderer for Markdown fenced code blocks (```) that produces
 * the same HTML structure as the native Tiki {CODE()} wiki plugin.
 *
 * This ensures consistent styling, copy-to-clipboard functionality,
 * and optional CodeMirror integration for Markdown-authored content.
 */
class FencedCodeRenderer implements NodeRendererInterface
{
    /**
     * @var int Static counter for generating unique IDs across all code blocks on a page.
     */
    private static int $codeCount = 0;

    public static function resetCount(): void
    {
        self::$codeCount = 0;
    }

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): \Stringable|string
    {
        FencedCode::assertInstanceOf($node);

        $code = $node->getLiteral();

        // Extract the language hint (e.g., "javascript" from ```javascript)
        $infoWords = $node->getInfoWords();
        $language = ! empty($infoWords) ? strtolower($infoWords[0]) : null;

        // Generate a unique ID for clipboard targeting
        $id = 'md-codebox' . ++self::$codeCount;

        // HTML-escape the code content
        $escapedCode = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');

        // Build the inner <div class="code"> wrapper (same as {CODE()})
        $codeDiv = new HtmlElement('div', ['class' => 'code'], $escapedCode);

        // Build the <pre> element with the same attributes as {CODE()}
        $preAttrs = [
            'class' => 'codelisting',
            'id'    => $id,
            'dir'   => 'ltr',
            'style' => 'white-space:pre-wrap; overflow-wrap: break-word; word-wrap: break-word;',
        ];
        if ($language) {
            $preAttrs['data-syntax'] = $language;
        }

        $pre = new HtmlElement('pre', $preAttrs, $codeDiv);

        // Build the copy-to-clipboard icon
        $copyTooltip = new HtmlElement('span', ['class' => 'copy_code_tooltiptext'], 'Copy to clipboard');
        $copyIcon = new HtmlElement('div', [
            'class'                 => 'icon_copy_code far fa-clipboard',
            'tabindex'              => '0',
            'data-clipboard-target' => '#' . $id,
        ], $copyTooltip);

        // Wrap everything in the codelisting_container (same as {CODE()})
        $container = new HtmlElement('div', ['class' => 'codelisting_container'], $copyIcon . $pre);

        return $container;
    }
}
