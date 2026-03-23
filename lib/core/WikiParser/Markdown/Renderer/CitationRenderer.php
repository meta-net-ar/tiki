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
    private const CACHE_TYPE = 'zotero_pandoc_citation_';
    private const CACHE_VERSION = '1';

    private static array $renderCache = [];

    public function render(Node $node, ChildNodeRendererInterface $childRenderer)
    {
        if (! ($node instanceof Citation)) {
            throw new \InvalidArgumentException('Incompatible node type: ' . get_class($node));
        }

        global $prefs;

        if (($prefs['zotero_pandoc_enabled'] ?? 'n') !== 'y') {
            return '<span class="text-danger">[' . tra('Pandoc citations not enabled') . ']</span>';
        }

        $citations = $node->getCitations();
        $pandocMarkdown = $this->citationsToPandocMarkdown($citations);

        $libraryPath = $prefs['zotero_pandoc_library'] ?? 'storage/zotero/My Library.json';
        $stylePath = 'storage/csl-styles/' . ($prefs['zotero_pandoc_style'] ?? 'chicago-author-date') . '.csl';
        $pandocPath = $prefs['zotero_pandoc_path'] ?? 'pandoc';

        // Request-level static cache to avoid repeated Pandoc calls for identical citation input.
        $cacheKey = $this->buildCacheKey($pandocMarkdown, $libraryPath, $stylePath, $pandocPath);
        if (array_key_exists($cacheKey, self::$renderCache)) {
            $rendered = self::$renderCache[$cacheKey];
        } else {
            $cachelib = \TikiLib::lib('cache');
            $cached = $cachelib->getCached($cacheKey, self::CACHE_TYPE);

            if ($cached !== false) {
                $rendered = $cached;
            } else {
                $rendered = $this->callPandoc($pandocMarkdown, $libraryPath, $stylePath, $pandocPath);
                if ($rendered !== false) {
                    $cachelib->cacheItem($cacheKey, $rendered, self::CACHE_TYPE);
                }
            }

            self::$renderCache[$cacheKey] = $rendered;
        }

        if ($rendered === false) {
            return '<span class="text-danger">[' . tra('Citation rendering failed') . ']</span>';
        }

        return $rendered;
    }

    private function buildCacheKey(string $markdown, string $libraryPath, string $stylePath, string $pandocPath): string
    {
        $libraryFingerprint = $this->getFileFingerprint($libraryPath);
        $styleFingerprint = $this->getFileFingerprint($stylePath);

        return hash(
            'sha256',
            self::CACHE_VERSION
            . "\n" . $pandocPath
            . "\n" . $libraryFingerprint
            . "\n" . $styleFingerprint
            . "\n" . $markdown
        );
    }

    private function getFileFingerprint(string $path): string
    {
        $resolvedPath = realpath($path);
        $normalizedPath = $resolvedPath !== false ? $resolvedPath : $path;

        if (! is_file($normalizedPath)) {
            return $normalizedPath . '|missing';
        }

        $mtime = @filemtime($normalizedPath) ?: 0;
        $size = @filesize($normalizedPath) ?: 0;

        return $normalizedPath . '|' . $mtime . '|' . $size;
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

    private function callPandoc(string $markdown, string $libraryPath, string $stylePath, string $pandocPath)
    {
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
