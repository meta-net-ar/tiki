<?php

namespace Tiki\Lib\core\Toolbar;

use TikiLib;

class ToolbarSwitchEditor extends ToolbarUtilityItem
{
    private string $onClick = '';

    public function __construct()
    {
        global $prefs;


        if ($prefs['markdown_enabled'] === 'y') {
            $label = tra('Syntax and Editor Settings');
        } else {
            $label = tra('Switch Editor (wiki or WYSIWYG)');
        }

        $this->setLabel(tra($label))
            ->setIconName('cog')
            ->setIcon('img/icons/gear.png')
            ->setWysiwygToken('tikiswitch')
            ->setMarkdownSyntax('tikiswitch')
            ->setMarkdownWysiwyg('tikiswitch')
            ->setType('SwitchEditor')
            ->setClass('qt-switcheditor');
    }

    public function getWikiHtml(): string
    {
        return $this->getPlainHtml();
    }

    public function getMarkdownHtml(): string
    {
        return $this->getPlainHtml();
    }

    /**
     * @return string
     */
    private function getPlainHtml(): string
    {
        $smarty = TikiLib::lib('smarty');
        $servicelib = TikiLib::lib('service');

        $params = ['controller' => 'edit', 'action' => 'editor_settings', 'modal' => 1, 'domId' => $this->domElementId, 'syntax' => $this->getEditorSyntaxType()];

        $icon = \SmartyTiki\FunctionHandler\Icon::render(['name' => $this->iconname], $smarty->getEmptyInternalTemplate());
        $url = $servicelib->getUrl($params);
        $title = tra($this->label);

        return "<a title=\":$title\" class=\"toolbar btn btn-sm px-2 qt-help tips bottom click-modal\" href=\"$url\" data-modal-size=\"modal-md\">$icon</a>";
    }

    public function getWysiwygToken(): string
    {
        return $this->wysiwyg;
    }

    public function getWysiwygJs(): string
    {
        $servicelib = TikiLib::lib('service');
        $syntax = $this->getEditorSyntaxType();
        $params = ['controller' => 'edit', 'action' => 'editor_settings', 'modal' => 1, 'domId' => $this->domElementId, 'type' => 'wysiwyg', 'syntax' => $syntax];
        return '$.openModal({show: true, remote: "' . $servicelib->getUrl($params) . '"});';
    }

    public function getMarkdownWysiwyg(): string
    {
        $this->onClick = $this->getWysiwygJs();

        if (! empty($this->markdown_wysiwyg)) {
            return parent::getMarkdownWysiwyg();
        }
        return '';
    }

    /**
     * The switch button may be shown when either WYSIWYG or Markdown is enabled,
     * but only if the user has the dedicated permission to switch editors.
     * - feature_wysiwyg : WYSIWYG editor is enabled
     * - wysiwyg_optional : user is allowed to choose the editor mode
     * - markdown_enabled : Markdown syntax is available
     * - tiki_p_edit_switch_mode : user has the dedicated permission
     *
     * @return bool
     */
    public function isAccessible(): bool
    {
        global $prefs, $tiki_p_edit_switch_mode;

        return ($prefs['markdown_enabled'] === 'y' || ($prefs['feature_wysiwyg'] === 'y' && $prefs['wysiwyg_optional'])) &&
            ! isset($_REQUEST['hdr']) &&
            $tiki_p_edit_switch_mode === 'y';
    }

    /**
     * @return string
     */
    public function getOnClick(): string
    {
        return $this->onClick; // set by markdown wysiwyg
    }
}
