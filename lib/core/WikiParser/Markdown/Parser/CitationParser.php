<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\WikiParser\Markdown\Parser;

use League\CommonMark\Parser\Inline\InlineParserInterface;
use League\CommonMark\Parser\Inline\InlineParserMatch;
use League\CommonMark\Parser\InlineParserContext;
use Tiki\WikiParser\Markdown\Node\Citation;

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
        $state = $cursor->saveState();
        $match = $cursor->match('/\{(-?)@([^}]+)\}/');

        if ($match === null) {
            return false;
        }

        if (! preg_match('/\{(-?)@([^}]+)\}/', $match, $groups)) {
            $cursor->restoreState($state);
            return false;
        }

        $groupSuppressAuthor = ($groups[1] === '-');
        $content = $groups[2];

        $citations = [];
        $parts = array_map('trim', explode(';', $content));

        foreach ($parts as $index => $part) {
            $partSuppressAuthor = ($groupSuppressAuthor && $index === 0);

            // Allow explicit per-citation -@ and @ markers.
            if (str_starts_with($part, '-@')) {
                $partSuppressAuthor = true;
                $part = substr($part, 2);
            } else {
                $part = ltrim($part, '@');
            }

            // Allow $ in citation keys (though we recommend against it)
            if (preg_match('/^([a-zA-Z0-9_\-:.$]+)(?:,\s*(.+))?$/', $part, $citMatch)) {
                $key = $citMatch[1];
                $locator = $citMatch[2] ?? null;
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
                    'suppress_author' => $partSuppressAuthor,
                ];
            }
        }

        if (empty($citations)) {
            $cursor->restoreState($state);
            return false;
        }

        $inlineContext->getContainer()->appendChild(new Citation($citations));

        return true;
    }
}
