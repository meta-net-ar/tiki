<?php

namespace Tiki\Lib\core\Toolbar;

class ToolbarLanguageCheck extends ToolbarUtilityItem
{
    public function __construct()
    {
        $this->setLabel(tra('Language Check'))
            ->setIconName('find')
            ->setIcon('img/icons/find.png')
            ->setWysiwygToken('languagecheck')
            ->setMarkdownSyntax('languagecheck')
            ->setMarkdownWysiwyg('languagecheck')
            ->setType('LanguageCheck')
            ->setClass('qt-languagecheck')
            ->addRequiredPreference('feature_language_check');
    }

    public function getOnClick(): string
    {
        return "if (window.languageCheckTrigger) languageCheckTrigger('" . $this->domElementId . "');";
    }
}
