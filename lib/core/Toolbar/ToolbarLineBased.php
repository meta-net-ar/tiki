<?php

namespace Tiki\Lib\core\Toolbar;

class ToolbarLineBased extends ToolbarInline // Will change in the future
{
    public static function fromName($tagName): ?ToolbarItem
    {
        $iconname = '';
        $wysiwyg = '';
        $syntax = '';
        $markdown = '';
        $markdown_wysiwyg = '';

        switch ($tagName) {
            case 'list':
                $label = tra('Bullet List');
                $iconname = 'list';
                $wysiwyg = 'ul';
                $syntax = '* text';
                $markdown = '* text';
                $markdown_wysiwyg = 'ul';
                break;
            case 'numlist':
                $label = tra('Numbered List');
                $iconname = 'list-numbered';
                $wysiwyg = 'ol';
                $syntax = '# text';
                $markdown = '1. text';
                $markdown_wysiwyg = 'ol';
                break;
            case 'indent':
                $label = tra('Indent');
                $iconname = 'indent';
                $markdown = '> text';
                $markdown_wysiwyg = 'indent';
                break;
            case 'outdent':
                $label = tra('Outdent');
                $iconname = 'outdent';
                $markdown = '< text';
                $markdown_wysiwyg = 'outdent';
                break;
            default:
                return null;
        }

        $tag = new self();
        $tag->setLabel($label)
            ->setWysiwygToken($wysiwyg)
            ->setIconName(! empty($iconname) ? $iconname : 'help')
            ->setSyntax($syntax)
            ->setMarkdownSyntax($markdown)
            ->setMarkdownWysiwyg($markdown_wysiwyg)
            ->setType('LineBased')
            ->setClass('qt-line');

        return $tag;
    }

    /**
     * @return string
     */
    public function getOnClick(): string
    {
        if ($this->syntax) {    // indent and outdent are markup only
            return 'insertAt(\'' . $this->domElementId . '\', \'' .
                addslashes(
                    htmlentities($this->syntax, ENT_COMPAT, 'UTF-8')
                ) . '\', true, true);';
        } else {
            return '';
        }
    }

    /**
     * @return string
     */
    public function getOnClickMarkdown(): string
    {
        return 'insertAt(\'' . $this->domElementId . '\', \'' .
            addslashes(
                htmlentities($this->markdown, ENT_COMPAT, 'UTF-8')
            ) . '\', true, true);';
    }
}
