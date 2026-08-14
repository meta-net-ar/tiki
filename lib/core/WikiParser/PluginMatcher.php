<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\WikiParser\PluginMatcherMatch;

class WikiParser_PluginMatcher implements Iterator, Countable
{
    private $starts = [];
    private $ends = [];
    private $level = 0;

    private $ranges = [];

    private $text = '';

    private $scanPosition = -1;

    private $leftOpen = 0;

    /**
     * @param $text
     * @return WikiParser_PluginMatcher
     */
    public static function match(?string $text)
    {
        $matcher = new self();
        $matcher->text = $text;
        $matcher->findMatches(0, strlen($text ?? ''));

        return $matcher;
    }

    public function __clone()
    {
        $new = $this;
        $this->starts = array_map(
            function ($match) use ($new) {
                $match->changeMatcher($new);
                return clone $match;
            },
            $this->starts
        );

        $this->ends = array_map(
            function ($match) use ($new) {
                $match->changeMatcher($new);
                return clone $match;
            },
            $this->ends
        );
    }

    private function getSubMatcher($start, $end)
    {
        $sub = new self();
        $sub->level = $this->level + 1;
        $sub->text = $this->text;
        $sub->findMatches($start, $end);

        return $sub;
    }

    private function appendSubMatcher($matcher)
    {
        foreach ($matcher->starts as $match) {
            $match->changeMatcher($this);
            $this->recordMatch($match);
        }
    }

    private function isComplete()
    {
        return $this->leftOpen == 0;
    }

    public function findMatches($start, $end)
    {
        global $prefs;

        static $passes;

        if ($this->level === 0) {
            $passes = 0;
        }

        if (++$passes > $prefs['wikiplugin_maximum_passes']) {
            return;
        }

        $this->findNoParseRanges($start, $end);

        $pos = $start;
        while (false !== $pos = strpos($this->text ?? '', '{', $pos)) {
            // Shortcut {$var} syntax
            if (substr($this->text, $pos + 1, 1) === '$') {
                ++$pos;
                continue;
            }

            if ($pos >= $end) {
                return;
            }

            if (! $this->isParsedLocation($pos)) {
                ++$pos;
                continue;
            }

            $match = new PluginMatcherMatch($this, $pos);
            ++$pos;

            if (! $match->findName($end)) {
                continue;
            }

            if (! $match->findArguments($end)) {
                continue;
            }

            if ($match->getEnd() !== false) {
                // End already reached
                $this->recordMatch($match);
                $pos = $match->getEnd();
            } else {
                ++$this->leftOpen;

                $bodyStart = $match->getBodyStart();
                $lookupStart = $bodyStart;

                while ($match->findEnd($lookupStart, $end)) {
                    $candidate = $match->getEnd();

                    $sub = $this->getSubMatcher($bodyStart, $candidate - 1);
                    if ($sub->isComplete()) {
                        $this->recordMatch($match);
                        if ($match->getName() != 'code') {
                            $this->appendSubMatcher($sub);
                        }
                        $pos = $match->getEnd();
                        --$this->leftOpen;
                        if (empty($this->level)) {
                            $passes = 0;
                        }
                        break;
                    }

                    $lookupStart = $candidate;
                }
            }
        }
    }

    public function getText()
    {
        return $this->text;
    }

    private function recordMatch($match)
    {
        $this->starts[$match->getStart()] = $match;
        $this->ends[$match->getEnd()] = $match;
    }

    private function findNoParseRanges($from, $to)
    {
        while (false !== $open = $this->findText('~np~', $from, $to)) {
            if (false !== $close = $this->findText('~/np~', $open, $to)) {
                $from = $close;
                $this->ranges[] = [$open, $close];
            } else {
                return;
            }
        }
    }

    public function isParsedLocation($pos)
    {
        foreach ($this->ranges as $range) {
            list($open, $close ) = $range;

            if ($pos > $open && $pos < $close) {
                return false;
            }
        }

        return true;
    }

    public function count(): int
    {
        return count($this->starts);
    }

    /**
     * @return PluginMatcherMatch
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->starts[ $this->scanPosition ];
    }

    /**
     * @return PluginMatcherMatch
     */
    public function next(): void
    {
        foreach ($this->starts as $key => $m) {
            if ($key > $this->scanPosition) {
                $this->scanPosition = $key;
                return;
            }
        }

        $this->scanPosition = -1;
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->scanPosition;
    }

    public function valid(): bool
    {
        return $this->scanPosition !== null && isset($this->starts[$this->scanPosition]);
    }

    public function rewind(): void
    {
        reset($this->starts);
        $this->scanPosition = key($this->starts) ?? -1;
    }

    public function getChunkFrom($pos, $size)
    {
        return substr($this->text, $pos, $size);
    }

    private function getFirstStart($lower)
    {
        foreach ($this->starts as $key => $match) {
            if ($key >= $lower) {
                return $key;
            }
        }

        return false;
    }

    private function getLastEnd()
    {
        $ends = array_keys($this->ends);
        return end($ends);
    }

    public function findText(string $string, int $from, int $to): int|bool
    {
        if ($from >= strlen($this->text ?? '')) {
            return false;
        }

        $pos = strpos($this->text, $string, $from);

        if ($pos === false || $pos + strlen($string) > $to) {
            return false;
        }

        return $pos;
    }

    public function performReplace($match, $string)
    {
        if (! isset($string)) {
            $string = '';
        }
        $start = $match->getStart();
        $end = $match->getEnd();

        $sizeDiff = - ($end - $start - strlen($string));
        $this->text = substr_replace($this->text, $string, $start, $end - $start);

        $this->removeRanges($start, $end);
        $this->offsetRanges($end, $sizeDiff);
        $this->findNoParseRanges($start, $start + strlen($string));

        $matches = $this->ends;
        $toRemove = [$match];
        $toAdd = [];

        foreach ($matches as $key => $m) {
            if ($m->inside($match)) {
                $toRemove[] = $m;
            } elseif ($match->inside($m)) {
                // Boundaries should not be extended for wrapping plugins
            } elseif ($key > $end) {
                unset($this->ends[$m->getEnd()]);
                unset($this->starts[$m->getStart()]);
                $m->applyOffset($sizeDiff);
                $toAdd[] = $m;
            }
        }

        foreach ($toRemove as $m) {
            unset($this->ends[$m->getEnd()]);
            unset($this->starts[$m->getStart()]);
            $m->invalidate();
        }

        foreach ($toAdd as $m) {
            $this->ends[$m->getEnd()] = $m;
            $this->starts[$m->getStart()] = $m;
        }

        $sub = $this->getSubMatcher($start, $start + strlen($string));
        if ($sub->isComplete()) {
            $this->appendSubMatcher($sub);
        }

        ksort($this->ends);
        ksort($this->starts);

        if ($this->scanPosition == $start) {
            $this->scanPosition = $start - 1;
        }
    }

    private function removeRanges($start, $end)
    {
        $toRemove = [];
        foreach ($this->ranges as $key => $range) {
            if ($start >= $range[0] && $start <= $range[1]) {
                $toRemove[] = $key;
            }
        }

        foreach ($toRemove as $key) {
            unset($this->ranges[$key]);
        }
    }

    private function offsetRanges($end, $sizeDiff)
    {
        foreach ($this->ranges as & $range) {
            if ($range[0] >= $end) {
                $range[0] += $sizeDiff;
                $range[1] += $sizeDiff;
            }
        }
    }
}
