<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Importer\Markdown\Parser;

/**
 * MarkdownPipeline
 * Central registry + convenience orchestrator
 */
class MarkdownPipeline
{
    /** @var array<string, MarkdownPurifierInterface> */
    private array $purifiers = [];

    /**
     * Register a purifier instance. Keyed by its ->id().
     */
    public function registerPurifier(MarkdownPurifierInterface $purifier): self
    {
        $this->purifiers[strtolower($purifier->id())] = $purifier;
        return $this;
    }

    /**
     * Purify a raw markdown string according to $source ('commonmark','gfm','logseq', …)
     *
     * @return array{markdown:string, notes:array<int,string>}
     */
    public function purify(string $source, string $raw, array $context = []): array
    {
        $id = strtolower($source);
        if (! isset($this->purifiers[$id])) {
            // Fallback to CommonMark if unknown
            $fallback = CommonMarkPurifier::id();
            if (! isset($this->purifiers[$fallback])) {
                // Safety: create a default CommonMarkPurifier on the fly
                $this->registerPurifier(new CommonMarkPurifier());
            }
            $id = $fallback;
        }

        return $this->purifiers[$id]->purify($raw, $context);
    }

    public function has(string $id): bool
    {
        return isset($this->purifiers[strtolower($id)]);
    }

    public function get(string $id): ?MarkdownPurifierInterface
    {
        $id = strtolower($id);
        return $this->purifiers[$id] ?? null;
    }
}
