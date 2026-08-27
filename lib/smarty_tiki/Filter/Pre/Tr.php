<?php

// / (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Filter\Pre;

use SmartyTiki\TikiSmartyExtensionInterface;

/** Smarty translation prefilter. This prefilter tries to offload the tr block from as much work as possible to keep
* the performance penalty of translation limited to compilation. It does not intervene if an argument is given (lang)
* and in some cases when translation may only be possible at runtime.
*/
class Tr implements \Smarty\Filter\FilterInterface, TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'tr';
    }

    public function filter($source, \Smarty\Template $template)
    {
        // The preg_replace() takes away the Smarty comments ({* *}) in case they have tr tags
        $source = preg_replace('/(?s)\{\*.*?\*\}/', '', $source);
        preg_match_all('/(?s)\{tr\}(.+?)\{\/tr\}/', $source, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        if (empty($matches)) {
            return $source;
        }

        $translations = [];
        $lastContextMatch = -1;
        foreach ($matches as $index => $match) {
            [$replacement, $translatedAtCompileTime] = $this->translateMatch($match);
            $needsEscaping = $translatedAtCompileTime && (
                str_contains($replacement, '\\')
                || str_contains($replacement, "'")
                || str_contains($replacement, '"')
            );
            $translations[] = [$replacement, $needsEscaping];
            if ($needsEscaping) {
                $lastContextMatch = $index;
            }
        }

        $result = '';
        $writeOffset = 0;
        $scanOffset = 0;
        $inSmartyTag = false;
        $quote = null;
        $inBacktick = false;
        $inLiteralBlock = false;

        foreach ($matches as $index => $match) {
            [$fullMatch, $matchOffset] = $match[0];
            if ($index <= $lastContextMatch) {
                $this->scanSmartyContext(
                    $source,
                    $scanOffset,
                    $matchOffset,
                    $inSmartyTag,
                    $quote,
                    $inBacktick,
                    $inLiteralBlock
                );
            }

            [$replacement, $needsEscaping] = $translations[$index];
            if ($needsEscaping && $quote !== null) {
                $replacement = $this->escapeForSmartyString($replacement, $quote);
            }

            $result .= substr($source, $writeOffset, $matchOffset - $writeOffset) . $replacement;
            $writeOffset = $matchOffset + strlen($fullMatch);
            $scanOffset = $writeOffset;
        }

        return $result . substr($source, $writeOffset);
    }

    /**
     * @return array{string, bool}
     */
    private function translateMatch(array $match): array
    {
        $source = $match[1][0];
        $translation = $this->translate($source);
        if ($translation == $source && str_contains($source, '{$')) {
            // The string to translate is not plain English. It contains a Smarty variable, which may prevent translation at compile time.
            // Leave the whole match ("tr call") intact so block.tr.php can attempt a new translation at runtime.
            return [$match[0][0], false];
        }

        return [$translation, true];
    }

    protected function translate(string $source): string
    {
        include_once(__DIR__ . '/../../../init/tra.php');
        return tra($source);
    }

    private function scanSmartyContext(
        string $source,
        int &$offset,
        int $limit,
        bool &$inSmartyTag,
        ?string &$quote,
        bool &$inBacktick,
        bool &$inLiteralBlock
    ): void {
        while ($offset < $limit) {
            if ($inLiteralBlock) {
                // Smarty ignores syntax and quote characters inside literal blocks.
                $literalEnd = strpos($source, '{/literal}', $offset);
                if ($literalEnd === false || $literalEnd >= $limit) {
                    $offset = $limit;
                    return;
                }

                $offset = $literalEnd + strlen('{/literal}');
                $inLiteralBlock = false;
                continue;
            }

            if (! $inSmartyTag) {
                // Jump between syntax characters to keep this compile-time filter inexpensive.
                $nextTag = strpos($source, '{', $offset);
                if ($nextTag === false || $nextTag >= $limit) {
                    $offset = $limit;
                    return;
                }

                $offset = $nextTag;
                if (substr_compare($source, '{literal}', $offset, strlen('{literal}')) === 0) {
                    $inLiteralBlock = true;
                    $offset += strlen('{literal}');
                    continue;
                } elseif ($this->isSmartyTagStart($source, $offset)) {
                    $inSmartyTag = true;
                }
                $offset++;
                continue;
            }

            if ($quote === null) {
                $offset += strcspn($source, "'\"}", $offset, $limit - $offset);
                if ($offset >= $limit) {
                    return;
                }

                $character = $source[$offset];
                if ($character === "'" || $character === '"') {
                    $quote = $character;
                } else {
                    $inSmartyTag = false;
                }
                $offset++;
                continue;
            }

            $specialCharacters = $inBacktick ? '\\`' : '\\' . $quote . ($quote === '"' ? '`' : '');
            $offset += strcspn($source, $specialCharacters, $offset, $limit - $offset);
            if ($offset >= $limit) {
                return;
            }

            $character = $source[$offset];
            if ($character === '\\') {
                // An escaped delimiter does not close the current Smarty string.
                $offset += 2;
                continue;
            } elseif ($inBacktick) {
                if ($character === '`') {
                    $inBacktick = false;
                }
            } elseif ($quote === '"' && $character === '`') {
                $inBacktick = true;
            } elseif ($character === $quote) {
                $quote = null;
            }

            $offset++;
        }
    }

    private function isSmartyTagStart(string $source, int $offset): bool
    {
        // Only enter quote tracking for syntax that can open a Smarty tag.
        return preg_match(
            '/\G\{(?:\{(?=[\'\"])|\$|\/?[A-Za-z_][A-Za-z0-9_]*(?=[\s}(]))/',
            $source,
            $matches,
            0,
            $offset
        ) === 1;
    }

    private function escapeForSmartyString(string $translation, string $quote): string
    {
        // Preserve literal backslashes before escaping the active Smarty delimiter.
        return strtr($translation, [
            '\\' => '\\\\',
            $quote => '\\' . $quote,
        ]);
    }
}
