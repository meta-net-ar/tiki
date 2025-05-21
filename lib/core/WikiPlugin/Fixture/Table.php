<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\WikiPlugin\Fixture;

use Iterator;

class Table implements Iterator
{
    private $title;
    private $headings = [];
    private $data = [];
    private $position = 0;

    public function __construct($string)
    {
        $lines = explode("\n", $string);
        $lines = array_map(function ($line) {
            return array_map('trim', explode('|', $line));
        }, $lines);

        $this->headings = array_shift($lines);
        $length = count($this->headings);
        $this->data = array_map(function ($line) use ($length) {
            return array_pad($line, $length, null);
        }, $lines);
    }

    public function __toString()
    {
        $lines = $this->data;
        array_unshift($lines, array_map(function ($entry) {
            return "__{$entry}__";
        }, $this->headings));

        if ($this->title) {
            array_unshift($lines, [$this->title]);
        }
        return "||" . implode("\n", array_map(function ($line) {
            return implode(' | ', $line);
        }, $lines)) . "||";
    }

    public function getHeadings()
    {
        return $this->headings;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        return array_map(function ($value) {
            return str_replace('%%%', "\n", $value);
        }, $this->data[$this->position]);
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->position;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function valid(): bool
    {
        return isset($this->data[$this->position]);
    }

    public function setValue($pos, $value, $color = null)
    {
        if ($color) {
            $value = "~~$color:$value~~";
        }

        $this->data[$this->position][$pos] = str_replace("\n", '%%%', $value);
    }
}
