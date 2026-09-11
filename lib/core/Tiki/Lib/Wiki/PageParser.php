<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Wiki;

class PageParser
{
    public $i;
    public $pages = [];
    public $page = [];
    public $currentTag = null;
    public $context = null;
    public $commentsStack = [];
    public $commentId = 0;
    public $iStructure = 0;
    private ?string $inputFile = null;
    private ?string $input = null;

    public function setInputFile(string $file): void
    {
        $this->inputFile = $file;
    }

    public function setInput(string $xml): void
    {
        $this->input = $xml;
    }

    public function parse()
    {
        if ($this->input !== null) {
            return $this->parseString($this->input);
        }

        if ($this->inputFile !== null) {
            return $this->parseFile($this->inputFile);
        }

        throw new \RuntimeException('No XML input provided.');
    }
    public function parseFile(string $file)
    {
        $xml = file_get_contents($file);

        if ($xml === false) {
            throw new \RuntimeException("Unable to read XML file: $file");
        }

        return $this->parseString($xml);
    }

    public function parseString(string $xml)
    {
        $parser = xml_parser_create();

        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, false);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, true);

        xml_set_element_handler(
            $parser,
            [$this, 'startHandler'],
            [$this, 'endHandler']
        );

        xml_set_character_data_handler(
            $parser,
            [$this, 'cdataHandler']
        );

        $result = xml_parse($parser, $xml, true);

        if (! $result) {
            $message = sprintf(
                'XML error: %s at line %d',
                xml_error_string(xml_get_error_code($parser)),
                xml_get_current_line_number($parser)
            );
            throw new \RuntimeException($message);
        }

        return true;
    }

    public function startHandler($parser, $name, $attribs)
    {
        switch ($name) {
            case 'page':
                $this->context = null;
                $this->page = [
                    'data' => '',
                    'comment' => '',
                    'description' => '',
                    'user' => 'admin',
                    'ip' => '0.0.0.0',
                    'lang' => '',
                    'is_html' => false,
                    'hash' => null,
                    'wysiwyg' => null,
                ];

                $this->page = array_merge($this->page, $attribs);

                if ($this->iStructure > 0) {
                    $this->page['structure'] = $this->iStructure;
                }
                break;

            case 'structure':
                ++$this->iStructure;
                break;

            case 'comments':
                $this->context = 'comments';
                $this->commentsStack = [];
                $this->i = -1;
                break;

            case 'attachments':
            case 'history':
            case 'images':
                $this->context = $name;
                $this->i = -1;
                break;

            case 'comment':
                if ($this->context === 'comments') {
                    ++$this->i;

                    $this->page[$this->context][$this->i] = $attribs;
                    $this->page[$this->context][$this->i]['parentId'] = empty($this->commentsStack)
                        ? 0
                        : $this->commentsStack[count($this->commentsStack) - 1];

                    $this->page[$this->context][$this->i]['threadId'] = ++$this->commentId;
                    $this->commentsStack[] = $this->commentId;
                } else {
                    $this->currentTag = $name;
                }
                break;

            case 'attachment':
                ++$this->i;
                $this->page[$this->context][$this->i] = array_merge(
                    ['comment' => ''],
                    $attribs
                );
                break;

            case 'version':
                ++$this->i;
                $this->page[$this->context][$this->i] = array_merge(
                    ['comment' => '', 'description' => '', 'ip' => '0.0.0.0'],
                    $attribs
                );
                break;

            case 'image':
                ++$this->i;
                $this->page[$this->context][$this->i] = $attribs;
                break;

            default:
                $this->currentTag = $name;
                break;
        }
    }

    public function endHandler($parser, $name)
    {
        $this->currentTag = null;

        switch ($name) {
            case 'comments':
            case 'attachments':
            case 'history':
            case 'images':
                $this->context = null;
                break;

            case 'comment':
                if ($this->context === 'comments') {
                    array_pop($this->commentsStack);
                }
                break;

            case 'page':
                $this->pages[] = $this->page;
                break;

            case 'structure':
                --$this->iStructure;
                break;
        }
    }

    public function cdataHandler($parser, $data)
    {
        $data = trim($data);

        if ($data === '' || $this->currentTag === null) {
            return true;
        }

        if (empty($this->context)) {
            $this->page[$this->currentTag] = ($this->page[$this->currentTag] ?? '') . $data;
        } else {
            $this->page[$this->context][$this->i][$this->currentTag] =
                ($this->page[$this->context][$this->i][$this->currentTag] ?? '') . $data;
        }

        return true;
    }

    public function getPages()
    {
        return $this->pages;
    }
}
