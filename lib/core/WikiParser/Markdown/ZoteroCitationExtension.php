<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\WikiParser\Markdown;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Parser\Inline\InlineParserInterface;
use League\CommonMark\Parser\Inline\InlineParserMatch;
use League\CommonMark\Parser\InlineParserContext;
use League\CommonMark\Node\Inline\AbstractInline;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

/**
 * Extension to support {@citekey} citation syntax for Pandoc rendering
 */
class ZoteroCitationExtension implements ExtensionInterface
{
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment
            ->addInlineParser(new CitationParser())
            ->addRenderer(Citation::class, new CitationRenderer());
    }
}

/**
 * AST Node representing a citation
 */
class Citation extends AbstractInline
{
    private $citations;
    
    public function __construct(array $citations)
    {
        parent::__construct();
        $this->citations = $citations;
    }
    
    public function getCitations(): array
    {
        return $this->citations;
    }
}

/**
 * Parser for {@citekey} syntax
 * Supports:
 * - {@key}
 * - {@key, p. 42}
 * - {@key1; @key2}
 * - {-@key} (suppress author)
 */
class CitationParser implements InlineParserInterface
{
    public function getMatchDefinition(): InlineParserMatch
    {
        return InlineParserMatch::regex('\{-?@[^}]+\}');
    }
    
    public function parse(InlineParserContext $inlineContext): bool
    {
        $cursor = $inlineContext->getCursor();
        
        $match = $cursor->match('/\{(-?)@([^}]+)\}/');
        
        if ($match === null) {
            return false;
        }
        
        if (!preg_match('/\{(-?)@([^}]+)\}/', $match, $groups)) {
            return false;
        }
        
        $suppressAuthor = ($groups[1] === '-');
        $content = $groups[2];
        
        $citations = [];
        $parts = array_map('trim', explode(';', $content));
        
        foreach ($parts as $part) {
            // Strip leading @ if present
            $part = ltrim($part, '@');
            
            // Allow $ in citation keys (though we recommend against it)
            if (preg_match('/^([a-zA-Z0-9_\-:.$]+)(?:,\s*(.+))?$/', $part, $citMatch)) {
                $key = $citMatch[1];
                $locator = isset($citMatch[2]) ? $citMatch[2] : null;
                $label = 'page';
                
                if ($locator) {
                    if (preg_match('/^(pp?\.?|pages?)\s+/i', $locator)) {
                        $label = 'page';
                    } elseif (preg_match('/^(secs?\.?|sections?)\s+/i', $locator)) {
                        $label = 'section';
                    } elseif (preg_match('/^(chaps?\.?|chapters?)\s+/i', $locator)) {
                        $label = 'chapter';
                    }
                    
                    $locator = preg_replace('/^(pp?\.?|pages?|sections?|secs?\.?|chapters?|chaps?\.?)\s+/i', '', $locator);
                }
                
                $citations[] = [
                    'key' => $key,
                    'locator' => $locator,
                    'label' => $label,
                    'suppress_author' => $suppressAuthor
                ];
            }
        }
        
        $inlineContext->getContainer()->appendChild(new Citation($citations));
        
        return true;
    }
}

/**
 * Renderer for citations - calls Pandoc
 */
class CitationRenderer implements NodeRendererInterface
{
    public function render(\League\CommonMark\Node\Node $node, ChildNodeRendererInterface $childRenderer)
    {
        if (!($node instanceof Citation)) {
            throw new \InvalidArgumentException('Incompatible node type: ' . get_class($node));
        }
        
        global $prefs;
        
        if (empty($prefs['zotero_pandoc_enabled']) || $prefs['zotero_pandoc_enabled'] !== 'y') {
            return '<span style="color:red;">[Pandoc citations not enabled]</span>';
        }
        
        $citations = $node->getCitations();
        
        $pandocMarkdown = $this->citationsToPandocMarkdown($citations);
        
        $libraryPath = $prefs['zotero_pandoc_library'] ?? 'storage/zotero/My Library.json';
        $stylePath = 'storage/csl-styles/' . ($prefs['zotero_pandoc_style'] ?? 'apa') . '.csl';
        
        $rendered = $this->callPandoc($pandocMarkdown, $libraryPath, $stylePath);
        
        if ($rendered === false) {
            return '<span style="color:red;">[Citation rendering failed]</span>';
        }
        
        return $rendered;
    }
    
    private function citationsToPandocMarkdown($citations)
    {
        $parts = [];
        
        foreach ($citations as $cit) {
            $part = '';
            
            if ($cit['suppress_author']) {
                $part .= '-';
            }
            
            if (preg_match('/[\$]/', $cit['key'])) {
               $part .= '@"' . $cit['key'] . '"';
            } else {
                $part .= '@' . $cit['key'];
            }
            
            if (!empty($cit['locator'])) {
                // Add label prefix for non-page locators
                if (!empty($cit['label']) && $cit['label'] !== 'page') {
                    $part .= ' [' . $cit['label'] . ' ' . $cit['locator'] . ']';
                } else {
                    $part .= ', ' . $cit['locator'];
                }
            }
            
            $parts[] = $part;
        }
        
        return '[' . implode('; ', $parts) . ']';
    }
    
    private function callPandoc($markdown, $libraryPath, $stylePath)
    {
        global $prefs;
        
        $pandocPath = $prefs['zotero_pandoc_path'] ?? 'pandoc';
        
        $cmd = escapeshellcmd($pandocPath);
        $cmd .= ' --citeproc';
        $cmd .= ' --bibliography=' . escapeshellarg($libraryPath);
        $cmd .= ' --csl=' . escapeshellarg($stylePath);
        $cmd .= ' -f markdown';
        $cmd .= ' -t html';
        
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        
        $process = proc_open($cmd, $descriptors, $pipes);
        
        if (!is_resource($process)) {
            error_log("Pandoc: Failed to start process");
            return false;
        }
        
        fwrite($pipes[0], $markdown);
        fclose($pipes[0]);
        
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        
        $returnCode = proc_close($process);
        
        if ($returnCode !== 0) {
            error_log("Pandoc error: $errors");
            return false;
        }
        
        // Extract citation from paragraph tags
        if (preg_match('/<p>(.*?)<\/p>/s', $output, $matches)) {
            $citation = $matches[1];
        } else {
            $citation = strip_tags($output);
        }
        
        $citation = trim($citation);
        
        // Remove outer parentheses if present
        if (preg_match('/^\((.+)\)$/', $citation, $matches)) {
            $citation = $matches[1];
        }
        
        return $citation;
    }
}