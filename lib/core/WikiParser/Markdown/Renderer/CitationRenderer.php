<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\WikiParser\Markdown\Renderer;

use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use Tiki\Process\Process;
use Tiki\WikiParser\Markdown\Node\Citation;
use Tiki\Zotero\CslStyleManager;
use Tiki\Zotero\PandocLibraryFile;

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

        try {
            $library = PandocLibraryFile::fromPreference($prefs['zotero_pandoc_library'] ?? '');
            $styleManager = new CslStyleManager();
            $stylePath = $styleManager->resolveStylePath($prefs['zotero_pandoc_style'] ?? 'chicago-author-date');
        } catch (\RuntimeException $e) {
            $this->logPandocError('Pandoc citation setup error: ' . $e->getMessage());
            return '<span class="text-danger">[' . tra('Citation rendering failed') . ']</span>';
        }

        $libraryPath = $library->getPath();
        $pandocPath = $prefs['zotero_pandoc_path'] ?? 'pandoc';

        // Request-level static cache to avoid repeated Pandoc calls for identical citation input.
        $cacheKey = $this->buildCacheKey($pandocMarkdown, $library->getFingerprint(), $styleManager->fileFingerprint($stylePath), $pandocPath);
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

    private function buildCacheKey(string $markdown, string $libraryFingerprint, string $styleFingerprint, string $pandocPath): string
    {
        return hash(
            'sha256',
            self::CACHE_VERSION
            . "\n" . $pandocPath
            . "\n" . $libraryFingerprint
            . "\n" . $styleFingerprint
            . "\n" . $markdown
        );
    }

    private function citationsToPandocMarkdown(array $citations): string
    {
        $parts = [];

        foreach ($citations as $cit) {
            $part = '';
            if ($cit['suppress_author']) {
                $part .= '-';
            }
            $part .= '@' . $cit['key'];

            if (! empty($cit['locator'])) {
                // Add label prefix for non-page locators.
                if ($cit['label'] !== 'page') {
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
        $command = [
            $pandocPath,
            '--citeproc',
            '--bibliography=' . $libraryPath,
            '--csl=' . $stylePath,
            '-f',
            'markdown',
            '-t',
            'html',
        ];

        try {
            $process = new Process($command, null, null, $markdown, null);
            $process->run();
        } catch (\Throwable $e) {
            $this->logPandocError('Pandoc failed to start: ' . $e->getMessage());
            return false;
        }

        if (! $process->isSuccessful()) {
            $this->logPandocError('Pandoc error: ' . $process->getErrorOutput());
            return false;
        }

        $output = $process->getOutput();

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

    private function logPandocError(string $message): void
    {
        \TikiLib::lib('logs')->add_log('zotero_pandoc', $message);
    }
}
