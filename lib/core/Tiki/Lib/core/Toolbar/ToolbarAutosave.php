<?php

namespace Tiki\Lib\core\Toolbar;

class ToolbarAutosave extends ToolbarItem
{
    public function __construct()
    {
        $this->setLabel(tra('Auto Save'))
            ->setIconName('save')
            ->setIcon(tra('img/icons/disk.png'))
            ->setWysiwygToken('autosave')
            ->setMarkdownSyntax('autosave')
            ->setMarkdownWysiwyg('autosave')
            ->setType('Autosave')
            ->setClass('qt-autosave')
            ->addRequiredPreference('feature_ajax');
    }

    public function getMarkdownWysiwyg(): string
    {
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
        return json_encode($item);
    }

    /**
     * @return string
     */
    public function getOnClick(): string
    {
        return 'auto_save(\'' . $this->domElementId . '\');';
    }
}
