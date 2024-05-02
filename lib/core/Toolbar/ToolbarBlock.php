<?php

namespace Tiki\Lib\core\Toolbar;

class ToolbarBlock extends ToolbarInline // Will change in the future
{
    public static function fromName($tagName): ?ToolbarItem
    {
        global $prefs;

        $label = '';
        $wysiwyg = '';
        $syntax = '';
        $markdown = '';
        $markdown_wysiwyg = '';

        switch ($tagName) {
            case 'center':
                $label = tra('Align Center');
                $iconname = 'align-center';
                $wysiwyg = 'JustifyCenter';
                if ($prefs['feature_use_three_colon_centertag'] == 'y') {
                    $syntax = ":::text:::";
                } else {
                    $syntax = "::text::";
                }
                break;
            case 'rule':
                $label = tra('Horizontal Bar');
                $iconname = 'horizontal-rule';
                $wysiwyg = 'HorizontalRule';
                $syntax = '---';
                $markdown = '***';
                $markdown_wysiwyg = 'hr';
                break;
            case 'pagebreak':
                $label = tra('Page Break');
                $iconname = 'page-break';
                $wysiwyg = 'PageBreak';
                $syntax = $prefs['wiki_page_separator'];
                break;
            case 'box':
                $label = tra('Box');
                $iconname = 'box';
                $wysiwyg = 'Box';
                $syntax = '^text^';
                break;
            case 'email':
                $label = tra('Email');
                $iconname = 'envelope';
                $syntax = '[mailto:email@example.com|text]';
                break;
            case 'blockquote':
                $label = tra('Block quote');
                $iconname = 'quote-left';
                $markdown_wysiwyg = 'quote';
                break;
            case 'h1':
            case 'h2':
            case 'h3':
                $label = tra('Heading') . ' ' . $tagName[1];
                $iconname = $tagName;
                $syntax = str_repeat('!', $tagName[1]) . ' text';
                $markdown = str_repeat('#', $tagName[1]) . ' text';
                $markdown_wysiwyg = 'heading';
                break;
            case 'titlebar':
                $label = tra('Title bar');
                $iconname = 'title';
                $syntax = '-=text=-';
                break;
            case 'toc':
                $label = tra('Table of contents');
                $iconname = 'book';
                $wysiwyg = 'TOC';
                $syntax = '{maketoc}';
                break;
            case 'task':
                $label = tra('Task');
                $iconname = 'check-square-o';
                $markdown_wysiwyg = 'task';
                break;
            case 'codeblock':
                $label = tra('Code block');
                $iconname = 'file-text-o';
                $markdown_wysiwyg = 'codeblock';
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
            ->setType('Block')
            ->setClass('qt-block');

        return $tag;
    }

    /**
     * @return string
     */
    public function getOnClick(): string
    {
        global $prefs;

        if ($this->syntax == $prefs['wiki_page_separator']) {
            // this breaks the toolbar when inside nested plugins if wiki_pagination is enabled becasue
            // \WikiLib::get_number_of_pages doesn't check where the ...page... string occurs in the data
            // so we get javascript to reassemble the "...page..." syntax client-side

            return 'insertAt(\'' . $this->domElementId . '\', \'...\'+\'page\'+\'...\', true);';
        } else {
            return 'insertAt(\'' . $this->domElementId . '\', \'' .
                addslashes(
                    htmlentities($this->syntax, ENT_COMPAT, 'UTF-8')
                ) . '\', true);';
        }
    }
}
