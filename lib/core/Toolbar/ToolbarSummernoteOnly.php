<?php

namespace Tiki\Lib\core\Toolbar;

class ToolbarSummernoteOnly extends ToolbarItem
{
    public function __construct($token, $iconName = '')
    {
        $this->setWysiwygToken($token)
            ->setType('SummernoteOnly')
            ->setIconName($iconName);
    }

    public static function fromName(string $name, bool $is_html, bool $is_markdown): ?ToolbarItem
    {
        global $prefs;

        if ($is_markdown) {
            return null;
        }

        switch ($name) {
            case 'source':
                global $tikilib, $user, $page;
                $p = $prefs['wysiwyg_htmltowiki'] == 'y' ? 'tiki_p_wiki_view_source' : 'tiki_p_use_HTML';
                if ($tikilib->user_has_perm_on_object($user, $page, 'wiki page', $p)) {
                    return new self('codeview');
                } else {
                    return null;
                }
            case 'sub':
                return new self('subscript', 'subscript');
            case 'sup':
                return new self('superscript', 'superscript');
        }
        return null;
    }

    public function getWikiHtml(): string
    {
        return '';
    }

    public function getWysiwygToken(): string
    {
        return $this->wysiwyg;
    }

    public function getOnClick(): string
    {
        return '';
    }
}
