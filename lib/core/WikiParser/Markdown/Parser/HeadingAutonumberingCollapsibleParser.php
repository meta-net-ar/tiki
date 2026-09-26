<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\WikiParser\Markdown\Parser;

use League\CommonMark\Extension\CommonMark\Parser\Block\HeadingParser;
use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;
use League\CommonMark\Util\RegexHelper;

class HeadingAutonumberingCollapsibleParser implements BlockStartParserInterface
{
    private $numbering;

    public function __construct()
    {
        $this->numbering = [];
        for ($i = 1; $i <= 6; $i++) {
            $this->numbering[$i] = 0;
        }
    }

    public function tryStart(Cursor $cursor, MarkdownParserStateInterface $parserState): ?BlockStart
    {
        if ($cursor->isIndented() || $cursor->getNextNonSpaceCharacter() !== '#') {
            return BlockStart::none();
        }

        $cursor->advanceToNextNonSpaceOrTab();

        if ($heading = $this->getCustomHeader($cursor)) {
            return BlockStart::of($heading)->at($cursor);
        }

        return BlockStart::none();
    }

    private function getCustomHeader(Cursor $cursor)
    {
        $match = RegexHelper::matchFirst('/^(#{1,6})(\$?)([+\-]{0,1})(?:[ \t]+|$)/', $cursor->getRemainder());
        if (! $match) {
            return null;
        }

        if (empty($match[2]) && empty($match[3])) {
            return null;
        }

        $cursor->advanceToNextNonSpaceOrTab();
        $cursor->advanceBy(\strlen($match[0]));

        $level = \strlen(\trim($match[1], " \n\r\t\v\x00"));
        $str = $cursor->getRemainder();

        if ($match[2] === '$') {
            $str = $this->autonumber($str, $level);
        }

        if (! empty($match[3])) {
            $open = $match[3] === '+';
            return new CollapsibleHeadingParser($level, $open, $str);
        } else {
            return new HeadingParser($level, $str);
        }
    }

    private function autonumber($str, $level)
    {
        for ($i = 1; $i < $level; $i++) {
            if ($this->numbering[$i] == 0) {
                $this->numbering[$i] = 1;
            }
        }
        $this->numbering[$level]++;
        for ($i = $level + 1; $i <= 6; $i++) {
            $this->numbering[$i] = 0;
        }

        return implode('.', array_slice($this->numbering, 0, $level)) . '. ' . $str;
    }
}
