<?php

namespace Tiki\Lib\core\Toolbar;

class ToolbarSpacer extends ToolbarItem
{
    public function __construct()
    {
        $this->setIcon('img/trans.png')
            ->setType('Spacer');
    }

    public function getWikiHtml(): string
    {
        return '||';
    }

    public function getOnClick(): string
    {
        return '';
    }
}
