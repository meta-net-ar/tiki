<?php

namespace Tiki\Lib\core\Toolbar;

use TikiLib;

/**
 * Toolbar item for switching the syntax of the current editor
 * (e.g., between Tiki syntax and Markdown) without changing the
 * editor mode (plain vs WYSIWYG).
 *
 * This class extends ToolbarUtilityItem and provides functionality to render
 * a toolbar button that allows users to switch the syntax of the editor.
 *
 * @package Tiki\Toolbar
 */
class ToolbarSwitchSyntax extends ToolbarUtilityItem
{
    public function __construct()
    {
        $this->setLabel(tra('Switch Syntax'))
            ->setIconName('markdown')
            ->setIcon('img/icons/markdown.svg')
            ->setMarkdownSyntax('tikiswitchsyntax')
            ->setMarkdownWysiwyg('tikiswitchsyntax')
            ->setType('SwitchSyntax')
            ->setClass('qt-switchsyntax')
            ->addRequiredPreference('markdown_enabled');
    }

    public function getWikiHtml(): string
    {
        $smarty = TikiLib::lib('smarty');
        $servicelib = TikiLib::lib('service');

        $params = ['controller' => 'edit', 'action' => 'syntax_settings', 'modal' => 1, 'domId' => $this->domElementId, 'syntax' => $this->getEditorSyntaxType()];

        $icon = \SmartyTiki\FunctionHandler\Icon::render(['name' => $this->iconname], $smarty->getEmptyInternalTemplate());
        $url = $servicelib->getUrl($params);
        $title = tra($this->label);

        return "<a title=\":$title\" class=\"toolbar btn btn-sm px-2 qt-help tips bottom click-modal\" href=\"$url\" data-modal-size=\"modal-md\">$icon</a>";
    }
    public function getWysiwygToken(): string
    {
        return '';
    }

    public function getMarkdownWysiwyg(): string
    {
        global $prefs;

        if (! empty($this->markdown_wysiwyg)) {
            if ($prefs['feature_wysiwyg'] == 'y' && $prefs['wysiwyg_optional'] == 'y') {
                return parent::getMarkdownWysiwyg();
            }
        }
        return '';
    }

    public function getOnClick(): string
    {
        $servicelib = TikiLib::lib('service');

        $params = [
            'controller' => 'edit',
            'action' => 'syntax_settings',
            'modal' => 1,
            'domId' => $this->domElementId,
            'syntax' => $this->getEditorSyntaxType(),
        ];

        return '$.openModal({show: true, remote: "' . $servicelib->getUrl($params) . '"});';
    }
}
