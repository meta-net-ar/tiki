<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Extension;

use Smarty\BlockHandler\BlockHandlerInterface;
use Smarty\FunctionHandler\FunctionHandlerInterface;

class SmartyTikiExtension extends \Smarty\Extension\Base
{
    private $blockHandlers = [];
    private $functionHandlers = [];
    private $outputFilters = [];
    private $preFilters = [];

    public function getModifierCallback(string $modifierName)
    {
        switch ($modifierName) {
            case 'a_or_an':
                return [$this, 'smarty_modifier_a_or_an'];
            case 'adjust':
                return [$this, 'smarty_modifier_adjust'];
            case 'count':
                return [$this, 'smarty_modifier_count'];
            case 'escape':
                return [$this, 'smarty_modifier_escape'];
            case 'sefurl':
                return [$this, 'smarty_modifier_sefurl'];
            case 'stringfix':
                return [$this, 'smarty_modifier_stringfix'];
            case 'ternary':
                return [$this, 'smarty_modifier_ternary'];
            case 'truncate':
                return [$this, 'smarty_modifier_truncate'];
            case 'trim':
                return [$this, 'smarty_modifier_trim'];
            case 'tr_if':
                return [$this, 'smarty_modifier_tr_if'];
            case 'userlink':
                return [$this, 'smarty_modifier_userlink'];
            case 'username':
                return [$this, 'smarty_modifier_username'];
            case 'virtual_path':
                return [$this, 'smarty_modifier_virtual_path'];
            case 'zone_is_empty':
                return [$this, 'smarty_modifier_zone_is_empty'];
        }
        return null;
    }

    public function getBlockHandler(string $blockTagName): ?BlockHandlerInterface
    {
        switch ($blockTagName) {
            case 'accordion_group':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\AccordionGroup();
                break;
            case 'accordion':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Accordion();
                break;
            case 'actions':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Actions();
                break;
            case 'activityframe':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\ActivityFrame();
                break;
            case 'ajax_href':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\AjaxHref();
                break;
            case 'compact':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Compact();
                break;
            case 'display':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Display();
                break;
            case 'filter':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Filter();
                break;
            case 'ifsearchexists':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\IfSearchExists();
                break;
            case 'ifsearchnotexists':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\IfSearchNotExists();
                break;
            case 'itemfield':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\ItemField();
                break;
            case 'jq':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Jq();
                break;
            case 'mailurl':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\MailUrl();
                break;
            case 'modules_list':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\ModulesList();
                break;
            case 'packageplugin':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\PackagePlugin();
                break;
            case 'pagination_links':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\PaginationLinks();
                break;
            case 'permission':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Permission();
                break;
            case 'popup_link':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\PopupLink();
                break;
            case 'repeat':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Repeat();
                break;
            case 'sortlinks':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\SortLinks();
                break;
            case 'self_link':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\SelfLink();
                break;
            case 'remarksbox':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Remarksbox();
                break;
            case 'tab':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Tab();
                break;
            case 'tabset':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Tabset();
                break;
            case 'textarea':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\TextArea();
                break;
            case 'tikimodule':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\TikiModule();
                break;
            case 'title':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Title();
                break;
            case 'tr':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Tr();
                break;
            case 'trackeritemcheck':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\TrackerItemCheck();
                break;
            case 'translation':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Translation();
                break;
            case 'vue':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Vue();
                break;
            case 'wiki':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Wiki();
                break;
            case 'wikiplugin':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Wikiplugin();
                break;
        }

        return $this->blockHandlers[$blockTagName] ?? null;
    }

    public function getFunctionHandler(string $functionName): ?FunctionHandlerInterface
    {
        if (isset($this->functionHandlers[$functionName])) {
            return $this->functionHandlers[$functionName];
        }

        switch ($functionName) {
            case 'activity':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Activity();
                break;
            case 'article':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Article();
                break;
            case 'attachments':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Attachments();
                break;
            case 'autocomplete':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Autocomplete();
                break;
            case 'banner':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Banner();
                break;
            case 'breadcrumbs':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Breadcrumbs();
                break;
            case 'bootstrap_modal':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\BootstrapModal();
                break;
            case 'button':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Button();
                break;
            case 'categoryName':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\CategoryName();
                break;
            case 'categoryselector':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\CategorySelector();
                break;
            case 'content':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Content();
                break;
            case 'cookie_jar':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\CookieJar();
                break;
            case 'cookie':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Cookie();
                break;
            case 'count':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Count();
                break;
            case 'currency':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Currency();
                break;
            case 'custom_template':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\CustomTemplate();
                break;
            case 'datetime_range':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\DatetimeRange();
                break;
            case 'debugger':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Debugger();
                break;
            case 'defaultmapcenter':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\DefaultMapCenter();
                break;
            case 'ed':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Ed();
                break;
            case 'elapsed':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Elapsed();
                break;
            case 'favorite':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Favorite();
                break;
            case 'feedback':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Feedback();
                break;
            case 'fgal_browse':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\FgalBrowse();
                break;
            case 'file_selector':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\FileSelector();
                break;
            case 'filegal_manager_url':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\FileGalManagerUrl();
                break;
            case 'filegal_uploader':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\FileGalUploader();
                break;
            case 'fileinfo':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\FileInfo();
                break;
            case 'formitem':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\FormItem();
                break;
            case 'help':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Help();
                break;
            case 'html_body_attributes':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\HtmlBodyAttributes();
                break;
            case 'html_select_date':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\HtmlSelectDate();
                break;
            case 'html_select_duration':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\HtmlSelectDuration();
                break;
            case 'html_select_time':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\HtmlSelectTime();
                break;
            case 'icon':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Icon();
                break;
            case 'initials_filter_links':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\InitialsFilterLinks();
                break;
            case 'interactivetranslation':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\InteractiveTranslation();
                break;
            case 'js_insert_icon':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\JsInsertIcon();
                break;
            case 'js_maxlength':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\JsMaxLength();
                break;
            case 'jscalendar':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\JsCalendar();
                break;
            case 'jspopup':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\JsPopup();
                break;
            case 'like':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Like();
                break;
            case 'listfilter':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ListFilter();
                break;
            case 'lock':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Lock();
                break;
            case 'memusage':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\MemUsage();
                break;
            case 'menu':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Menu();
                break;
            case 'module':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Module();
                break;
            case 'modulelist':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ModuleList();
                break;
            case 'monitor_link':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\MonitorLink();
                break;
            case 'multilike':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\MultiLike();
                break;
            case 'norecords':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\NoRecords();
                break;
            case 'notification_link':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\NotificationLink();
                break;
            case 'obj_in_cat':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ObjInCat();
                break;
            case 'object_title':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ObjectTitle();
                break;
            case 'object_link':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ObjectLink();
                break;
            case 'object_score':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ObjectScore();
                break;
            case 'object_selector_multi':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ObjectSelectorMulti();
                break;
            case 'object_selector':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ObjectSelector();
                break;
            case 'object_type':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ObjectType();
                break;
            case 'page_alias':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\PageAlias();
                break;
            case 'page_in_structure':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\PageInStructure();
                break;
            case 'payment':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Payment();
                break;
            case 'permission_link':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\PermissionLink();
                break;
            case 'pluralize':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Pluralize();
                break;
            case 'poll':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Poll();
                break;
            case 'popup':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Popup();
                break;
            case 'preference':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Preference();
                break;
            case 'profilesymbolvalue':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ProfileSymbolValue();
                break;
            case 'query':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Query();
                break;
            case 'quotabar':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Quotabar();
                break;
            case 'rating':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Rating();
                break;
            case 'rating_choice':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\RatingChoice();
                break;
            case 'rating_override_menu':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\RatingOverrideMenu();
                break;
            case 'rating_result_avg':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\RatingResultAvg();
                break;
            case 'sefurl':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Sefurl();
                break;
            case 'service':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Service();
                break;
            case 'show_short':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ShowShort();
                break;
            case 'ticket':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Ticket();
                break;
            case 'tracker_item_status_icon':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\TrackerItemStatusIcon();
                break;
        }
        return $this->functionHandlers[$functionName] ?? null;
    }

    public function getOutputFilters(): array
    {
        if (isset($_REQUEST['highlight']) || (isset($prefs['feature_referer_highlight']) && $prefs['feature_referer_highlight'] == 'y')) {
            $this->outputFilters[] = new \SmartyTiki\Filter\Output\Highlight();
        }

        return $this->outputFilters;
    }

    public function getPreFilters(): array
    {
        global $prefs;

        $this->preFilters = [
            new \SmartyTiki\Filter\Pre\Tr(),
            new \SmartyTiki\Filter\Pre\Jq()
        ];

        if (! empty($prefs['log_tpl']) && $prefs['log_tpl'] === 'y') {
            $this->preFilters[] = new \SmartyTiki\Filter\Pre\LogTpl();
        }

        return $this->preFilters;
    }

    /*
    * Smarty plugin
    * -------------------------------------------------------------
    * Type:     modifier
    * Prepends an "a " or "an " depending on whether word starts with vowel.
    * @param caps, if set will cause "A " or "An "
    * -------------------------------------------------------------
    */
    public function smarty_modifier_a_or_an($string, $caps = false)
    {
        global $prefs;
        if (substr($prefs['language'], 0, 2) != 'en') {
            return $string;
        }
        $vowels = ['a', 'e', 'i', 'o', 'u'];
        $initial = strtolower(substr($string, 0, 1));
        if (in_array($initial, $vowels)) {
            $prefix = $caps ? 'An ' : 'an ';
        } else {
            $prefix = $caps ? 'A ' : 'a ';
        }
        return $prefix . $string;
    }

    /*
    * Smarty plugin
    * -------------------------------------------------------------
    * Type:     modifier
    * Name:     adjust
    * Purpose:  Adjust a string to a certain length if necessary,
    *           optionally splitting in the middle of a word, and
    *           appending the $etc string or padding the string
    *             using $pad as filler.
    * -------------------------------------------------------------
    */
    public function smarty_modifier_adjust(
        $string,
        $length = 80,
        $pad = '&nbsp;',
        $etc = '...',
        $break_words = false
    ) {

        if ($length == 0) {
            return '';
        }

        if (strlen($string) > $length) {
            $length -= strlen($etc);
            $fragment = substr($string, 0, $length + 1);
            if ($break_words) {
                $fragment = substr($fragment, 0, -1);
            } else {
                $fragment = preg_replace('/\s+(\S+)?$/', '', $fragment);
            }
            return $fragment . $etc;
        } elseif (strlen($string) < $length) {
            return $string . str_repeat($pad, $length - strlen($string));
        } else {
            return $string;
        }
    }

    public function smarty_modifier_count($arrayOrObject, $mode = 0)
    {
        if ($arrayOrObject instanceof \Countable || is_array($arrayOrObject)) {
            return count($arrayOrObject, (int) $mode);
        } elseif ($arrayOrObject === null) {
            return 0;
        }
        return 1;
    }

    /**
     * Smarty escape modifier
     *
     * Type:     modifier<br>
     * Name:     escape<br>
     * Purpose:  Escape the string according to escapement type
     * @param html|htmlall|url|quotes|hex|hexentity|javascript
     * @param string $esc_type
     * @param string $char_set
     * @param bool $double_encode
     * @return string
     */
    public function smarty_modifier_escape($string, $esc_type = 'html', $char_set = 'UTF-8', $double_encode = true)
    {
        switch ($esc_type) {
            case 'html':
                if (is_array($string)) {
                    $string = implode(',', $string);
                }
                $return = htmlspecialchars($string ?? '', ENT_QUOTES, $char_set, $double_encode);

                // Convert back sanitization tags into real tags to avoid them to be displayed
                $return = str_replace('&lt;x&gt;', '<x>', $return);
                // Convert back sanitization tags into real tags for no wrap space
                $return = str_replace('&amp;nbsp;', '&nbsp;', $return);

                return $return;

            case 'htmlall':
                $return = htmlentities($string ?? '', ENT_QUOTES, $char_set);
                if (! strlen($return) && strlen($string)) { // Bug php when there is non utf8 characters in the string(http://bugs.php.net/bug.php?id=43549, http://bugs.php.net/bug.php?id=43294)
                    $return = htmlentities($string, ENT_QUOTES);
                }

                // Convert back sanitization tags into real tags to avoid them to be displayed
                $return = str_replace('&lt;x&gt;', '<x>', $return);

                return $return;

            case 'url':
                return rawurlencode($string ?? '');

            case 'urlpathinfo':
                return str_replace('%2F', '/', rawurlencode($string));

            case 'quotes':
                // escape unescaped single quotes
                return preg_replace("%(?<!\\\\)'%", "\\'", $string);

            case 'hex':
                // escape every character into hex
                $return = '';
                for ($x = 0, $xstrlen_string = strlen($string); $x < $xstrlen_string; $x++) {
                    $return .= '%' . bin2hex($string[$x]);
                }
                return $return;

            case 'hexentity':
                $return = '';
                for ($x = 0, $x_strlen_string = strlen($string); $x < $x_strlen_string; $x++) {
                    $return .= '&#x' . bin2hex($string[$x]) . ';';
                }
                return $return;

            case 'decentity':
                $return = '';
                for ($x = 0, $x_strlen_string = strlen($string); $x < $x_strlen_string; $x++) {
                    $return .= '&#' . ord($string[$x]) . ';';
                }
                return $return;

            case 'javascript':
                // escape quotes and backslashes, newlines, etc.
                return strtr($string, ['\\' => '\\\\', "'" => "\\'", '"' => '\\"', "\r" => '\\r', "\n" => '\\n', '</' => '<\/']);

            case 'mail':
                // safe way to display e-mail address on a web page
                return str_replace(['@', '.'], [' [AT] ', ' [DOT] '], $string);

            case 'nonstd':
                // escape non-standard chars, such as ms document quotes
                $_res = '';
                for ($_i = 0, $_len = strlen($string); $_i < $_len; $_i++) {
                    $_ord = ord(substr($string, $_i, 1));
                    // non-standard char, escape it
                    if ($_ord >= 126) {
                        $_res .= '&#' . $_ord . ';';
                    } else {
                        $_res .= substr($string, $_i, 1);
                    }
                }
                return $_res;

            case 'unescape':
                return rawurldecode($string);

            case 'attr':
                $esc = new \Laminas\Escaper\Escaper();
                return $esc->escapeHtmlAttr((string) $string);

            default:
                return $string;
        }
    }

    public function smarty_modifier_sefurl($source, $type = 'wiki', $with_next = '', $all_langs = '', $with_title = 'y', $title = '')
    {
        global $prefs;
        $wikilib = \TikiLib::lib('wiki');
        $tikilib = \TikiLib::lib('tiki');
        $smarty = \TikiLib::lib('smarty');

        $sefurl = $prefs['feature_sefurl'] == 'y';

        if (isset($type)) {
            switch ($type) {
                case 'wiki page':
                case 'wikipage':
                    $type = 'wiki';
                    break;
                case 'post':
                case 'blog post':
                    $type = 'blogpost';
                    break;
            }
        }

        $urlAnchor = '';
        if (isset($type) && substr($type, -7) == 'comment') {
            $urlAnchor = '#threadId=' . (int)$source;
            $type = substr($type, 0, strlen($type) - 8);
            $info = \TikiLib::lib('comments')->get_comment((int)$source);
            $source = $info['object'] ?? $source;
        }

        switch ($type) {
            case 'wiki':
                return \TikiLib::tikiUrlOpt($wikilib->sefurl($source, $with_next, $all_langs));

            case 'blog':
                $href = $sefurl ? "blog$source" : "tiki-view_blog.php?blogId=$source";
                break;

            case 'blog post':
            case 'blogpost':
                $href = $sefurl ? "blogpost$source" : "tiki-view_blog_post.php?postId=$source";
                break;
            case 'calendar':
                $href = $sefurl ? "cal$source" : "tiki-calendar.php?calIds[]=$source";
                break;

            case 'calendaritem':
                $href = "tiki-ajax_services.php?controller=calendar&action=view_item&calitemId=$source";
                break;

            case 'calendar event':
                $href = $sefurl ? "calevent$source" : "tiki-ajax_services.php?controller=calendar&action=view_item&calitemId=$source";
                break;

            case 'article':
                $href = $sefurl ? "article$source" : "tiki-read_article.php?articleId=$source";
                break;

            case 'topic':
                $href = "tiki-view_articles.php?topic=$source";
                break;

            case 'file':
            case 'thumbnail':
            case 'display':
            case 'preview':
                $attributelib = \TikiLib::lib('attribute');
                $attributes = $attributelib->get_attributes('file', $source);

                if ($type == 'file') {
                    $prefix = 'dl';
                    $suffix = null;
                } else {
                    $prefix = $type;
                    $suffix = '&amp;' . $type;
                }

                if (isset($attributes['tiki.content.url'])) {
                    $href = $attributes['tiki.content.url'];
                } else {
                    $href = $sefurl ? "$prefix$source" : "tiki-download_file.php?fileId=$source$suffix";
                }

                break;

            case 'draft':
                $href = 'tiki-download_file.php?fileId=' . $source . '&amp;draft';
                break;

            case 'trackeritemfield':
                $source = (int) explode(':', $source)[0];
                // fall through to trackeritem handling intentionally
            case 'tracker item':
            case 'trackeritem':
                $type = 'trackeritem';
                $replacementpage = '';
                if ($prefs["feature_sefurl_tracker_prefixalias"] == 'y' && $prefs['tracker_prefixalias_on_links'] == 'y') {
                    $trklib = \TikiLib::lib('trk');
                    $replacementpage = $trklib->get_trackeritem_pagealias($source);
                }
                if ($replacementpage) {
                    $href = \TikiLib::tikiUrlOpt($wikilib->sefurl($replacementpage, $with_next, $all_langs));
                    if ($prefs['feature_sefurl_title_trackeritem'] == 'y') {
                        $title = $trklib->get_title_sefurl($source);
                    }
                } else {
                    if ($prefs['pwa_feature'] == 'y') {
                        $trklib = \TikiLib::lib('trk');
                        $item = $trklib->get_item_info($source);
                        $href = 'tiki-ajax_services.php?controller=tracker&action=update_item&trackerId=' . $item['trackerId'] . '&itemId=' . $source;
                    } else {
                        $href = 'tiki-view_tracker_item.php?itemId=' . $source;
                    }
                }
                break;

            case 'tracker':
                if ($source) {
                    $href = 'tiki-view_tracker.php?trackerId=' . $source;
                } else {
                    $href = 'tiki-list_trackers.php';
                }
                break;

            case 'trackerfield':
                $trackerId = \TikiLib::lib('trk')->get_field_info((int)$source)['trackerId'];
                $href = 'tiki-admin_tracker_fields.php?trackerId=' . $trackerId;
                break;
            case 'trackerfields':
                $href = 'tiki-admin_tracker_fields.php?trackerId=' . $source;
                break;
            case 'filegallery':
            case 'file gallery':
                $type = 'file gallery';
                $href = 'tiki-list_file_gallery.php?galleryId=' . $source;
                break;

            case 'forum':
                $href = $sefurl ? "forum$source" : 'tiki-view_forum.php?forumId=' . $source;
                break;

            case 'forumthread':
            case 'forum post':  // used in unified search getSupportedTypes()
                $href = $sefurl ? "forumthread$source" : 'tiki-view_forum_thread.php?comments_parentId=' . $source;
                break;

            case 'image':
                $href = 'tiki-browse_image.php?imageId=' . $source;
                break;

            case 'sheet':
                $href = $sefurl ? "sheet$source" : "tiki-view_sheets.php?sheetId=$source";
                break;

            case 'category':
                $href = $sefurl ? "cat$source" : "tiki-browse_categories.php?parentId=$source";
                break;

            case 'freetag':
                $href = "tiki-browse_freetags.php?tag=" . urlencode($source);
                break;

            case 'newsletter':
                $href = "tiki-newsletters.php?nlId=" . urlencode($source);
                break;

            case 'survey':
                $href = "tiki-take_survey.php?surveyId=" . urlencode($source);
                break;

            default:
                $href = $source;
                break;
        }

        if ($with_next && ($with_title != 'y' || $prefs['feature_sefurl'] !== 'y')) {
            $href .= '&amp;';
        }

        if ($prefs['feature_sefurl'] == 'y' && $smarty) {
            include_once('tiki-sefurl.php');
            return \TikiLib::tikiUrlOpt(filter_out_sefurl($href, $type, $title, $with_next, $with_title)) . $urlAnchor;
        } else {
            return \TikiLib::tikiUrlOpt($href) . $urlAnchor;
        }
    }

    /**
     * \brief Smarty modifier to replace strings
     *
     * - type:     modifier
     * - name:     stringfix
     * - purpose:  to return a "bugged" string which needs to be fixed
     *
     * Simple function to return a string with replaced string(s), e.g. to get correct translation strings for country names taken from flag filenames
     *
     * @param string to be replaced (optional)
     * @param replaced by string (optional)
     * @return corrected string
     *
     * Syntax: {$foo|stringfix[:"<fix_what>"][:"<fix_by>"]} (optional params in brackets)
     *
     * Example: {$country|stringfix:"_":" "}
     */

    public function smarty_modifier_stringfix($string, $what = '_', $by = ' ')
    {
        return str_replace($what, $by, $string);
    }

    /*
    * Smarty plugin
    * -------------------------------------------------------------
    * Type:     modifier
    * Name:     ternary
    * Purpose:  map true and false to first and second parameter respectively
    * -------------------------------------------------------------
    */
    public function smarty_modifier_ternary($input, $true = '', $false = '')
    {
        return $input ? $true : $false;
    }

    /**
     * Smarty truncate modifier
     *
     * Type:     modifier<br>
     * Name:     truncate<br>
     * Purpose:  Truncate a string to a certain length if necessary,
     *           optionally splitting in the middle of a word, and
     *           appending the $etc string or inserting $etc into the middle.
     * @param string
     * @param integer
     * @param string
     * @param boolean
     * @param boolean
     * @return string
     */
    public function smarty_modifier_truncate($string, $length = 80, $etc = '...', $break_words = false, $middle = false)
    {
        if (! isset($string) || $length == 0) {
            return '';
        }

        $strlength = (function_exists('mb_strlen') ? 'mb_strlen' : 'strlen');
        if ($strlength($string) > $length) {
            $length -= min($length, strlen($etc));
            if (function_exists('mb_substr')) {
                $func = 'mb_substr';
            } else {
                $func = 'substr';
            }
            if (! $break_words && ! $middle) {
                $string = preg_replace('/\s+?(\S+)?$/', '', $func($string, 0, $length + 1));
            }
            if (! $middle) {
                return $func($string, 0, $length) . $etc;
            } else {
                return $func($string, 0, $length / 2) . $etc . $func($string, -$length / 2);
            }
        } else {
            return $string;
        }
    }

    /**
     * @param string $string - Required. Specifies the string to check
     * @param string $chars - Optional. Specifies which characters to remove from the string. If omitted, the following characters will be removed: " \t\n\r\0\x0B"
     *
     * @return string - String trimed
     */
    public function smarty_modifier_trim($string, $chars = null)
    {
        return empty($chars) ? trim($string) : trim($string, $chars);
    }

    public function smarty_modifier_tr_if($source)
    {
        global $prefs;
        $args = array_slice(func_get_args(), 1);

        if ($prefs['language'] != 'en') {
            include_once('lib/init/tra.php');
            return tra($source, '', false, $args);
        } else {
            $replace = array_values($args);
            $search = array_map(
                function ($arg) {
                    return '%' . $arg;
                },
                array_keys($args)
            );
            return str_replace($search, $replace, $source);
        }
    }

    /**
     * \brief Smarty modifier to create user links with optional mouseover info
     *
     * - type:     modifier
     * - name:     userlink
     * - purpose:  to return a user link
     *
     * @param string class (optional)
     * @param string idletime (optional)
     * @param string fullname (optional)
     * @param integer max_length (optional)
     * @return string user link
     *
     * Syntax: {$foo|userlink[:"<class>"][:"<idletime>"][:"<fullname>"][:<max_length>]} (optional params in brackets)
     *
     * Example: {$userinfo.login|userlink:'link':::25}
     */
    public function smarty_modifier_userlink($other_user, $class = 'userlink', $idletime = 'not_set', $fullname = '', $max_length = 0, $popup = '')
    {
        global $prefs;

        if (empty($other_user)) {
            return "";
        }
        if (is_array($other_user)) {
            if (count($other_user) > 1) {
                $other_user = array_map(
                    function ($username) use ($class, $idletime, $popup) {
                        $username = \TikiLib::lib('user')->distinguish_anonymous_users($username);
                        return $this->smarty_modifier_userlink($username, $class, $idletime, '', 0, $popup);
                    },
                    $other_user
                );

                $last = array_pop($other_user);
                return tr('%0 and %1', implode(', ', $other_user), $last);
            } else {
                $other_user = \TikiLib::lib('user')->distinguish_anonymous_users(reset($other_user));
            }
        } else {
            $other_user = \TikiLib::lib('user')->distinguish_anonymous_users($other_user);
        }
        if (! $fullname) {
            $fullname = \TikiLib::lib('user')->clean_user($other_user);
        }
        if ($max_length) {
            $fullname = $this->smarty_modifier_truncate($fullname, $max_length, '...', true);
        }

        if (empty($popup) && $prefs['feature_community_mouseover'] == 'n') {
            $popup = 'n';
        } else {
            $popup = 'y';
        }

        return \TikiLib::lib('user')->build_userinfo_tag($other_user, htmlspecialchars($fullname, ENT_QUOTES), $class, $popup);
    }

    public function smarty_modifier_username($user, $login_fallback = true, $check_user_show_realnames = true, $html_encoding = true)
    {
        global $prefs;
        $userlib = \TikiLib::lib('user');

        $return = $userlib->clean_user($user, ! $check_user_show_realnames, $login_fallback);

        if ($html_encoding) {
            $return = htmlspecialchars($return);
        }
        return $return;
    }

    public function smarty_modifier_virtual_path($fileOrPageId, $type = 'file')
    {
        global $prefs, $base_url;

        $filegallib = \TikiLib::lib('filegal');

        if ($type == 'wiki page') {
            return $base_url . 'tiki-webdav.php/Wiki Pages/' . $fileOrPageId;
        } else {
            return $base_url . 'tiki-webdav.php' . ($filegallib->get_full_virtual_path($fileOrPageId, $type));
        }
    }

    public function smarty_modifier_zone_is_empty($zoneName)
    {
        return zone_is_empty($zoneName);
    }
}
