<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Wiki;

use XML_Parser;

class PageParser extends XML_Parser
{
    public $i;
    public $pages;
    public $page;
    public $currentTag = null;
    public $context = null;
    public $folding = false; // keep tag as original
    public $commentsStack = [];
    public $commentId = 0;
    public $iStructure = 0;

    public function startHandler($parser, $name, &$attribs)
    {
        switch ($name) {
            case 'page':
                $this->context = null;
                if (is_array($attribs)) {
                    $this->page = [
                        'data' => '',
                        'comment' => '',
                        'description' => '',
                        'user' => 'admin',
                        'ip' => '0.0.0.0',
                        'lang' => '',
                        'is_html' => false,
                        'hash' => null,
                        'wysiwyg' => null
                    ];
                    $this->page = array_merge($this->page, $attribs);
                }
                if ($this->iStructure > 0) {
                    $this->page['structure'] = $this->iStructure;
                }
                break;

            case 'structure':
                ++$this->iStructure;
                break;

            case 'comments':
                $comentsStack = [];
                break;
            case 'attachments':
            case 'history':
            case 'images':
                $this->context = $name;
                $this->i = -1;
                break;

            case 'comment':
                if ($this->context == 'comments') {
                    ++$this->i;
                    $this->page[$this->context][$this->i] = $attribs;
                    $this->page[$this->context][$this->i]['parentId'] = empty($this->commentsStack) ? 0 : $this->commentsStack[count($this->commentsStack) - 1];
                    $this->page[$this->context][$this->i]['threadId'] = ++$this->commentId;
                    array_push($this->commentsStack, $this->commentId);
                } else {
                    $this->currentTag = $name;
                }
                break;

            case 'attachment':
                ++$this->i;
                $this->page[$this->context][$this->i] = ['comment' => ''];
                $this->page[$this->context][$this->i] = array_merge($this->page[$this->context][$this->i], $attribs);
                break;

            case 'version':
                ++$this->i;
                $this->page[$this->context][$this->i] = ['comment' => '', 'description' => '', 'ip' => '0.0.0.0'];
                $this->page[$this->context][$this->i] = array_merge($this->page[$this->context][$this->i], $attribs);
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
                array_pop($this->commentsStack);
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
        if (empty($data)) {
            return true;
        }
        if (empty($this->context)) {
            $this->page[$this->currentTag] = $data;
        } else {
            $this->page[$this->context][$this->i][$this->currentTag] = $data;
        }
    }

    public function getPages()
    {
        return $this->pages;
    }
}
