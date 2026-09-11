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
    protected string $singleSpaAppName;
    protected string $singleSpaDomId;

    public static function fromName(string $tagName, bool $is_wysiwyg = false, bool $is_html = false, bool $is_markdown = false, string $domElementId = ''): ?ToolbarItem
    {
        $tool_prefs = [];
        $markdown_wysiwyg = '';
        $markdown = '';

        switch ($tagName) {
            case 'tikilink':
                $label = tra('Wiki Link');
                $iconname = 'link';
                $icon = tra('img/icons/page_link.png');
                $wysiwyg = 'tikilink';
                $markdown = 'tikilink';
                $markdown_wysiwyg = 'tikilink';
                ;

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

                break;
            case 'link':
                $wysiwyg = 'link';
                $label = tra('External Link');
                $iconname = 'external-link-alt';    // for isDialogSupported but will work if not too
                $icon = tra('img/icons/world_link.png');
                $markdown = 'link';
                $markdown_wysiwyg = 'link';
                break;

            case 'table':
            case 'tikitable':
                $iconname = 'table';
                $icon = tra('img/icons/table.png');
                $wysiwyg = 'table';
                $markdown = 'table';
                $markdown_wysiwyg = 'table';
                $label = tra('Table Builder');
                break;

            case 'replace':
                $icon = tra('img/icons/text_replace.png');
                $iconname = 'exchange';
                $wysiwyg = 'replace';
                $markdown = ''; // TODO
                $label = tra('Text Replace');
                $tool_prefs[] = 'feature_wiki_replace';

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
            ->setType('Dialog')
            ->setClass('qt-picker')
            ->setDomElementId($domElementId);

        foreach ($tool_prefs as $pref) {
            $tag->addRequiredPreference($pref);
        }

        global $toolbarDialogIndex;
        ++$toolbarDialogIndex;
        $tag->index = $toolbarDialogIndex;
        $tag->singleSpaAppName = "@vue-mf/toolbar-dialogs-" . \Tiki\Utilities\Identifiers::getHttpRequestId() . '_' . $tag->index;
        $tag->singleSpaDomId = "single-spa-application:{$tag->singleSpaAppName}";
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
        return '';
    }

    public function setupJs(): void
    {
        $data = get_object_vars($this);
        unset($data['list']);
        $data['editor']['isMarkdown'] = $this->isMarkdown;
        $data['editor']['isWysiwyg'] = $this->isWysiwyg;

        if ($this->isDialogSupported()) {
            TikiLib::lib('header')->add_js_module('
                import "@vue-mf/root-config";
                import "@vue-mf/toolbar-dialogs";
            ');

            // language=JavaScript
            TikiLib::lib('header')->add_jq_onready('
    window.registerApplication({
        name: "' . $this->singleSpaAppName . '",
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
    onDOMElementRemoved("' . $this->singleSpaDomId . '", function () {
        window.unregisterApplication("' . $this->singleSpaAppName . '");
    });
    ');
        }
    }

    public function getWikiHtml(): string
    {
        return '<span id="' . $this->singleSpaDomId . '" class="toolbar-dialogs"></span>';
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
        if ($this->isDialogSupported()) {
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
    protected function isDialogSupported(): bool
    {
        $supported = ['tikilink', 'link'];

        if (! $this->isWysiwyg) {   // not working in toast yet TODO
            $supported[] = 'table';
            $supported[] = 'emoji';
            $supported[] = 'find';
            $supported[] = 'replace';
        }

        return in_array($this->name, $supported);
    }
}
