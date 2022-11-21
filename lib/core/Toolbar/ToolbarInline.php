<?php

namespace Tiki\Lib\core\Toolbar;

class ToolbarInline extends ToolbarItem
{

    public static function fromName($tagName): ?ToolbarItem
    {
        $markdown = '';
        $markdown_wysiwyg = '';

        switch ($tagName) {
            case 'bold':
                $label = tra('Bold');
                $icon = tra('img/icons/text_bold.png');
                $iconname = 'bold';
                $wysiwyg = 'Bold';
                $syntax = '__text__';
                $markdown = '__text__';
                $markdown_wysiwyg = 'bold';
                break;
            case 'italic':
                $label = tra('Italic');
                $icon = tra('img/icons/text_italic.png');
                $iconname = 'italic';
                $wysiwyg = 'Italic';
                $syntax = "''text''";
                $markdown = '_text_';
                $markdown_wysiwyg = 'italic';
                break;
            case 'underline':
                $label = tra('Underline');
                $icon = tra('img/icons/text_underline.png');
                $iconname = 'underline';
                $wysiwyg = 'Underline';
                $syntax = "===text===";
                break;
            case 'strike':
                $label = tra('Strikethrough');
                $icon = tra('img/icons/text_strikethrough.png');
                $iconname = 'strikethrough';
                $wysiwyg = 'Strike';
                $syntax = '--text--';
                $markdown = '~~text~~';
                $markdown_wysiwyg = 'strike';
                break;
            case 'code':
                $label = tra('Inline Code');
                $icon = tra('img/icons/page_white_code.png');
                $iconname = 'code';
                $wysiwyg = 'Code';
                $syntax = '-+text+-';
                $markdown = '`text`';
                $markdown_wysiwyg = 'code';
                break;
            case 'nonparsed':
                $label = tra('Non-parsed (wiki syntax does not apply)');
                $icon = tra('img/icons/noparse.png');
                $iconname = 'ban';
                $wysiwyg = '';
                $syntax = '~np~text~/np~';
                break;
            default:
                return null;
        }

        $tag = new self();
        $tag->setLabel($label)
            ->setWysiwygToken($wysiwyg)
            ->setIconName(! empty($iconname) ? $iconname : 'help')
            ->setIcon(! empty($icon) ? $icon : 'img/icons/shading.png')
            ->setSyntax($syntax)
            ->setMarkdownSyntax($markdown)
            ->setMarkdownWysiwyg($markdown_wysiwyg)
            ->setType('Inline')
            ->setClass('qt-inline');

        return $tag;
    }

    /**
     * @return string
     */
    public function getOnClick(): string
    {
        if ($this->syntax === '~np~text~/np~') {
            // closing non-parse tags get removed by the parser so combine that in js
            return 'insertAt(\'' . $this->domElementId . '\', \'~np~text~\'+\'/np~\', true);';
        } else {
            return 'insertAt(\'' . $this->domElementId . '\', \'' .
                addslashes(
                    htmlentities($this->syntax, ENT_COMPAT, 'UTF-8')
                ) . '\');';
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
            ) . '\');';
    }
}
