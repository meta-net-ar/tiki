<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\WikiParser;

class PluginMatcherMatch
{
    private const LONG = 1;
    private const SHORT = 2;
    private const LEGACY = 3;
    private const NAME_MAX_LENGTH = 50;

    private $matchType = false;
    private $nameEnd = false;
    private $name = false;
    private $bodyStart = false;
    private $bodyEnd = false;

    /** @var WikiParser_PluginMatcher|bool */
    private $matcher = false;

    private $start = false;
    private $end = false;
    private $initialstart = false;
    private $arguments = false;

    public function __construct($matcher, $start)
    {
        $this->matcher = $matcher;
        $this->start = $start;
        $this->initialstart = $start;
    }

    public function findName($limit)
    {
        $candidate = $this->matcher->getChunkFrom($this->start + 1, self::NAME_MAX_LENGTH);
        $name = strtok($candidate, " (}\n\r,");

        if (empty($name) || ! ctype_alnum($name)) {
            $this->invalidate();
            return false;
        }

        // Upper case uses long syntax
        if (strtoupper($name) == $name) {
            $this->matchType = self::LONG;

            // Parenthesis required when using long syntax
            if (isset($candidate[strlen($name)]) && $candidate[strlen($name)] != '(') {
                $this->invalidate();
                return false;
            }
        } else {
            $this->matchType = self::SHORT;
        }

        $nameEnd = $this->start + 1 + strlen($name);

        if ($nameEnd > $limit) {
            $this->invalidate();
            return false;
        }

        $this->name = strtolower($name);
        $this->nameEnd = $nameEnd;

        return true;
    }

    public function findArguments($limit)
    {
        if ($this->nameEnd === false) {
            return false;
        }

        $pos = $this->matcher->findText('}', $this->nameEnd, $limit);

        if (false === $pos) {
            $this->invalidate();
            return false;
        }

        $unescapedFound = $this->countUnescapedQuotes($this->nameEnd, $pos);

        while (1 == ($unescapedFound % 2)) {
            $old = $pos;
            $pos = $this->matcher->findText('}', $pos + 1, $limit);
            if (false === $pos) {
                $this->invalidate();
                return false;
            }

            $unescapedFound += $this->countUnescapedQuotes($old, $pos);
        }

        if ($this->matchType == self::LONG && $this->matcher->findText('/', $pos - 1, $limit) === $pos - 1) {
            $this->matchType = self::LEGACY;
            --$pos;
        }

        $seek = $pos;
        while (ctype_space($this->matcher->getChunkFrom($seek - 1, '1'))) {
            $seek--;
        }

        if (in_array($this->matchType, [self::LONG, self::LEGACY]) && $this->matcher->findText(')', $seek - 1, $limit) !== $seek - 1) {
            $this->invalidate();
            return false;
        }

        // $arguments =    trim($this->matcher->getChunkFrom($this->nameEnd, $pos - $this->nameEnd), '() ');
        $rawarguments = trim($this->matcher->getChunkFrom($this->nameEnd, $pos - $this->nameEnd), '() ');
        // arguments can be html encoded. So, decode first
        $arguments = html_entity_decode($rawarguments);
        $this->arguments = trim($arguments);

        if ($this->matchType == self::LEGACY) {
            ++$pos;
        }

        $this->bodyStart = $pos + 1;

        if ($this->matchType == self::SHORT || $this->matchType == self::LEGACY) {
            $this->end = $this->bodyStart;
            $this->bodyStart = false;
        }

        return true;
    }

    public function findEnd($after, $limit)
    {
        if ($this->bodyStart === false) {
            return false;
        }

        $endToken = '{' . strtoupper($this->name) . '}';

        do {
            if (isset($bodyEnd)) {
                $after = $bodyEnd + 1;
            }

            if (false === $bodyEnd = $this->matcher->findText($endToken, $after, $limit)) {
                $this->invalidate();
                return false;
            }
        } while (! $this->matcher->isParsedLocation($bodyEnd));

        $this->bodyEnd = $bodyEnd;
        $this->end = $bodyEnd + strlen($endToken);

        return true;
    }

    public function inside($match)
    {
        return $this->start > $match->start
            && $this->end < $match->end;
    }

    public function replaceWith($string)
    {
        $this->matcher->performReplace($this, $string);
    }

    public function replaceWithPlugin($name, $params, $content)
    {
        $replacement = $this->buildPluginString($name, $params, $content);
        $this->replaceWith($replacement);
    }

    public function buildPluginString($name, $params, $content)
    {
        $hasBody = ! empty($content) && ! ctype_space($content);

        if (is_array($params)) {
            $parts = [];
            foreach ($params as $key => $value) {
                if ($value || $value === '0') {
                    $parts[] = "$key=\"" . str_replace('"', "\\\"", $value) . '"';
                }
            }

            $params = implode(' ', $parts);
        }

        // Replace the content
        if ($hasBody) {
            $type = strtoupper($name);
            $result = "{{$type}($params)}$content{{$type}}";
        } else {
            $plugin = strtolower($name);
            $result = "{{$plugin} $params}";
        }

        return $result;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getArguments()
    {
        return $this->arguments;
    }

    public function getBody()
    {
        return $this->matcher->getChunkFrom($this->bodyStart, $this->bodyEnd - $this->bodyStart);
    }

    public function getStart()
    {
        return $this->start;
    }

    public function getEnd()
    {
        return $this->end;
    }

    public function getInitialStart()
    {
        return $this->initialstart;
    }

    public function getBodyStart()
    {
        return $this->bodyStart;
    }

    public function invalidate()
    {
        $this->matcher = false;
        $this->start = false;
        $this->end = false;
    }

    public function applyOffset($offset)
    {
        $this->start += $offset;
        $this->end += $offset;

        if ($this->nameEnd !== false) {
            $this->nameEnd += $offset;
        }

        if ($this->bodyStart !== false) {
            $this->bodyStart += $offset;
        }

        if ($this->bodyEnd !== false) {
            $this->bodyEnd += $offset;
        }
    }

    private function countUnescapedQuotes($from, $to)
    {
        $string = $this->matcher->getChunkFrom($from, $to - $from);
        $count = 0;

        $pos = -1;
        while (false !== $pos = strpos($string, '"', $pos + 1)) {
            ++$count;
            if ($pos > 0 && $string[$pos - 1] == "\\") {
                --$count;
            }
        }

        return $count;
    }

    public function changeMatcher($matcher)
    {
        $this->matcher = $matcher;
    }

    public function __toString()
    {
        return $this->matcher->getChunkFrom($this->start, $this->end - $this->start);
    }

    public function debug($level = 'X')
    {
        echo "\nMatch [$level] {$this->name} ({$this->arguments}) = {$this->getBody()}\n";
        echo "{$this->bodyStart}-{$this->bodyEnd} {$this->nameEnd} ({$this->matchType})\n";
    }
}
