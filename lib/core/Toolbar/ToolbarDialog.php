<?php

namespace Tiki\Lib\core\Toolbar;

use TikiLib;

class ToolbarDialog extends ToolbarItem
{
    protected $isMarkdown;
    protected $isWysiwyg;
    protected array $list;
    protected int $index;
    protected string $name;

    public static function fromName(string $tagName, bool $is_wysiwyg = false, bool $is_html = false, bool $is_markdown = false, string $domElementId = ''): ?ToolbarItem
    {
        global $prefs;

        $tool_prefs = [];
        $markdown_wysiwyg = '';
        $markdown = '';

        switch ($tagName) {
            case 'tikilink':
                $label = tra('Wiki Link');
                $iconname = 'link';
                $icon = tra('img/icons/page_link.png');
                $wysiwyg = '';  // cke link dialog now adapted for wiki links
                $markdown = 'tikilink';
                $markdown_wysiwyg = 'tikilink';
                $list = [
                    tra("Wiki Link"),
                    '<label for="tbWLinkDesc">' . tra("Show this text") . '</label>',
                    '<input type="text" id="tbWLinkDesc" class="ui-widget-content ui-corner-all" style="width: 98%" />',
                    '<label for="tbWLinkPage">' . tra("Link to this page") . '</label>',
                    '<input type="text" id="tbWLinkPage" class="ui-widget-content ui-corner-all" style="width: 98%" />',
                    $prefs['wikiplugin_alink'] == 'y' ? '<label for="tbWLinkAnchor">' . tra(
                        "Anchor"
                    ) . ':</label>' : '',
                    $prefs['wikiplugin_alink'] == 'y' ? '<input type="text" id="tbWLinkAnchor" class="ui-widget-content ui-corner-all" style="width: 98%" />' : '',
                    $prefs['feature_semantic'] == 'y' ? '<label for="tbWLinkRel">' . tra(
                        "Semantic relation"
                    ) . ':</label>' : '',
                    $prefs['feature_semantic'] == 'y' ? '<input type="text" id="tbWLinkRel" class="ui-widget-content ui-corner-all" style="width: 98%" />' : '',
                    '{"open": function () { dialogInternalLinkOpen(area_id, clickedElement); },
                        "buttons": { "' . tra("Cancel") . '": function() { dialogSharedClose(area_id,this); },' .
                    '"' . tra("Insert") . '": function() { dialogInternalLinkInsert(area_id,this); }}}',
                ];

                break;
            case 'objectlink':
                $types = TikiLib::lib('unifiedsearch')->getSupportedTypes();
                $options = '';
                foreach ($types as $type => $title) {
                    $options .= '<option value="' . $type . '">' . $title . '</option>';
                }
                $label = tra('Object Link');
                $iconname = 'link-external-alt';
                $icon = tra('img/icons/page_link.png');
                $wysiwyg = '';

                $smarty = TikiLib::lib('smarty');
                $object_selector = smarty_function_object_selector(
                    [
                                                                       '_id'        => 'tbOLinkObjectSelector',
                                                                       '_class'     => 'ui-widget-content ui-corner-all',
                                                                       //              '_format' => '{title}',
                                                                       '_filter'    => ['type' => ''],
                                                                       '_parent'    => 'tbOLinkObjectType',
                                                                       '_parentkey' => 'type',
                                                                   ],
                    $smarty->getEmptyInternalTemplate()
                );

                $list = [
                    tra('Object Link'),
                    '<label for="tbOLinkDesc">' . tra("Show this text") . '</label>',
                    '<input type="text" id="tbOLinkDesc" />',
                    '<label for="tbOLinkObjectType">' . tra("Types of object") . '</label>',
                    '<select id="tbOLinkObjectType" class="ui-widget-content ui-corner-all" style="width: 98%">' .
                    '<option value="*">' . tra('All') . '</option>' .
                    $options .
                    '</select>',
                    '<label for="tbOLinkObjectSelector">' . tra("Link to this object") . '</label>',
                    $object_selector,
                    //                      '<input type="text" id="tbOLinkObjectSelector" class="ui-widget-content ui-corner-all" style="width: 98%" />',
                    '{"open": function () { dialogObjectLinkOpen(area_id); },
                        "buttons": { "' . tra("Cancel") . '": function() { dialogSharedClose(area_id,this); },' .
                    '"' . tra("Insert") . '": function() { dialogObjectLinkInsert(area_id,this); }}}',
                ];

                break;
            case 'link':
                $wysiwyg = 'Link';
                $label = tra('External Link');
                $iconname = 'link-external';
                $iconname = 'external-link-alt';    // for isVueTool but will work if not too
                $icon = tra('img/icons/world_link.png');
                $markdown = 'link';
                $markdown_wysiwyg = 'link';
                $list = [
                    tra('External Link'),
                    '<label for="tbLinkDesc">' . tra("Show this text") . '</label>',
                    '<input type="text" id="tbLinkDesc" class="ui-widget-content ui-corner-all" style="width: 98%" />',
                    '<label for="tbLinkURL">' . tra("link to this URL") . '</label>',
                    '<input type="text" id="tbLinkURL" class="ui-widget-content ui-corner-all" style="width: 98%" />',
                    '<label for="tbLinkRel">' . tra("Relation") . ':</label>',
                    '<input type="text" id="tbLinkRel" class="ui-widget-content ui-corner-all" style="width: 98%" />',
                    $prefs['cachepages'] == 'y' ? '<br /><label for="tbLinkNoCache" style="display:inline;">' . tra(
                        "No cache"
                    ) . ':</label>' : '',
                    $prefs['cachepages'] == 'y' ? '<input type="checkbox" id="tbLinkNoCache" class="ui-widget-content ui-corner-all" />' : '',
                    '{"width": 300, "open": function () { dialogExternalLinkOpen( area_id ) },
                        "buttons": { "' . tra("Cancel") . '": function() { dialogSharedClose(area_id,this); },' .
                    '"' . tra("Insert") . '": function() { dialogExternalLinkInsert(area_id,this) }}}',
                ];
                break;

            case 'table':
            case 'tikitable':
                $iconname = 'table';
                $icon = tra('img/icons/table.png');
                $wysiwyg = 'Table';
                $markdown = 'table';
                $markdown_wysiwyg = 'table';
                $label = tra('Table Builder');
                $list = [
                    tra('Table Builder'),
                    '{"open": function () { dialogTableOpen(area_id,this); },
                        "width": 320, "buttons": { "' . tra(
                        "Cancel"
                    ) . '": function() { dialogSharedClose(area_id,this); },' .
                    '"' . tra("Insert") . '": function() { dialogTableInsert(area_id,this); }}}',
                ];
                break;

            case 'find':
                $icon = tra('img/icons/find.png');
                $iconname = 'search';
                $wysiwyg = 'Find';
                $markdown = ''; // TODO
                $label = tra('Find Text');
                $list = [
                    tra('Find Text'),
                    '<label>' . tra("Search") . ':</label>',
                    '<input type="text" id="tbFindSearch" class="ui-widget-content ui-corner-all" />',
                    '<label for="tbFindCase" style="display:inline;">' . tra("Case Insensitivity") . ':</label>',
                    '<input type="checkbox" id="tbFindCase" checked="checked" class="ui-widget-content ui-corner-all" />',
                    '<p class="description">' . tra("Note: Uses regular expressions") . '</p>',
                    // TODO add option to not
                    '{"open": function() { dialogFindOpen(area_id); },' .
                    '"buttons": { "' . tra("Close") . '": function() { dialogSharedClose(area_id,this); },' .
                    '"' . tra("Find") . '": function() { dialogFindFind(area_id); }}}',
                ];

                break;

            case 'replace':
                $icon = tra('img/icons/text_replace.png');
                $iconname = 'repeat';
                $wysiwyg = 'Replace';
                $markdown = ''; // TODO
                $label = tra('Text Replace');
                $tool_prefs[] = 'feature_wiki_replace';

                $list = [
                    tra('Text Replace'),
                    '<label for="tbReplaceSearch">' . tra("Search") . ':</label>',
                    '<input type="text" id="tbReplaceSearch" class="ui-widget-content ui-corner-all" />',
                    '<label for="tbReplaceReplace">' . tra("Replace") . ':</label>',
                    '<input type="text" id="tbReplaceReplace" class="ui-widget-content ui-corner-all clearfix" />',
                    '<label for="tbReplaceCase" style="display:inline;">' . tra("Case Insensitivity") . ':</label>',
                    '<input type="checkbox" id="tbReplaceCase" checked="checked" class="ui-widget-content ui-corner-all" />',
                    '<br /><label for="tbReplaceAll" style="display:inline;">' . tra("Replace All") . ':</label>',
                    '<input type="checkbox" id="tbReplaceAll" checked="checked" class="ui-widget-content ui-corner-all" />',
                    '<p class="description">' . tra("Note: Uses regular expressions") . '</p>',
                    // TODO add option to not
                    '{"open": function() { dialogReplaceOpen(area_id); },' .
                    '"buttons": { "' . tra("Close") . '": function() { dialogSharedClose(area_id,this); },' .
                    '"' . tra("Replace") . '": function() { dialogReplaceReplace(area_id); }}}',
                ];

                break;

            default:
                return null;
        }

        $tag = new self();
        $tag->name = $tagName;

        $tag->isMarkdown = $is_markdown;
        $tag->isWysiwyg = $is_wysiwyg;

        $tag->setWysiwygToken($wysiwyg)
            ->setMarkdownWysiwyg($markdown_wysiwyg)
            ->setMarkdownSyntax($markdown)
            ->setLabel($label)
            ->setIconName(! empty($iconname) ? $iconname : 'help')
            ->setIcon(! empty($icon) ? $icon : 'img/icons/shading.png')
            ->setList($list)
            ->setType('Dialog')
            ->setClass('qt-picker')
            ->setDomElementId($domElementId);

        foreach ($tool_prefs as $pref) {
            $tag->addRequiredPreference($pref);
        }

        global $toolbarDialogIndex;
        ++$toolbarDialogIndex;
        $tag->index = $toolbarDialogIndex;

        $tag->setupJs();

        return $tag;
    }

    public function setList(array $list): ToolbarItem
    {
        $this->list = $list;

        return $this;
    }

    public function getOnClick(): string
    {
        if ($this->isVueTool()) {
            return 'toolbarDialog(\'' . $this->name . '\',\'' . $this->domElementId . '\')';
        } else {
            return 'displayDialog( this, ' . $this->index . ', \'' . $this->domElementId . '\')';
        }
    }

    public function setupJs(): void
    {
        global $toolbarDialogIndex;

        $data = get_object_vars($this);
        unset($data['list']);
        $data['editor']['isMarkdown'] = $this->isMarkdown;
        $data['editor']['isWysiwyg'] = $this->isWysiwyg;

        if ($this->isVueTool()) {
            TikiLib::lib('header')->add_js_module('
                import "@vue-mf/root-config";
                import "@vue-mf/toolbar-dialogs";
            ');

            // language=JavaScript
            TikiLib::lib('header')->add_jq_onready('
    window.registerApplication({
        name: "@vue-mf/toolbar-dialogs-" + ' . json_encode($this->index) . ',
        app: () => importShim("@vue-mf/toolbar-dialogs"),
        activeWhen: (location) => {
            let condition = true;
            return condition;
        },
        customProps: {
            toolbarObject: ' . json_encode($data) . ',
            syntax: ""
        },
    })
    onDOMElementRemoved("single-spa-application:@vue-mf/toolbar-dialogs-" + ' . json_encode($toolbarDialogIndex) . ', function () {
        window.unregisterApplication("@vue-mf/toolbar-dialogs-" + ' . json_encode($toolbarDialogIndex) . ');
    });
    ');
        } else {
            TikiLib::lib('header')->add_jsfile('lib/jquery_tiki/tiki-toolbars.js');
        }
    }

    public function getWikiHtml(): string
    {
        $headerlib = TikiLib::lib('header');
        $headerlib->add_js(
            "if (! window.dialogData) { window.dialogData = {}; } window.dialogData[$this->index] = "
            . json_encode($this->list) . ";",
            1 + $this->index
        );

        if ($this->isVueTool()) {
            return '<span id="single-spa-application:@vue-mf/toolbar-dialogs-' . $this->index . '" class="toolbar-dialogs"></span>';
        } else {
            return $this->getSelfLink(
                $this->getOnClick(),
                htmlentities($this->label, ENT_QUOTES, 'UTF-8'),
                $this->getClass()
            );
        }
    }

    public function getMarkdownHtml(): string
    {
        if ($this->markdown) {
            return $this->getWikiHtml();
        } else {
            return '';
        }
    }

    public function getWysiwygToken(): string
    {
        if (! empty($this->wysiwyg)) {
            TikiLib::lib('header')->add_js(
                "window.dialogData[$this->index] = " . json_encode($this->list) . ";",
                1 + $this->index
            );
            $onClick = str_replace('\'' . $this->domElementId . '\'', 'editor.name', $this->getOnClick());
            $this->setupCKEditorTool($onClick);
        }
        return $this->wysiwyg;
    }

    public function getWysiwygWikiToken(): string // wysiwyg_htmltowiki
    {
        switch ($this->name) {
            case 'tikilink':
                $this->wysiwyg = 'tikilink';
                break;
            case 'objectlink':
                $this->wysiwyg = 'objectlink';
                break;
            case 'table':
                $this->wysiwyg = 'tikitable';
                break;
            case 'link':
                $this->wysiwyg = 'externallink';
                break;
            default:
                return $this->wysiwyg;
        }

        return $this->getWysiwygToken();
    }

    public function getMarkdownWysiwyg(): string
    {
        if ($this->isVueTool()) {
            $html = $this->getWikiHtml();

            \TikiLib::lib('header')->add_jq_onready(
                "tuiToolbarItem$this->markdown_wysiwyg = $('$html').get(0);"
            );
            $item = [
                'name'    => $this->markdown_wysiwyg,
                'tooltip' => $this->label,
                'el'      => "%~tuiToolbarItem{$this->markdown_wysiwyg}~%",
            ];
            return json_encode($item);
        } elseif (in_array($this->name, ['tikilink'])) {
            \TikiLib::lib('header')->add_jq_onready(
                "tuiToolbarItem$this->markdown_wysiwyg = $.fn.getIcon('$this->iconname').click(function () {
                        {$this->getOnClick()}
                    }).get(0);"
            );
            TikiLib::lib('header')->add_js(
                "window.dialogData[$this->index] = " . json_encode($this->list) . ";",
                1 + $this->index
            );

            $item = [
                'name'    => $this->markdown_wysiwyg,
                'tooltip' => $this->label,
                'el'      => "%~tuiToolbarItem{$this->markdown_wysiwyg}~%",
            ];
            return json_encode($item);
        } elseif ($this->name === 'link') {
            return $this->name;
        } elseif ($this->name === 'table') {
            return $this->name;
        }
        return '';
    }

    /**
     * Tell if current dialog is a vue-toolbar-dialogs tool
     *
     * @return bool
     */
    protected function isVueTool(): bool
    {
        global $prefs;

        // not for ckeditor (yet)
        if (! $this->isMarkdown && $this->isWysiwyg) {
            return false;
        }

        $supported = ['tikilink', 'link'];

        if (! $this->isWysiwyg) {   // not working in toast yet TODO
            $supported[] = 'table';
            $supported[] = 'emoji';
        }

        return $prefs['vuejs_enable'] === 'y' &&
            $prefs['vuejs_toolbar_dialogs'] === 'y' &&
            in_array($this->name, $supported);
    }
}
