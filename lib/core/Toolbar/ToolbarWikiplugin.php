<?php

namespace Tiki\Lib\core\Toolbar;

use TikiLib;

class ToolbarWikiplugin extends ToolbarUtilityItem
{
    private string $pluginName;

    public static function fromName($name)
    {
        $parserlib = TikiLib::lib('parser');

        if (substr($name, 0, 11) == 'wikiplugin_') {
            $name = substr($name, 11);
            if ($info = $parserlib->plugin_info($name)) {
                $tag = new self();
                $tag->setLabel(str_ireplace('wikiplugin_', '', $info['name']))
                    ->setWysiwygToken(strtolower(str_replace(' ', '', $info['name'])))
                    ->setMarkdownSyntax($name)
                    ->setMarkdownWysiwyg(str_replace(' ', '_', $info['name']))
                    ->setPluginName($name)
                    ->setType('Wikiplugin')
                    ->setClass('qt-plugin');

                if (! empty($info['iconname'])) {
                    $tag->setIconName($info['iconname']);
                } elseif (! empty($info['icon'])) {
                    $tag->setIcon($info['icon']);
                } else {
                    $tag->setIcon('img/icons/plugin.png');
                }

                TikiLib::lib('header')->add_jsfile('lib/jquery_tiki/tiki-toolbars.js');

                return $tag;
            }
        }
    }

    public function setPluginName(string $name): ToolbarItem
    {
        $this->pluginName = $name;

        return $this;
    }

    public function getPluginName(): string
    {
        return $this->pluginName;
    }

    public function isAccessible(): bool
    {
        $parserlib = TikiLib::lib('parser');
        $dummy_output = '';
        return parent::isAccessible() && $parserlib->plugin_enabled($this->pluginName, $dummy_output);
    }

    public function getWysiwygToken(): string
    {
        return $this->wysiwyg;
    }

    public function getWysiwygWikiToken(): string // wysiwyg_htmltowiki
    {
        switch ($this->pluginName) {
            case 'img':
                $this->wysiwyg = 'wikiplugin_img';
                break;
            default:
        }

        return $this->getWysiwygToken();
    }

    /**
     * @return string
     */
    public function getOnClick(): string
    {
        return 'popupPluginForm(\'' . $this->domElementId . '\',\'' . $this->pluginName . '\')';
    }
}
