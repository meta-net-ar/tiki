<?php

namespace Tiki\Lib\core\Toolbar;

use TikiLib;

abstract class ToolbarItem
{
    protected string $wysiwyg = '';
    protected string $icon = '';
    protected string $iconname = '';
    protected string $label = '';
    protected string $type = '';
    protected string $domElementId = '';
    protected string $class = '';
    protected string $syntax = '';
    protected string $editorSyntaxType = '';
    protected string $markdown = '';
    protected string $markdown_wysiwyg = '';
    private array $requiredPrefs = [];

    /**
     * @return string
     */
    protected function getClass(): string
    {
        return $this->class;
    }

    /**
     * @param string $class
     *
     * @return ToolbarItem
     */
    protected function setClass(string $class): ToolbarItem
    {
        $this->class = $class;
        return $this;
    }

    /**
     * @return string
     */
    protected function getDomElementId(): string
    {
        return $this->domElementId;
    }

    /**
     * @param string $domElementId
     *
     * @return ToolbarItem
     */
    public function setDomElementId(string $domElementId): ToolbarItem
    {
        $this->domElementId = $domElementId;
        return $this;
    }


    public static function getTag(string $tagName, bool $wysiwyg = false, bool $is_html = false, bool $is_markdown = false, string $domElementId = '', string|null $objectId = ''): ?ToolbarItem
    {
        global $section;

        //we detect sheet first because it has unique buttons
        if ($section == 'sheet' && $tag = ToolbarSheet::fromName($tagName)) {
            return $tag;
        } elseif ($wysiwyg && $tag = ToolbarSummernoteOnly::fromName($tagName, $is_html, $is_markdown)) {
            return $tag;
        } elseif ($tag = ToolbarItem::getCustomTool($tagName)) {
            return $tag;
        } elseif ($tag = ToolbarInline::fromName($tagName)) {
            return $tag;
        } elseif ($tag = ToolbarBlock::fromName($tagName)) {
            return $tag;
        } elseif ($tag = ToolbarLineBased::fromName($tagName)) {
            return $tag;
        } elseif ($tag = ToolbarWikiplugin::fromName($tagName)) {
            return $tag;
        } elseif ($tag = ToolbarPicker::fromName($tagName, $wysiwyg, $is_html, $is_markdown, $domElementId)) {
            return $tag;
        } elseif ($tag = ToolbarDialog::fromName($tagName, $wysiwyg, $is_html, $is_markdown, $domElementId)) {
            return $tag;
        } elseif ($tagName == 'fullscreen') {
            return new ToolbarFullscreen();
        } elseif ($tagName == 'tikiimage') {
            return new ToolbarFileGallery();
        } elseif ($tagName == 'tikifile') {
            return new ToolbarFileGalleryFile();
        } elseif ($tagName == 'help') {
            return new ToolbarHelptool();
        } elseif ($tagName == 'switcheditor') {
            return new ToolbarSwitchEditor();
        } elseif ($tagName == 'admintoolbar') {
            return new ToolbarAdmin();
        } elseif ($tagName == '-') {
            return new ToolbarSeparator();
        } elseif ($tagName == '|') {
            return new ToolbarSpacer();
        } elseif ($tagName == 'autosave') {
            return new ToolbarAutosave();
        } elseif ($tagName == 'linkfile') {
            return new ToolbarLinkFile($objectId);
        } elseif ($tagName == 'launchplugins') {
            return new ToolbarLaunchPlugins();
        }
        return null;
    }

    public static function getList($include_custom = true): array
    {
        $parserlib = TikiLib::lib('parser');
        $plugins = $parserlib->plugin_get_list();

        foreach ($plugins as & $name) {
            $name = "wikiplugin_$name";
        }

        if ($include_custom) {
            $custom = ToolbarItem::getCustomList();
            $plugins = array_merge($plugins, $custom);
        }

        return array_unique(
            array_merge(
                [
                    '-',
                    '|',
                    'bold',
                    'italic',
                    'underline',
                    'strike',
                    'code',
                    'sub',
                    'sup',
                    'tikilink',
                    'autosave',
                    'link',
                    'anchor',
                    'color',
                    'bgcolor',
                    'center',
                    'table',
                    'rule',
                    'pagebreak',
                    'box',
                    'blockquote',
                    'email',
                    'h1',
                    'h2',
                    'h3',
                    'titlebar',
                    'toc',
                    'list',
                    'numlist',
                    'specialchar',
                    'smiley',
                    'emoji',
                    'templates',
                    'cut',
                    'copy',
                    'paste',
                    'pastetext',
                    'pasteword',
                    'print',
                    'spellcheck',
                    'undo',
                    'redo',
                    'find',
                    'replace',
                    'selectall',
                    'removeformat',
                    'showblocks',
                    'left',
                    'right',
                    'full',
                    'indent',
                    'outdent',
                    'unlink',
                    'style',
                    'fontname',
                    'fontsize',
                    'format',
                    'source',
                    'fullscreen',
                    'help',
                    'tikiimage',
                    'tikifile',
                    'switcheditor',
                    'admintoolbar',
                    'nonparsed',
                    'bidiltr',
                    'bidirtl',
                    'screencapture',
                    'image',
                    'launchplugins',

                    'sheetsave',    // spreadsheet ones
                    'addrow',
                    'addrowmulti',
                    'addrowbefore',
                    'deleterow',
                    'addcolumn',
                    'addcolumnbefore',
                    'deletecolumn',
                    'addcolumnmulti',
                    'sheetgetrange',
                    'sheetfind',
                    'sheetrefresh',
                    'sheetclose',

                    'objectlink',
                    'tikitable',
                    'task',
                    'codeblock',
                    'linkfile'
                ],
                $plugins
            )
        );
    }

    public static function getCustomList(): array
    {
        global $prefs;
        if (isset($prefs['toolbar_custom_list'])) {
            $custom = @unserialize($prefs['toolbar_custom_list']);
            sort($custom);
        } else {
            $custom = [];
        }

        return $custom;
    }

    public static function getCustomTool(string $name): ?ToolbarItem
    {
        global $prefs;
        if (isset($prefs["toolbar_tool_$name"])) {
            $data = unserialize($prefs["toolbar_tool_$name"]);
            return ToolbarItem::fromData($name, $data);
        } else {
            return null;
        }
    }

    public static function isCustomTool(string $name): bool
    {
        global $prefs;
        return isset($prefs["toolbar_tool_$name"]);
    }

    public static function saveTool(
        string $name,
        string $label,
        string $icon = 'img/icons/shading.png',
        string $token = '',
        string $syntax = '',
        string $type = 'Inline',
        string $plugin = ''
    ): void {
        global $tikilib;

        $name = strtolower(TikiLib::remove_non_word_characters_and_accents($name));
        $standard_names = ToolbarItem::getList(false);
        $custom_list = ToolbarItem::getCustomList();
        if (in_array($name, $standard_names)) {     // don't allow custom tools with the same name as standard ones
            $c = 1;
            while (in_array($name . '_' . $c, $custom_list)) {
                $c++;
            }
            $name = $name . '_' . $c;
        }

        $prefName = "toolbar_tool_$name";
        $data = [
            'name'   => $name,
            'label'  => $label,
            'token'  => $token,
            'syntax' => $syntax,
            'type'   => $type,
            'plugin' => $plugin,
        ];

        if (str_contains($icon, 'img/icons/')) {
            $data['icon'] = $icon;
        } else {
            $data['iconname'] = $icon;
        }

        $tikilib->set_preference($prefName, serialize($data));

        if (! in_array($name, $custom_list)) {
            $custom_list[] = $name;
            $tikilib->set_preference('toolbar_custom_list', serialize($custom_list));
        }
    }

    public static function deleteTool($name)
    {
        global $prefs, $tikilib;

        $name = strtolower($name);

        $prefName = "toolbar_tool_$name";
        if (isset($prefs[$prefName])) {
            $tikilib->delete_preference($prefName);

            $list = [];
            if (isset($prefs['toolbar_custom_list'])) {
                $list = unserialize($prefs['toolbar_custom_list']);
            }
            if (in_array($name, $list)) {
                $list = array_diff($list, [$name]);
                $tikilib->set_preference('toolbar_custom_list', serialize($list));
            }
        }
    }

    public static function deleteAllCustomTools()
    {
        $tikilib = TikiLib::lib('tiki');

        $tikilib->query('DELETE FROM `tiki_preferences` WHERE `name` LIKE \'toolbar_tool_%\'');
        $tikilib->delete_preference('toolbar_custom_list');
    }


    public static function fromData($tagName, $data)
    {
        switch ($data['type']) {
            case 'Inline':
                $tag = new ToolbarInline();
                $tag->setSyntax($data['syntax']);
                break;
            case 'Block':
                $tag = new ToolbarBlock();
                $tag->setSyntax($data['syntax']);
                break;
            case 'LineBased':
                $tag = new ToolbarLineBased();
                $tag->setSyntax($data['syntax']);
                break;
            case 'Picker':
                $tag = new ToolbarPicker();
                break;
            case 'Separator':
                $tag = new ToolbarSeparator();
                break;
            case 'Spacer':
                $tag = new ToolbarSpacer();
                break;
            case 'SummernoteOnly':
                $tag = new ToolbarSummernoteOnly($tagName);
                break;
            case 'Fullscreen':
                $tag = new ToolbarFullscreen();
                break;
            case 'Autosave':
                $tag = new ToolbarAutosave();
                break;
            case 'Helptool':
                $tag = new ToolbarHelptool();
                break;
            case 'FileGallery':
                $tag = new ToolbarFileGallery();
                break;
            case 'LinkFile':
                $tag = new ToolbarLinkFile();
                break;
            case 'Wikiplugin':
                if (! isset($data['plugin'])) {
                    $data['plugin'] = '';
                }
                $tag = ToolbarWikiplugin::fromName('wikiplugin_' . $data['plugin']);
                if (empty($tag)) {
                    $tag = new ToolbarWikiplugin();
                }
                break;
            default:
                $tag = new ToolbarInline();
                break;
        }

        $tag->setLabel($data['label'])
            ->setWysiwygToken($data['token'])
            ->setIconName(! empty($data['iconname']) ? $data['iconname'] : 'help')
            ->setIcon(! empty($data['icon']) ? $data['icon'] : 'img/icons/shading.png')
            ->setType($data['type']);

        return $tag;
    }


    public function getWikiHtml(): string
    {
        $onClick = $this->getOnClick();
        if ($onClick) {
            return $this->getSelfLink(
                $onClick,
                htmlentities($this->label, ENT_QUOTES, 'UTF-8'),
                $this->getClass()
            );
        } else {
            return '';
        }
    }

    public function getSyntax(): string
    {
        return $this->syntax;
    }

    public function setSyntax(string $syntax): ToolbarItem
    {
        $this->syntax = $syntax;

        return $this;
    }

    public function getEditorSyntaxType(): string
    {
        return $this->editorSyntaxType;
    }

    public function setEditorSyntaxType(string $editorSyntaxType): ToolbarItem
    {
        $this->editorSyntaxType = $editorSyntaxType;

        return $this;
    }

    public function setMarkdownSyntax($markdown): ToolbarItem
    {
        $this->markdown = $markdown;

        return $this;
    }

    public function getMarkdownHtml(): string
    {
        if ($this->markdown) {
            return $this->getSelfLink(
                $this->getOnClickMarkdown(),
                htmlentities($this->label, ENT_QUOTES, 'UTF-8'),
                $this->getClass()
            );
        } else {
            return '';
        }
    }

    abstract public function getOnClick(): string;

    protected function getOnClickMarkdown(): string
    {
        return $this->getOnClick();
    }

    public function isAccessible(): bool
    {
        global $prefs;

        foreach ($this->requiredPrefs as $prefName) {
            if (! isset($prefs[$prefName]) || $prefs[$prefName] != 'y') {
                return false;
            }
        }

        return true;
    }

    protected function addRequiredPreference(string $prefName): void
    {
        $this->requiredPrefs[] = $prefName;
    }

    protected function setIcon(string $icon): ToolbarItem
    {
        $this->icon = $icon;

        return $this;
    }

    protected function setIconName(string $iconname): ToolbarItem
    {
        $this->iconname = $iconname;

        return $this;
    }

    protected function setLabel(string $label): ToolbarItem
    {
        $this->label = $label;

        return $this;
    }

    protected function setWysiwygToken(string $token): ToolbarItem
    {
        $this->wysiwyg = $token;

        return $this;
    }

    protected function setType(string $type): ToolbarItem
    {
        $this->type = $type;

        return $this;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getWysiwygToken(): string
    {
        return $this->wysiwyg;
    }


    public function getWysiwygWikiToken(): string // wysiwyg_htmltowiki
    {
        return $this->getWysiwygToken();
    }

    public function getWysiwygRenderCallback(): string
    {
        return '';
    }

    public function setMarkdownWysiwyg($markdown_wysiwyg)
    {
        $this->markdown_wysiwyg = $markdown_wysiwyg;

        return $this;
    }

    public function getMarkdownWysiwyg(): string
    {
        return $this->markdown_wysiwyg ?? '';
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getIconHtml(): string
    {
        if (! empty($this->iconname)) {
            $iname = $this->iconname;
        } elseif (! empty($this->icon)) {
            $headerlib = TikiLib::lib('header');
            return '<img src="' . htmlentities($headerlib->convert_cdn($this->icon), ENT_QUOTES, 'UTF-8') . '" alt="'
                . htmlentities($this->getLabel(), ENT_QUOTES, 'UTF-8') . '" title=":'
                . htmlentities($this->getLabel(), ENT_QUOTES, 'UTF-8') . '" class="tips bottom icon">';
        } else {
            $iname = 'help';
        }
        $smarty = TikiLib::lib('smarty');
        return smarty_function_icon(
            [
            'name'   => $iname,
            'ititle' => ':'
                . htmlentities(
                    $this->getLabel(),
                    ENT_QUOTES,
                    'UTF-8'
                ),
            'iclass' => 'tips bottom',
            ],
            $smarty->getEmptyInternalTemplate()
        );
    }

    public function getSelfLink(string $click, string $title, string $class): string
    {
        global $prefs;
        $smarty = TikiLib::lib('smarty');
        $params = [];
        $params['_onclick'] = $click . (substr($click, strlen($click) - 1) != ';' ? ';' : '') . 'return false;';
        $params['_class'] = 'toolbar btn btn-sm px-2 tips bottom' . (! empty($class) ? ' ' . $class : '');
        $params['_ajax'] = 'n';
        $content = $title;
        if ($this->iconname) {
            $params['_icon_name'] = $this->iconname;
            $colon = ':';
            $params['_title'] = $colon . $title;
        } else {
            $params['_icon'] = $this->icon;
        }

        if (
            str_contains($class, 'qt-plugin')
            && ($this->iconname == 'plugin'
                || $this->icon == 'img/icons/plugin.png')
        ) {
            $params['_menu_text'] = 'y';
            $params['_menu_icon'] = 'y';
        }
        return smarty_block_self_link($params, $content, $smarty->getEmptyInternalTemplate());
    }
}
