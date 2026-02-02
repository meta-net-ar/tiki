<?php

namespace Tiki\Lib\core\Toolbar;

use TikiLib;

class ToolbarLinkFile extends ToolbarUtilityItem
{
    private $trackerItemId;

    public function __construct($trackerItemId)
    {
        $this->trackerItemId = $trackerItemId;
        $this->setLabel(tra('Link file from tracker item'))
            ->setIcon('img/icons/file-manager.png')
            ->setIconName('file')
            ->setType('LinkFile')
            ->setWysiwygToken('linkfile')
            ->setMarkdownSyntax('linkfile')
            ->setMarkdownWysiwyg('linkfile')
            ->setClass('qt-linkfile');
    }

    private function getUrl(): string
    {
        $servicelib = TikiLib::lib('service');
        return $servicelib->getUrl([
            'controller' => 'tracker',
            'action' => 'itemFiles',
            'itemId' => $this->trackerItemId,
            'domId' => $this->domElementId,
            'modal' => 1
        ]);
    }

    private function getPlainHtml(): string
    {
        $url = $this->getUrl();
        $icon = $this->getIconHtml();
        $title = $this->getLabel();
        return "<a title=\":$title\" class=\"toolbar btn btn-sm px-2 tips bottom click-modal\" href=\"$url\">$icon</a>";
    }

    public function getWikiHtml(): string
    {
        return $this->getPlainHtml();
    }

    public function getMarkdownHtml(): string
    {
        return $this->getPlainHtml();
    }

    public function getWysiwygToken(): string
    {
        return 'linkfile';
    }

    public function getOnClick(): string
    {
        return '$.openModal({show: true, remote: "' . $this->getUrl() . '"});';
    }
}
