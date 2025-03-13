<?php

namespace Tiki\Lib\core\Toolbar;

class ToolbarFullscreen extends ToolbarUtilityItem
{
    public function __construct()
    {
        $this->setLabel(tra('Full-screen edit'))
            ->setIconName('fullscreen')
            ->setWysiwygToken('fullscreen')
            ->setMarkdownSyntax('fullscreen')
            ->setMarkdownWysiwyg('fullscreen')
            ->setType('Fullscreen')
            ->setClass('qt-fullscreen')
        ;
    }

    /**
     * @return string
     */
    public function getOnClick(): string
    {
        return 'toggleFullScreen(\'' . $this->domElementId . '\');return false;';
    }
}
