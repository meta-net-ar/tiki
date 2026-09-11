<?php

namespace Tiki\Lib\core\Toolbar;

use TikiLib;

class ToolbarSwitchEditor extends ToolbarUtilityItem
{
    /**
     * @var string
     */
    public $onClick;
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
            ->setClass('qt-switcheditor')
            ->addRequiredPreference('feature_wysiwyg');
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
        global $prefs;

        $this->onClick = $this->getWysiwygJs();

        if (! empty($this->markdown_wysiwyg)) {
            if ($prefs['feature_wysiwyg'] == 'y' && $prefs['wysiwyg_optional'] == 'y') {
                return parent::getMarkdownWysiwyg();
            }
        }
        return '';
    }

    public function isAccessible(): bool
    {
        // TODO make object specific check, but we don't know where this toolbar is down here...
        global $tiki_p_edit_switch_mode;

        return parent::isAccessible() &&
            ! isset($_REQUEST['hdr']) &&        // or in section edit
            $tiki_p_edit_switch_mode === 'y';   // or no perm (new in 7.1)
    }

    /**
     * @return string
     */
    public function getOnClick(): string
    {
        return $this->onClick; // set by markdown wysiwyg
    }
}
