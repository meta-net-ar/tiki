<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\WikiParser\Markdown\Renderer;

use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use Tiki\WikiParser\Markdown\Node\Citation;

/**
 * Renderer for citations - calls Pandoc
 */
class CitationRenderer implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer)
    {
        if (! ($node instanceof Citation)) {
            throw new \InvalidArgumentException('Incompatible node type: ' . get_class($node));
        }

        global $prefs;

        if (empty($prefs['zotero_pandoc_enabled']) || $prefs['zotero_pandoc_enabled'] !== 'y') {
            return '<span class="text-danger">[' . tra('Pandoc citations not enabled') . ']</span>';
        }

        $citations = $node->getCitations();
        $pandocMarkdown = $this->citationsToPandocMarkdown($citations);

        $libraryPath = $prefs['zotero_pandoc_library'] ?? 'storage/zotero/My Library.json';
        $stylePath = 'storage/csl-styles/' . ($prefs['zotero_pandoc_style'] ?? 'chicago-author-date') . '.csl';

        $rendered = $this->callPandoc($pandocMarkdown, $libraryPath, $stylePath);
        if ($rendered === false) {
            return '<span class="text-danger">[' . tra('Citation rendering failed') . ']</span>';
        }

        return $rendered;
    }

    private function citationsToPandocMarkdown(array $citations): string
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

            if (! empty($cit['locator'])) {
                // Add label prefix for non-page locators.
                if (! empty($cit['label']) && $cit['label'] !== 'page') {
                    $part .= ' [' . $cit['label'] . ' ' . $cit['locator'] . ']';
                } else {
                    $part .= ', ' . $cit['locator'];
                }
            }

            $parts[] = $part;
        }

        return '[' . implode('; ', $parts) . ']';
    }

    private function callPandoc(string $markdown, string $libraryPath, string $stylePath)
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

        if (! is_resource($process)) {
            error_log('Pandoc: Failed to start process');
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

        // Extract citation from paragraph tags.
        if (preg_match('/<p>(.*?)<\/p>/s', $output, $matches)) {
            $citation = $matches[1];
        } else {
            $citation = strip_tags($output);
        }

        $citation = trim($citation);

        // Remove outer parentheses if present.
        if (preg_match('/^\((.+)\)$/', $citation, $matches)) {
            $citation = $matches[1];
        }

        return $citation;
    }
}
