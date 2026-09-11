<?php

namespace Tiki\Lib\core\Toolbar;

use TikiLib;

class ToolbarNewReport extends ToolbarUtilityItem
{
    private string $onClick = '';

    public function __construct()
    {
        $this->setLabel(tra('New Report'))
            ->setIcon('img/icons/plugin.png')
            ->setIconName('chart-bar')
            ->setType('NewReport')
            ->setWysiwygToken('tiki_newreport')
            ->setMarkdownSyntax('tiki_newreport')
            ->setMarkdownWysiwyg('tiki_newreport')
            ->setClass('qt-newreport');
    }

    public function getWikiHtml(): string
    {
        $servicelib = TikiLib::lib('service');
        $smarty = TikiLib::lib('smarty');

        $params = ['controller' => 'report', 'action' => 'wizard', 'modal' => 1];
        $icon = \SmartyTiki\FunctionHandler\Icon::render(['name' => 'chart-bar'], $smarty->getEmptyInternalTemplate());
        $url = $servicelib->getUrl($params);
        $label = tra('New Report');

        return "<a title=\":$label\" class=\"toolbar btn btn-sm px-2 tips bottom click-modal\" href=\"$url\" data-modal-size=\"modal-lg\">$icon</a>";
    }

    public function getOnClick(): string
    {
        return $this->onClick;
    }

    public function getWysiwygToken(): string
    {
        return 'tiki_newreport';
    }

    public function getMarkdownWysiwyg(): string
    {
        $this->onClick = $this->getWysiwygJs();
        return parent::getMarkdownWysiwyg();
    }

    public function getWysiwygJs(): string
    {
        $servicelib = TikiLib::lib('service');
        $params = ['controller' => 'report', 'action' => 'wizard', 'modal' => 1];
        return '$.openModal({show: true, remote: "' . $servicelib->getUrl($params) . '", size: "modal-lg"});';
    }

    public function isAccessible(): bool
    {
        global $prefs;
        return parent::isAccessible()
            && ($prefs['feature_wiki'] ?? '') === 'y'
            && ($prefs['feature_trackers'] ?? '') === 'y';
    }
}
