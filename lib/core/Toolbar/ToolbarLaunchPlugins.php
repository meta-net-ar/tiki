<?php

namespace Tiki\Lib\core\Toolbar;

use TikiLib;

class ToolbarLaunchPlugins extends ToolbarUtilityItem
{
    private string $onClick = '';

    public function __construct()
    {
        $this->setLabel(tra('Launch Plugins'))
            ->setIcon('img/icons/plugin.png')
            ->setIconName('puzzle-piece')
            ->setType('LaunchPlugins')
            ->setWysiwygToken('tiki_launchplugins')
            ->setMarkdownSyntax('tiki_launchplugins')
            ->setMarkdownWysiwyg('tiki_launchplugins')
            ->setClass('qt-launchplugins');
    }

    public function getWikiHtml(): string
    {
        return $this->getPlainHtml();
    }

    public function getMarkdownHtml(): string
    {
        return self::getPlainHtml(true);
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function getPlainHtml(): string
    {
        $servicelib = TikiLib::lib('service');

        $params = ['controller' => 'edit', 'action' => 'help', 'modal' => 1];
        $params['plugins'] = 1;
        $params['areaId'] = $this->domElementId;

        $icon = $this->getIconHtml();
        $url = $servicelib->getUrl($params);
        $label = $this->getLabel();

        return "<a title=\":$label\" class=\"toolbar btn btn-sm px-2 qt-help tips bottom click-modal\" href=\"$url\" data-modal-size=\"modal-md\">$icon</a>";
    }

    public function getWysiwygToken(): string
    {
        return 'tiki_launchplugins';
    }

    public function getMarkdownWysiwyg(): string
    {

        $this->onClick = $this->getWysiwygJs(true);

        return parent::getMarkdownWysiwyg();
    }

    public function getWysiwygJs(): string
    {
        $servicelib = TikiLib::lib('service');

        $params = ['controller' => 'edit', 'action' => 'help', 'modal' => 1];
        $params['plugins'] = 1;

        // multiple editor could share the same toolbar commands, so area_id must be added when clicked
        $params['areaId'] = $this->domElementId;

        return '$.openModal({show: true, remote: "' . $servicelib->getUrl($params) . '"});';
    }
    public function getOnClick(): string
    {
        // set by markdown wysiwyg
        return $this->onClick;
    }
}
