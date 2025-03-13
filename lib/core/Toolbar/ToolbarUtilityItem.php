<?php

namespace Tiki\Lib\core\Toolbar;

abstract class ToolbarUtilityItem extends ToolbarItem
{
    abstract public function getOnClick(): string;

    // same toolbar item for non wysiwyg markdown
    public function getMarkdownHtml(): string
    {
        return $this->getWikiHtml();
    }

    public function getMarkdownWysiwyg(): string
    {
        if (! empty($this->markdown_wysiwyg)) {
            \TikiLib::lib('header')->add_jq_onready(
                "tuiToolbarItem$this->markdown_wysiwyg = $.fn.getIcon('$this->iconname').on('click', function () {
                        {$this->getOnClick()}
                    }).get(0);"
            );

            $item = [
                'name'    => $this->markdown,
                'tooltip' => $this->label,
                'el'      => "%~tuiToolbarItem{$this->markdown_wysiwyg}~%",
            ];
            if ($this->class !== '') {
                $item['className'] = $this->class;
            }
            return json_encode($item);
        }
        return '';
    }
}
