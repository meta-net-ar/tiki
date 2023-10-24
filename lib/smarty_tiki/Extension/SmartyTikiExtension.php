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
    private $outputfilters = [];

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
            case 'trim':
                return [$this, 'smarty_modifier_trim'];
            case 'tr_if':
                return [$this, 'smarty_modifier_tr_if'];
            case 'username':
                return [$this, 'smarty_modifier_username'];
            case 'zone_is_empty':
                return [$this, 'smarty_modifier_zone_is_empty'];

        }
        return null;
    }

    public function getBlockHandler(string $blockTagName): ?BlockHandlerInterface
    {
        switch ($blockTagName) {
            case 'ajax_href':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\AjaxHref();
                break;
            case 'self_link':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\SelfLink();
                break;
            case 'remarksbox':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Remarksbox();
                break;
            case 'tr':
                $this->blockHandlers[$blockTagName] = new \SmartyTiki\BlockHandler\Tr();
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
            case 'breadcrumbs':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Breadcrumbs();
                break;
            case 'count':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Count();
                break;
            case 'feedback':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Feedback();
                break;
            case 'html_body_attributes':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\HtmlBodyAttributes();
                break;
            case 'icon':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Icon();
                break;
            case 'modulelist':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ModuleList();
                break;
            case 'object_title':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ObjectTitle();
                break;
            case 'query':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Query();
                break;
            case 'rating':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Rating();
                break;
            case 'service':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Service();
                break;
            case 'show_short':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ShowShort();
                break;
        }
        return $this->functionHandlers[$functionName] ?? null;
    }

    public function getOutputFilters(): array
    {
        if (isset($_REQUEST['highlight']) || (isset($prefs['feature_referer_highlight']) && $prefs['feature_referer_highlight'] == 'y')) {
            $this->outputfilters[] = new \SmartyTiki\Filter\Output\Highlight();
        }

        return $this->outputfilters;
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
    function smarty_modifier_escape($string, $esc_type = 'html', $char_set = 'UTF-8', $double_encode = true)
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
                if (!strlen($return) && strlen($string)) { // Bug php when there is non utf8 characters in the string(http://bugs.php.net/bug.php?id=43549, http://bugs.php.net/bug.php?id=43294)
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

     function smarty_modifier_stringfix($string, $what = '_', $by = ' ')
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
     * @param string $string - Required. Specifies the string to check
     * @param string $chars - Optional. Specifies which characters to remove from the string. If omitted, the following characters will be removed: " \t\n\r\0\x0B"
     * 
     * @return string - String trimed  
     */
    public function smarty_modifier_trim($string, $chars = null) {
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

    public function smarty_modifier_username($user, $login_fallback = true, $check_user_show_realnames = true, $html_encoding = true)
    {
        global $prefs;
        $userlib = \TikiLib::lib('user');

        $return = $userlib->clean_user($user, !$check_user_show_realnames, $login_fallback);

        if ($html_encoding) {
            $return = htmlspecialchars($return);
        }
        return $return;
    }

    public function smarty_modifier_zone_is_empty($zoneName)
    {
        return zone_is_empty($zoneName);
    }
}
