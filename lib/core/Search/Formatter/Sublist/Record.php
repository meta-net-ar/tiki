<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Search\Formatter\Sublist;

use Search_Formatter;
use Exception;

class Record
{
    private $key;
    private $multiple;
    private $required;
    private $body;
    /** Array of Record */
    private array $sublists = [];
    private ?self $parent;

    private $parser;

    public function __construct(string $key, Parser $parser)
    {
        $this->key = $key;
        $this->multiple = false;
        $this->required = false;
        $this->parser = $parser;
        $this->parent = null;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function setBody($body)
    {
        $this->body = $body;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent(self $parent): void
    {
        $this->parent = $parent;
    }

    public function addSublist(Record $sublist): void
    {
        $sublist->setParent($this);
        $this->sublists[] = $sublist;
    }

    public function getSublists()
    {
        return $this->sublists;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function isMultiple()
    {
        return $this->multiple;
    }

    public function setMultiple($multiple)
    {
        $this->multiple = $multiple;
    }

    public function isRequired()
    {
        return $this->required;
    }

    public function setRequired($required)
    {
        $this->required = $required;
    }

    public function executeOverDataset(&$data, &$root_data, Search_Formatter $sf): void
    {
        $executor = new Executor($this, $sf);
        try {
            $executor->runOnDataset($data, $root_data);
        } catch (Exception $e) {
            if (empty($e->suppress_feedback)) {
                \Feedback::error(tr('Sublist error: %0', $e->getMessage()));
                trigger_error($e->getMessage(), E_USER_WARNING);
            }
        }
    }

    public function getFormats()
    {
        $formats = [];
        $parts = $this->parser->getParts($this->body);
        foreach ($parts as $part) {
            if ($part['name'] == 'format') {
                $formats[] = [
                    'body' => $part['match']->getBody(),
                    'arguments' => $part['arguments'],
                ];
            }
        }
        return $formats;
    }

    public function getFilters()
    {
        $filters = [];
        $parts = $this->parser->getParts($this->body);
        foreach ($parts as $part) {
            if ($part['name'] == 'filter') {
                $filters[] = [
                    'match' => $part['match'],
                    'arguments' => $part['arguments'],
                ];
            }
        }
        return $filters;
    }
}
