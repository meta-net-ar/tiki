<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Extension;

use Smarty\BlockHandler\BlockHandlerInterface;
use Smarty\FunctionHandler\FunctionHandlerInterface;
use Smarty\Compile\CompilerInterface;
use TikiLib;

class SmartyTikiExtension extends \Smarty\Extension\Base
{
    private $blockHandlers = [];
    private $functionHandlers = [];
    private $outputFilters = [];
    private $preFilters = [];
    private $tags = [];

    public function getTagCompiler(string $tag): ?CompilerInterface
    {
        if (isset($this->tags[$tag])) {
            return $this->tags[$tag];
        }
        
        switch ($tag) {
            case 'assign_content':
                $this->tags[$tag] = new \SmartyTiki\Compile\Tag\AssignContent();
                break;
        }

        return $this->tags[$tag] ?? null;
    }

    public function getModifierCallback(string $modifierName)
    {
        switch ($modifierName) {
            case 'a_or_an':
                return [$this, 'smarty_modifier_a_or_an'];
            case 'addslashes':
                return [$this, 'smarty_modifier_addslashes'];
            case 'adjust':
                return [$this, 'smarty_modifier_adjust'];
            case 'array_reverse':
                return [$this, 'smarty_modifier_array_reverse'];
            case 'avatarize':
                return [$this, 'smarty_modifier_avatarize'];
            case 'breakline':
                return [$this, 'smarty_modifier_breakline'];
            case 'categid':
                return [$this, 'smarty_modifier_categid'];
            case 'compactisodate':
                return [$this, 'smarty_modifier_compactisodate'];
            case 'contryflag':
                return [$this, 'smarty_modifier_contryflag'];
            case 'count':
                return [$this, 'smarty_modifier_count'];
            case 'd':
                return [$this, 'smarty_modifier_d'];
            case 'dbg':
                return [$this, 'smarty_modifier_dbg'];
            case 'div':
                return [$this, 'smarty_modifier_div'];
            case 'duration_short':
                return [$this, 'smarty_modifier_duration_short'];
            case 'duration':
                return [$this, 'smarty_modifier_duration'];
            case 'escape':
                return [$this, 'smarty_modifier_escape'];
            case 'file_can_convert_to_pdf':
                return [$this, 'smarty_modifier_file_can_convert_to_pdf'];
            case 'file_diagram':
                return [$this, 'smarty_modifier_file_diagram'];
            case 'forumname':
                return [$this, 'smarty_modifier_forumname'];
            case 'forumtopiccount':
                return [$this, 'smarty_modifier_forumtopiccount'];
            case 'groupmembercount':
                return [$this, 'smarty_modifier_groupmembercount'];
            case 'how_many_user_inscriptions':
                return [$this, 'smarty_modifier_how_many_user_inscriptions'];
            case 'htmldecode':
                return [$this, 'smarty_modifier_htmldecode'];
            case 'iconify':
                return [$this, 'smarty_modifier_iconify'];
            case 'implode':
                return [$this, 'smarty_modifier_implode'];
            case 'in_group':
                return [$this, 'smarty_modifier_in_group'];
            case 'is_array':
                return [$this, 'smarty_modifier_is_array'];
            case 'isodate':
                return [$this, 'smarty_modifier_isodate'];
            case 'json_decode':
                return [$this, 'smarty_modifier_json_decode'];
            case 'json_encode':
                return [$this, 'smarty_modifier_json_encode'];
            case 'kbsize':
                return [$this, 'smarty_modifier_kbsize'];
            case 'langname':
                return [$this, 'smarty_modifier_langname'];
            case 'lcfirst':
                return [$this, 'smarty_modifier_lcfirst'];
            case 'max_user_inscriptions':
                return [$this, 'smarty_modifier_max_user_inscriptions'];
            case 'md5':
                return [$this, 'smarty_modifier_md5'];
            case 'money_format':
                return [$this, 'smarty_modifier_money_format'];
            case 'namespace':
                return [$this, 'smarty_modifier_namspace'];
            case 'nonamespace':
                return [$this, 'smarty_modifier_nonamspace'];
            case 'nonp':
                return [$this, 'smarty_modifier_nonp'];
            case 'number_format':
                return [$this, 'smarty_modifier_number_format'];
            case 'numStyle':
                return [$this, 'smarty_modifier_numStyle'];
            case 'output':
                return [$this, 'smarty_modifier_output'];
            case 'packageitemid':
                return [$this, 'smarty_modifier_packageitemid'];
            case 'pagname':
                return [$this, 'smarty_modifier_pagename'];
            case 'parse':
                return [$this, 'smarty_modifier_parse'];
            case 'percent':
                return [$this, 'smarty_modifier_percent'];
            case 'preg_match':
                return [$this, 'smarty_modifier_preg_match'];
            case 'quoted':
                return [$this, 'smarty_modifier_quoted'];
            case 'replacei':
                return [$this, 'smarty_modifier_replacei'];
            case 'reversre_array':
                return [$this, 'smarty_modifier_reversre_array'];
            case 'sefurl':
                return [$this, 'smarty_modifier_sefurl'];
            case 'slug':
                return [$this, 'smarty_modifier_slug'];
            case 'star':
                return [$this, 'smarty_modifier_star'];
            case 'stringfix':
                return [$this, 'smarty_modifier_stringfix'];
            case 'stristr':
                return [$this, 'smarty_modifier_stristr'];
            case 'strpos':
                return [$this, 'smarty_modifier_strpos'];
            case 'substring':
                return [$this, 'smarty_modifier_substring'];
            case 'tasklink':
                return [$this, 'smarty_modifier_tasklink'];
            case 'template':
                return [$this, 'smarty_modifier_template'];
            case 'ternary':
                return [$this, 'smarty_modifier_ternary'];
            case 'tiki_date_format':
                return [$this, 'smarty_modifier_tiki_date_format'];
            case 'tiki_long_date':
                return [$this, 'smarty_modifier_tiki_long_date'];
            case 'tiki_long_datetime':
                return [$this, 'smarty_modifier_tiki_long_datetime'];
            case 'tiki_long_time':
                return [$this, 'smarty_modifier_tiki_long_time'];
            case 'tiki_remaining_days_from_now':
                return [$this, 'smarty_modifier_tiki_remaining_days_from_now'];
            case 'tiki_short_date':
                return [$this, 'smarty_modifier_tiki_short_date'];
            case 'tiki_short_datetime':
                return [$this, 'smarty_modifier_tiki_short_datetime'];
            case 'tiki_short_time':
                return [$this, 'smarty_modifier_tiki_short_time'];
            case 'times':
                return [$this, 'smarty_modifier_times'];
            case 'trim':
                return [$this, 'smarty_modifier_trim'];
            case 'tra':
                return [$this, 'smarty_modifier_tra'];
            case 'truncate':
                return [$this, 'smarty_modifier_truncate'];
            case 'truex':
                return [$this, 'smarty_modifier_truex'];
            case 'tr_if':
                return [$this, 'smarty_modifier_tr_if'];
            case 'ucfirst':
                return [$this, 'smarty_modifier_ucfirst'];
            case 'ucwords':
                return [$this, 'smarty_modifier_ucwords'];
            case 'urlencode':
                return [$this, 'smarty_modifier_urlencode'];
            case 'userlink':
                return [$this, 'smarty_modifier_userlink'];
            case 'username':
                return [$this, 'smarty_modifier_username'];
            case 'utf8unicode':
                return [$this, 'smarty_modifier_utf8unicode'];
            case 'var_dump':
                return [$this, 'smarty_modifier_var_dump'];
            case 'virtual_path':
                return [$this, 'smarty_modifier_virtual_path'];
            case 'yesno':
                return [$this, 'smarty_modifier_yesno'];
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
            case 'rating_result':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\RatingResult();
                break;
            case 'rcontent':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Rcontent();
                break;
            case 'redirect':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Redirect();
                break;
            case 'reindex_file_pixel':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ReindexFilePixel();
                break;
            case 'router_params':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\RouterParams();
                break;
            case 'rss':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Rss();
                break;
            case 'sameurl':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\SameUrl();
                break;
            case 'scheduler_params':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\SchedulerParams();
                break;
            case 'sefurl':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Sefurl();
                break;
            case 'select_all':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\SelectAll();
                break;
            case 'service_inline':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ServiceInline();
                break;
            case 'service':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Service();
                break;
            case 'set':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Set();
                break;
            case 'show_short':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\ShowShort();
                break;
            case 'svn_lastup':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\SvnLastup();
                break;
            case 'svn_rev':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\SvnRev();
                break;
            case 'syntax':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Syntax();
                break;
            case 'thumb':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Thumb();
                break;
            case 'ticket':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Ticket();
                break;
            case 'toolbars':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\Toolbars();
                break;
            case 'tracker_item_status_icon':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\TrackerItemStatusIcon();
                break;
            case 'trackerfields':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\TrackerFields();
                break;
            case 'trackerheader':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\TrackerHeader();
                break;
            case 'trackerinput':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\TrackerInput();
                break;
            case 'trackeroutput':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\TrackerOutput();
                break;
            case 'trackerrules':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\TrackerRules();
                break;
            case 'treetable':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\TreeTable();
                break;
            case 'user_registration':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\UserRegistration();
                break;
            case 'user_selector':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\UserSelector();
                break;
            case 'var_dump':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\VarDump();
                break;
            case 'vimeo_uploader':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\VimeoUploader();
                break;
            case 'wiki_diff':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\WikiDiff();
                break;
            case 'wikistructure':
                $this->functionHandlers[$functionName] = new \SmartyTiki\FunctionHandler\WikiStructure();
                break;
        }
        return $this->functionHandlers[$functionName] ?? null;
    }

    public function getOutputFilters(): array
    {
        if (isset($_REQUEST['highlight']) || (isset($prefs['feature_referer_highlight']) && $prefs['feature_referer_highlight'] == 'y')) {
            $this->outputFilters[] = new \SmartyTiki\Filter\Output\Highlight();
        }

        $this->outputFilters[] = new \SmartyTiki\Filter\Output\Ticket();

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

    /**
     * Smarty modifier addslashes
     * --------------------------
     * Purpose: Quote string with slashes
     * 
     * @param string $string   The string to be escaped.
     * @return string          A string with backslashes added before characters that need to be escaped. These characters are: ',",\, NUL (the NUL byte)
     */
    public function smarty_modifier_addslashes($string)
    {
        return addslashes($string);
    }

    /*
    * Smarty modifier adjust
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

    /**
     * Smarty modifier array_reverse
     * -----------------------------
     * Purpose: Reverse the order of array elements
     * @param array $array          The entry table.
     * @param bool  $preserve_key   Optional. If set to true, numeric keys will be preserved. Non-numeric keys will not be affected by this configuration, and will always be preserved.
     * @return array                The array in reverse order.
     */
    public function smarty_modifier_array_reverse($array, $preserve_keys = false)
    {
        return array_reverse($array, $preserve_keys);
    }
    /**
    * Smarty modifier avatarize
    * -------------------------------------------------------------
    * Purpose:  show avatar for a given user name
    * -------------------------------------------------------------
    */
    public function smarty_modifier_avatarize($user, $float = '', $default = '', $show_tag = 'y')
    {
        if (!$user) {
            return '';
        }

        $avatar = TikiLib::lib('tiki')->get_user_avatar($user, $float);

        if (!$avatar && $default) {
            $smarty = TikiLib::lib('smarty');
            $name = TikiLib::lib('user')->clean_user($user);
            $avatar = smarty_function_icon(['_id' => $default, 'title' => $name], $smarty->getEmptyInternalTemplate());
        }

        if ($avatar != '' && $show_tag == 'y') {
            $avatar = TikiLib::lib('user')->build_userinfo_tag($user, $avatar);
        }
        return $avatar;
    }

    /**
     * This modifier allows the unique "[breakline]" into a string to split into separate lines replaced by <br /> after the use of escape modifier which unallows any html tag.
     * usefull for <label> when they need to be long and/or when automatic breaklines particularly after translations creates a split line in an unapproriate place.
     *
     * @param mixed $str_content
     * @access public
     * @return void
     */
    public function smarty_modifier_breakline($str_content)
    {
        return str_replace('[breakline]', '<br />', $str_content);
    }

    /**
     * Gets Category Id from the Category name
     */

    public function smarty_modifier_categid($category)
    {
        return TikiLib::lib('categ')->get_category_id($category);
    }

    public function smarty_modifier_compactisodate($string)
    {
        global $tikilib;
        return $tikilib->get_compact_iso8601_datetime($string);
    }

    /**
     * \brief Smarty modifier plugin to add user's country flag
     *
     * - type:     modifier
     * - name:     countryflag
     * - purpose:  Returns a specified user's country flag
     *
     * @author
     * @param string
     * @return string
     *
     * Example: {$userinfo.login|countryflag}
     */
    public function smarty_modifier_countryflag($user)
    {
        global $tikilib;
        $flag = $tikilib->get_user_preference($user, 'country', 'Other');
        if ($flag == 'Other' || empty($flag)) {
            return '';
        }
        return "<img alt='" . tra(str_replace('_', ' ', $flag)) . "' src='img/flags/" . str_replace(' ', '_', $flag) .
            ".png' title='" . tra(str_replace('_', ' ', $flag)) . "' />";
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

    /*
    * If installed, this modifier will use Kint (from https://github.com/kint-php/kint/)
    *
    *   You need to enable dev mode for composer by running `php console.php help dev:configure`
    *   and then setup.sh
    *
    * Example usage:
    *
    *     {$smarty.request|d}
    */

    function smarty_modifier_d($var, $modifier = '')
    {
        if (is_callable('Kint::dump')) {
            // add this function as an alias of Kint::dump
            \Kint::$aliases[] = 'smarty_modifier_d';
            // So far SmartyKin just replaces the ugly
            \Kint::$plugins[] = new SmartyKint();

            switch ($modifier) {
                case '!':                   // Expand all data in this dump automatically
                    ! \Kint::dump($var);
                    break;
                case '+':                   // Disable the depth limit in this dump
                    +\Kint::dump($var);
                    break;
                case '-':                   // Clear buffered output and flush after dump
                    -\Kint::dump($var);
                    break;
                case '@':                   // Return the output of this dump instead of echoing it
                    @\Kint::dump($var);
                    break;
                case '~':                   // Use the text renderer for this dump
                    ~\Kint::dump($var);
                    break;
                default:
                    \Kint::dump($var);
            }
        } else {
            var_dump($var);
        }
    }

    /** \file
     *
     *
     * \author zaufi <zaufi@sendmail.ru>
     */
    /**
     * \brief Smarty modifier plugin to add string to debug console log w/o modify output
     * Usage format {$smarty_var|dbg}
     */
    public function smarty_modifier_dbg($string, $label = '')
    {
        global $debugger;
        require_once('lib/debug/debugger.php');
        //
        $debugger->msg('Smarty log' . ((strlen($label) > 0) ? ': ' . $label : '') . ': ' . $string);
        return $string;
    }

    /*
    * Smarty plugin
    * -------------------------------------------------------------
    * Type:     modifier
    *
    * -------------------------------------------------------------
    */
    public function smarty_modifier_div($string, $num, $max = 10)
    {
        if ($num == 0) {
            return 0;
        }

        if (ceil(strlen($string) / $num) > $max) {
            return $max;
        }

        return ceil(strlen($string) / $num);
    }

    /*
    * Smarty plugin
    * -------------------------------------------------------------
    * Type:     modifier
    * Name:     duration
    * Purpose:  formats a duration from seconds
    * -------------------------------------------------------------
    */
    public function smarty_modifier_duration_short($string)
    {
        $result = '';
        if ($string > 60 * 60 * 24) {
            $days = floor($string / (60 * 60 * 24));
            $day_label = $days == 1 ? tra('day') : tra('days');
            $result = "$days " . $day_label;
        } elseif ($string > 60 * 60) {
            $hours = floor($string / (60 * 60));
            $hr_label = $hours == 1 ? tra('hour') : tra('hours');
            $result = "$hours " . $hr_label;
        } elseif ($string > 60) {
            $mins = floor($string / (60));
            $min_label = $mins == 1 ? tra('minute') : tra('minutes');
            $result = "$mins " . $min_label;
        } else {
            $sec_label = $string == 1 ? tra('second') : tra('seconds');
            $result = "$string " . $sec_label;
        }
        return $result;
    }

    /**
    * Smarty plugin
    * -------------------------------------------------------------
    * Type:     modifier
    * Name:     duration
    * Purpose:  formats a duration from seconds
    * -------------------------------------------------------------
    */
    public function smarty_modifier_duration($string, $long = true, $maxLevel = false)
    {
        if (!is_numeric($string)) {
            return $string;
        }
        $values = [31536000, 2628000, 604800, 86400, 3600, 60, 1];
        $output = [tra('year'), tra('month'), tra('week'), tra('day'), tra('hour'), tra('minute'), tra('second')];
        $outputs = [tra('years'), tra('months'), tra('weeks'), tra('days'), tra('hours'), tra('minutes'), tra('seconds')];
        $result = [];

        // maxLevel defines the maximum unit to be consider (e.g. $maxLevel = 'hour')
        if (is_string($maxLevel) && $level = array_search(tra($maxLevel), $output)) {
            $values = array_slice($values, $level);
            $output = array_slice($output, $level);
            $outputs = array_slice($outputs, $level);
        }

        foreach ($values as $i => $value) {
            if ($string >= $value) {
                $nb = floor($string / $value);
                // add a zero before seconds or minutes with just one digit if $long == false
                $nb = (!$long && !empty($result) && ($output[$i] == 'minute' || $output[$i] == 'second') && strlen($nb) == 1) ? 0 . $nb : $nb;
                $s = ($nb == 1) ? $output[$i] : $outputs[$i];
                $s = $long ? " $s" : substr($s, 0, 1);
                $string = $string % $value;
                $result[] = "$nb$s";
            }
        }
        return implode(' ', $result);
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

    /*
    * Smarty plugin
    * -------------------------------------------------------------
    * Type:     modifier
    * Name:     file_can_convert_to_pdf
    * Purpose:  Checks if mimetype is supported to convert to PDF
    * -------------------------------------------------------------
    */
    public function smarty_modifier_file_can_convert_to_pdf($string)
    {
        return \Tiki\File\PDFHelper::canConvertToPDF($string);
    }

    /**
     * Checks if a given file id is a diagram
     */
    public function smarty_modifier_file_diagram($fileId)
    {
        return \Tiki\File\DiagramHelper::isDiagram($fileId);
    }

    public function smarty_modifier_forumname($commentid, $retrun_forumid = 'n')
    {
        global $tikilib;
        $cachelib = TikiLib::lib('cache');
        require_once 'lib/comments/commentslib.php';
        $comments = new \Comments();

        if ($retrun_forumid == 'y') {
            $cacheItem = "retrun_forumid" . $commentid;
        } else {
            $cacheItem = "retrun_forumname" . $commentid;
        }

        if ($cached = $cachelib->getCached($cacheItem)) {
            return $cached;
        }

        $forum_id = $comments->get_comment_forum_id($commentid);
        $cachelib->cacheItem($cacheItem, $forum_id);
        if ($retrun_forumid == 'y') {
            return $forum_id;
        }
        $ret = $comments->get_forum($forum_id);
        $cachelib->cacheItem($cacheItem, $ret['name']);
        return $ret['name'];
    }

    public function smarty_modifier_forumtopiccount($forumId)
    {
        return TikiLib::lib('comments')->count_forum_topics($forumId);
    }

    public function smarty_modifier_groupmembercount($group)
    {
        return TikiLib::lib('user')->nb_users_in_group($group);
    }

    /*
    * Smarty plugin
    * -------------------------------------------------------------
    * Type:     modifier
    * Name:     lcfirst
    * Purpose:  to use with the tracker field type "User inscription"
    *           if $text="12[13], 14[15], 16[17]"
    *           then return 48 (=13+1+15+1+17+1)
    * -------------------------------------------------------------
    */
    public function smarty_modifier_how_many_user_inscriptions($text)
    {

        $pattern = "/\d+\[(\d+)\]/";
        $out = preg_match_all($pattern, $text, $match);

        $nb = 0;

        foreach ($match[1] as $n) {
            $nb += ($n + 1);
        }

        return $nb;
    }

    /*
    * Smarty plugin
    * -------------------------------------------------------------
    * Type:     modifier
    * Name:     htmldecode
    * Purpose:  Convert all HTML entities to their applicable characters
    * -------------------------------------------------------------
    */
    public function smarty_modifier_htmldecode($s)
    {
        return TikiLib::htmldecode($s);
    }

    /**
     * Smarty plugin
     * -------------------------------------------------------------
     * Type:     modifier
     * Name:     iconify
     * Purpose:  Returns a filetype icon or file type
     * -------------------------------------------------------------
     *
     * @param $string               File name with extension
     * @param null $filetype        File type
     * @param null $fileId          File id when using file galleries
     * @param int $size             Icon size
     * @param string $return        icon or filtype
     * @return null|string
     * @throws Exception
     * @throws \Smarty\Exception
     */
    public function smarty_modifier_iconify($string, $filetype = '', $fileId = null, $size = 1, $return = 'icon')
    {
        $smarty = TikiLib::lib('smarty');
        global $prefs;

        $icon = '';
        $ext = strtolower(substr($string, strrpos($string, '.') + 1));
        if ($fileId && substr($filetype, 0, 6) == 'image/') {
            // Special handling for file gallery images,
            // display thumbnail
            $icon = smarty_modifier_sefurl($fileId, 'thumbnail');
            $icon = smarty_modifier_escape($icon);
            $width = 16 * $size;

            return "<img src=\"$icon\" width=\"$width\"/>";
        } else {
            include_once('lib/mime/mimetypes.php');
            global $mimetypes;
            $mimes = array_keys($mimetypes, $filetype);
            if ($prefs['theme_iconset'] === 'legacy') {
                if (file_exists("img/icons/mime/$ext.png")) {
                    $icon = $ext;
                } elseif (file_exists('img/icons/mime/' . substr($ext, 0, 3) . '.png')) {
                    $icon = substr($ext, 0, 3);
                } else {
                    foreach ($mimes as $m) {
                        if (file_exists("img/icons/mime/$m.png")) {
                            $icon = $m;
                        }
                    }
                    if (empty($icon)) {
                        $icon = 'default';
                    }
                }
                if ($return === 'filetype') {
                    return $m;
                } elseif ($return === 'icon') {
                    return smarty_function_icon(
                        [
                            '_id' => 'img/icons/mime/' . $icon . '.png',
                            'alt' => ( $filetype === null ? $icon : $filetype ),
                            'class' => '',
                            'size' => $size
                        ],
                        $smarty->getEmptyInternalTemplate()
                    );
                }
            //iconsets introduced with Tiki14
            } else {
                if (! empty($filetype)) {
                    $type = $filetype;
                } elseif (! empty($mimetypes[$ext])) {
                    $type = $mimetypes[$ext];
                } else {
                    $type = 'file';
                }
                switch ($type) {
                    case $type === 'application/msword'
                        || $type === 'application/vnd.ms-word'
                        || strpos($type, 'application/vnd.openxmlformats-officedocument.wordprocessingml') === 0:
                        $iconname = 'word';
                        break;
                    case $type === 'application/pdf':
                        $iconname = 'pdf';
                        break;
                    case $type === 'application/vnd.ms-excel'
                        || $type === 'application/ms-excel'
                        || $type === 'application/msexcel'
                        || strpos($type, 'application/vnd.openxmlformats-officedocument.spreadsheetml') === 0
                        || $type === 'application/vnd.oasis.opendocument.spreadsheet':
                        $iconname = 'excel';
                        break;
                    case $type === 'application/vnd.ms-powerpoint'
                        || $type === 'application/ms-powerpoint'
                        || $type === 'application/mspowerpoint'
                        || strpos($type, 'application/vnd.openxmlformats-officedocument.presentationml') === 0:
                        $iconname = 'powerpoint';
                        break;
                    case strpos($type, 'audio/') === 0:
                        $iconname = 'audio';
                        break;
                    case strpos($type, 'image/') === 0:
                        $iconname = 'image';
                        break;
                    case strpos($type, 'text/') === 0:
                        switch ($ext) {
                            case 'c':
                            case 'cpp':
                            case 'css':
                            case 'htm':
                            case 'html':
                            case 'java':
                            case 'js':
                            case 'less':
                            case 'php':
                            case 'py':
                            case 'scss':
                                $iconname = 'code_file';
                                break;
                            case 'eml':
                                $iconname = 'envelope';
                                break;
                            default:
                                $iconname = 'textfile';
                        }
                        break;
                    case $type === 'application/vnd.oasis.opendocument.text':
                        $iconname = 'textfile';
                        break;
                    case strpos($type, 'video/') === 0:
                        $iconname = 'video';
                        break;
                    case strpos($type, 'application/') === 0 && strpos($type, 'zip') !== false:
                        $iconname = 'zip';
                        break;
                    case strpos($type, 'application/') === 0 &&
                            (strpos($type, 'mail') !== false || $type === 'application/CDFV2-corrupt'):     // outlook mail
                        $iconname = 'envelope';
                        break;
                    default:
                        $iconname = 'file';
                        break;
                }
                if ($return === 'filetype') {
                    return $type;
                } else {
                    return smarty_function_icon(['name' => $iconname, 'size' => $size], $smarty->getEmptyInternalTemplate());
                }
            }
        }
    }

    /**
     * Samrty modifier implode
     * -----------------------
     * Purpose: Joins the elements of an array into a string
     * 
     * @param string $separator     Seprator
     * @param array $array          The array of strings to gather.
     * @return string
     * 
     * Synthax: {$separator|implode:$array}
     */
    public function smarty_modifier_implode($separator, $array)
    {
        if (! is_array($array)) {
            return $array;
        }
        return implode($separator, $array);
    }

    /**
     * Tests whether a user is in a specific group, usage:
     *
     *      {if 'Admins'|in_group}...
     * or
     *      {if 'Group Name'|in_group:'testuser'}...
     *
     * @param string $group     group name to test (string being "modified")
     * @param string $auser     user name to check if not current logged-in user
     * @return bool
     * @throws Exception
     */

    public function smarty_modifier_in_group($group, $auser = '')
    {
        global $user;

        if (! $auser) {
            $auser = $user;
        }
        return TikiLib::lib('user')->user_is_in_group($auser, $group);
    }

    public function smarty_modifier_is_array($array)
    {
        return is_array($array);
    }

    public function smarty_modifier_isodate($string)
    {
        global $tikilib;
        return $tikilib->get_iso8601_datetime($string);
    }

    /**
     * Smarty modifer json_decode
     * --------------------------
     * Purpose: Decode a JSON string. Get a string JSON encoded and convert it into a PHP value.
     * 
     * @param string    $json          The JSON string
     * @param bool      $associative
     * @param int       $depth
     * @param int       $flags
     * @return mixed
     * @see https://php.net/manual/en/function.json-decode.php for more details about params
     */
    public function smarty_modifier_json_decode($json, $associative = null, $depth = 512, $flags = 0)
    {
        return json_decode($json, $associative, $depth, $flags);
    }

    /**
     * Smarty modifier json_encode
     * ---------------------------
     * Purpose: Returns the JSON representation of a value
     * 
     * @param mixed $value   The value to encode. Can be any type except a resource
     * @param int   $flags
     * @param int   $depth
     * @return JSON|flase    Returns JSON encoded as a string on success or false on error.
     * 
     * @see https://php.net/manual/en/function.json-encode.php for more details about params description
     */
    public function smarty_modifier_json_encode($value, $flags = 0, $depth = 512)
    {
        return json_encode($value, $flags, $depth);
    }

    /*
    * Smarty plugin
    * -------------------------------------------------------------
    * Type:     modifier
    * Name:     kbsize
    * Purpose:  returns size in Mb, Kb or bytes.
    * -------------------------------------------------------------
    */
    public function smarty_modifier_kbsize($string, $bytes = false, $nb_decimals = 2, $unit_separator = '&nbsp;')
    {
        if ($string == '') {
            return '';
        }

        if ($string > 1099511627776) { // 1024 x 1024 x 1024 x 1024 = 1099511627776
            $string = number_format($string / 1099511627776, $nb_decimals);
            $kb_string = 'T';
        } elseif ($string > 1073741824) { // 1024 x 1024 x 1024 = 1073741824
            $string = number_format($string / 1073741824, $nb_decimals);
            $kb_string = 'G';
        } elseif ($string > 1048576) { // 1024 x 1024 = 1048576
            $string = number_format($string / 1048576, $nb_decimals);
            $kb_string = 'M';
        } elseif ($string > 1024) {
            $string = number_format($string / 1024, $nb_decimals);
            $kb_string = 'K';
        } else {
            $string = $string;
            $kb_string = '';
        };

        $kb_string = $kb_string . (($bytes) ? 'B' : 'b');

        return $string . $unit_separator . tra($kb_string);
    }

    public function smarty_modifier_langname($lang)
    {
        if (empty($lang)) {
            return '';
        }

        include('lang/langmapping.php');
        return empty($langmapping[$lang]) ? $lang : tra($langmapping[$lang][0]);
    }

    /*
    * Smarty plugin
    * -------------------------------------------------------------
    * Type:     modifier
    * Name:     lcfirst
    * Purpose:  lowercase the initial character in a string
    * -------------------------------------------------------------
    */
    public function smarty_modifier_lcfirst($s)
    {
        return strtolower($s[0]) . substr($s, 1);
    }

    /*
    * Smarty plugin
    * -------------------------------------------------------------
    * Type:     modifier
    * Name:     max_user_inscriptions
    * Purpose:  to use with the tracker field type "User inscription"
    * -------------------------------------------------------------
    */
    public function smarty_modifier_max_user_inscriptions($text)
    {
        return substr($text, 0, strpos($text, '#'));
    }

    /**
     * Smarty modifier md5
     * -------------------
     * Purpose: Calculate the md5 of a string
     * 
     * @param string $string  The string
     * @param bool   $binary  Optional. If set to true, then the md5 is returned in raw binary format with a length of 16.
     * @return string         Returns the md5 of the string, as a 32-character hexadecimal number.
     */
    public function smarty_modifier_md5($string, $binary = false)
    {
        return md5($string, $binary);
    }

    /**
    * Smarty money_format modifier plugin
    *
    * Type:     modifier<br>
    * Name:     money_format<br>
    * Purpose:  Format a currency amount.
    *           Mimics money_format, which can't be used on windows servers, with some enhancements for alignment.
    * Copyright: Based on code posted at http://www.php.net/manual/en/function.money-format.php#89060
    *            by Rafael M. Salvioni on 19-Feb-2009 08:20.
    *            All documentation and comments on that site are covered by the
    *            Creative Commons Attribution 3.0 License, copyright (c) the PHP Documentation Group.
    *            See http://www.php.net/license/index.php#web-lic and http://creativecommons.org/licenses/by/3.0/
    * @link based on money_format(): http://www.php.net/manual/en/function.money-format.php#89060
    * @author lindon
    * @param number
    * @param locale: currency locale for formatting (default=en_US)
    * @param format: format string
    * @oaram all: whether all amounts in list have a currency symbol or on first item
    * @return formatted number
    */
   public function smarty_modifier_money_format($number, $locale, $currency, $format = '%(#10n', $display = 0)
   {
       if (! empty($locale)) {
           $ret = setlocale(LC_MONETARY, $locale);
           if ($ret === false) {
               \Feedback::error(tr('"%0" is not supported by this system.', $locale));
               return $number;
           }
       }

       if (! extension_loaded('intl')) {
           \Feedback::error(tr('The PHP "intl" extension is required for "money_format".'));
           return $number;
       }

       $locale = localeconv();

       if (! empty($currency)) {
           $locale['int_curr_symbol'] = $currency;
           $fmt = new \NumberFormatter(locale_get_default() . "@currency=$currency", \NumberFormatter::CURRENCY);
           $locale['currency_symbol'] = $fmt->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);
       }

       //regex for format string
       $regex = '/%((?:[\^!\-]|\+|\(|\=.)*)([0-9]+)?' .
                   '(?:#([0-9]+))?(?:\.([0-9]+))?([in%])/';
       preg_match_all($regex, $format, $matches, PREG_SET_ORDER);
       foreach ($matches as $fmatch) {
           $value = (float)$number;
           $flags = [
               'fillchar' => preg_match('/\=(.)/', $fmatch[1], $match) ?
                               $match[1] : ' ',
               'nogroup' => preg_match('/\^/', $fmatch[1]) > 0,
               'usesignal' => preg_match('/\+|\(/', $fmatch[1], $match) ?
                               $match[0] : '+',
               'nosimbol' => preg_match('/\!/', $fmatch[1]) > 0,
               'isleft' => preg_match('/\-/', $fmatch[1]) > 0,
           ];
           $width = trim($fmatch[2]) ? (int) $fmatch[2] : 0;
           $left = trim($fmatch[3]) ? (int) $fmatch[3] : 0;
           $right = trim($fmatch[4]) ? (int) $fmatch[4] : $locale['int_frac_digits'];
           $conversion = $fmatch[5];

           $positive = true;
           if ($value < 0) {
               $positive = false;
               $value *= -1;
           }
           $letter = $positive ? 'p' : 'n';

           $prefix = $suffix = $cprefix = $csuffix = $signal = '';
           $signal = $positive ? $locale['positive_sign'] : $locale['negative_sign'];

           switch (true) {
               case $locale["{$letter}_sign_posn"] == 1 && $flags['usesignal'] == '+':
                   $prefix = $signal;
                   break;
               case $locale["{$letter}_sign_posn"] == 2 && $flags['usesignal'] == '+':
                   $suffix = $signal;
                   break;
               case $locale["{$letter}_sign_posn"] == 3 && $flags['usesignal'] == '+':
                   $cprefix = $signal;
                   break;
               case $locale["{$letter}_sign_posn"] == 4 && $flags['usesignal'] == '+':
                   $csuffix = $signal;
                   break;
               case $flags['usesignal'] == '(':
               case $locale["{$letter}_sign_posn"] == 0:
                   if ($positive == false) {
                       $prefix = '(';
                       $suffix = ')';
                   }
                   break;
           }

           if (! $flags['nosimbol']) {
               $currency = $cprefix . ($conversion == 'i' ? $locale['int_curr_symbol'] : $locale['currency_symbol']) . $csuffix;
           } else {
               $currency = '';
           }

           if (! empty($currency) && $display == 0) {
               $currency = '<span style="visibility:hidden">' . $currency . '</span>';
           }

           $space = $locale["{$letter}_sep_by_space"] && ! empty($currency) && $display == 1 ? ' ' : '';

           $value = number_format(
               $value,
               $right,
               $locale['mon_decimal_point'],
               $flags['nogroup'] ? '' : $locale['mon_thousands_sep']
           );
           $value = @explode($locale['mon_decimal_point'], $value);

           $n = strlen($prefix) + strlen($currency) + strlen($value[0]);
           if ($left > 0 && $left > $n) {
               $value[0] = str_repeat($flags['fillchar'], $left - $n) . $value[0];
           }
           $value = implode($locale['mon_decimal_point'], $value);

           if ($locale["{$letter}_cs_precedes"]) {
               $value = $prefix . $currency . $space . $value . $suffix;
               //create right pad for alignment
               $rightpad = empty($suffix) && $flags['usesignal'] == '(' ? ')' : '';
           } else {
               $value = $prefix . $value . $space . $currency . $suffix;
               //create right pad for alignment
               $rightpad = $locale['n_sep_by_space'] == true && $locale['n_cs_precedes'] == false && strlen($space) == 0 ? ' ' : '';
               $currpad = $conversion == 'i' ? $locale['int_curr_symbol'] : $locale['currency_symbol'];
               $rightpad .= ($locale['p_cs_precedes'] == false || $locale['n_cs_precedes'] == false) && empty($currency) ? $currpad : '';
               $rightpad .= empty($suffix) && $flags['usesignal'] == '(' ? ')' : '';
           }

           $format = str_replace($fmatch[0], $value, $format);
           if (! empty($rightpad) && $display == 0) {
               $format .= '<span style="visibility:hidden">' . $rightpad . '</span>';
           }
       }
       return $format;
   }

   public function smarty_modifier_namespace($pageName)
   {
       return TikiLib::lib('wiki')->get_namespace($pageName);
   }

   public function smarty_modifier_nonamespace($pageName)
   {
       return TikiLib::lib('wiki')->get_without_namespace($pageName);
   }

   /**
     * Smarty modifier plugin to remove ~np~ tags from smarty variable. For use in templates used by the {list} wiki plugin
     *
     * - type:     modifier
     * - name:     nonp (short for "no nonparsed")
     * - purpose:  to return a usable string
     *
     * @param string to be replaced (optional)
     * @return corrected string
     *
     * Example: {if $row.title|nonp eq ''}
     */

    public function smarty_modifier_nonp($string)
    {
        return preg_replace('/~[\/]?np~/', '', $string);
    }

    /**
     * Smarty substring modifier plugin
     *
     * Type:     modifier<br>
     * Name:     number_format<br>
     * Purpose:  Format a number. Same arguments as
     *           PHP number_format function.
     * @link based on number_format(): http://www.php.net/manual/function.number-format.php
     * @author   lindon
     * @param number
     * @param decimals: sets the number of decimal places (default=0)
     * @param dec_point: sets the separator for the decimal point
     * @param thousands: thousands separator
     * @return number
     */
    public function smarty_modifier_number_format($number, $decimals = 2, $dec_point = '.', $thousands = ',')
    {
        $dec_point = $this->separator($dec_point);
        $thousands = $this->separator($thousands);
        return number_format($number, $decimals, $dec_point, $thousands);
    }

    private function separator($sep)
    {
        switch ($sep) {
            case 'c':
            case ',':
                $sep = ',';
                break;
            case 'd':
            case '.':
                $sep = '.';
                break;
            case 's':
            case ' ':
                $sep = ' ';
                break;
        }
        return $sep;
    }

    /**
     * Converts a positive integer into a selected format.
     * Obe may choose a CSS list-style-type value. Some language specific options have not been implemented.
     *
     * One may also select a 'footnote' value, which will use traditional footnote markers.
     *
     * Eastern languages are not implemented, they require a different formula as they use a modifier
     * before 10 instead of repeating it.
     *
     * @param $num int the number to be converted
     * @param $type string the CSS List-Style-Type to format the number to
     *
     * @return string The returned number in the given format
     *
     * eg. {14|numStyle:'upper-roman'}
     */

    function smarty_modifier_numStyle($num, $type)
    {
        $style = new StyleType();
        $num = (int)$num;                     // some basic filtering, using negative or 0 may result in failure, depending on selection
        if ($num < 1) {
            return $num;
        }
        switch (strtolower($type)) {
            case "decimal-leading-zero":
                return '0' . $num;
            case 'lower-alpha':
            case 'lower-latin':
                return $style->toAlpha($num, range('a', 'z'));
            case 'upper-alpha':
            case 'upper-latin':
                return $style->toAlpha($num, range('A', 'Z'));
            case 'lower-greek':
                $range = ['α', 'β', 'γ', 'δ', 'ε', 'ζ', 'η', 'θ', 'ι', 'κ', 'λ', 'μ', 'ν', 'ξ', 'ο', 'π', 'ρ', 'σ', 'τ', 'υ', 'φ', 'χ', 'ψ', 'ω'];
                return $style->toAlpha($num, $range);
            case 'upper-greek':
                $range = ['Α', 'Β', 'Γ', 'Δ', 'Ε', 'Ζ', 'Η', 'Θ', 'Ι', 'Κ', 'Λ', 'Μ', 'Ν', 'Ξ', 'Ο', 'Π', 'Ρ', 'Σ', 'Τ', 'Υ', 'Φ', 'Χ', 'Ψ', 'Ω'];
                return $style->toAlpha($num, $range);
            case 'lower-roman':
                return strtolower($style->numerals($num));
            case 'upper-roman':
                return $style->numerals($num);
            case 'hebrew':
                return $style->numerals($num, ['א׳א׳‎' => 1000000 ,'א׳ק‎' => 100000 ,'א׳י‎' => 10000 ,'ה׳‎' => 5000 ,'ב׳‎' => 2000 ,'א׳‎' => 1000 ,'ץ‎' => 900 ,'ף‎' => 800 ,'ן‎' => 700 ,'ם‎' => 600 ,'ך‎' => 500 ,'ת‎' => 400 ,'ש‎' => 300 ,'ר‎' => 200 ,'ק‎' => 100 ,'צ‎' => 90 ,'פ‎' => 80 ,'ע‎' => 70 ,'ס‎' => 60 ,'נ‎' => 50 ,'מ‎' => 40 ,'ל‎' => 30 ,'כ‎' => 20 ,'יט‎' => 19 ,'יח‎' => 18 ,'יז‎' => 17 ,'ט״ז‎' => 16 ,'ט״ו‎' => 15 ,'יד‎' => 14 ,'יג‎' => 13 ,'יב‎' => 12 ,'יא‎' => 11 ,'י‎' => 10 ,'ט‎' => 9 ,'ח‎' => 8 ,'ז‎' => 7,'ו‎' => 6 ,'ה‎' => 5 ,'ד‎' => 4 ,'ג‎' => 3 ,'ב‎' => 2 ,'א‎' => 1]);
            case 'georgian':
                return $style->numerals($num, ['ჵ' => 10000, 'ჰ' => 9000, 'ჯ' => 8000, 'ჴ' => 7000, 'ხ' => 6000, 'ჭ' => 5000, 'წ' => 4000, 'ძ' => 3000, 'ც' => 2000, 'ჩ' => 1000, 'შ' => 900, 'ყ' => 800, 'ღ' => 700, 'ქ' => 600, 'ფ' => 500, 'ჳ' => 400, 'ტ' => 300, 'ს' => 200, 'რ' => 100, 'ჟ' => 90, 'პ' => 80, 'ო' => 70, 'ჲ' => 60, 'ნ' => 50, 'მ' => 40, 'ლ' => 30, 'კ' => 20, 'ი' => 10, 'თ' => 9, 'ჱ' => 8, 'ზ' => 7, 'ვ' => 6, 'ე' => 5, 'დ' => 4, 'გ' => 3, 'ბ' => 2, 'ა' => 1]);
            case 'footnote':
                return $style->toAlpha($num, ['*','†','‡','§','ƒ']);
            case 'disc':
                return '●';
            case 'circle':
                return '○';
            case 'square':
                return '■';
            case 'none':
                return '';
            default:
                return $num;
        }
    }

    /**
     *  Fake modifier for pretty tracker fields
     */
    public function smarty_modifier_output($string)
    {
        return $string;
    }

    public function smarty_modifier_packageitemid($token)
    {
        $api = new \Tiki\Package\Extension\Api();
        return $api->getItemIdFromToken($token);
    }

    public function smarty_modifier_pagename($source)
    {
        global $prefs;
        if (! empty($prefs['wiki_pagename_strip']) || $prefs['namespace_indicator_in_page_title'] == 'y') {
            if (! empty($prefs['wiki_pagename_strip'])) {
                $wiki_strip = '~' . preg_quote($prefs['wiki_pagename_strip']) . '.*$~';
                $source = preg_replace($wiki_strip, '', $source);
            }
            if ($prefs['namespace_indicator_in_page_title'] == 'y') {
                $wiki_namespace = '~.* / ~';
                $source = preg_replace($wiki_namespace, '', $source);
            }
            return $source;
        } else {
            return $source;
        }
    }

    /**
     * Smarty parse modifier plugin
     * Type:     modifier
     * Name:     parse
     * Purpose:  Parse code in Tiki syntax
     *
     * @param boolean $simple true for less parsing, false for normal parsing
     *
     * @return string Parsed string
     */
    public function smarty_modifier_parse($string, $simple = false)
    {
        $parserlib = TikiLib::lib('parser');
        if ($simple) {
            $string = htmlentities($string, ENT_QUOTES, 'UTF-8'); // Surely this should not be done here, if it is necessary. Chealer 2017-12-29
            return $parserlib->parse_data_simple($string);
        } else {
            return $parserlib->parse_data($string);
        }
    }

    /**
     *
     * returns a percentage instead of a fraction
     * @param float $string fraction to format
     */
    public function smarty_modifier_percent($string)
    {
        return number_format($string * 100, 1);
    }

   /**
    * Smarty modifier preg_match
    * --------------------------
    * Purpose: Perform a regular expression match
    * @param string $pattern     The pattern to search for, as a string.
    * @param string $subject     The input string.
    * @param array  $matches 
    * @param int    $flags
    * @param int    $offset
    * @return int|flase
    * @see https://php.net/manual/en/function.preg-match.php for details about the params description
    */
    public function smarty_modifier_preg_match($pattern, $subject, $matches = null, $flags = 0, $offset = 0)
    {
        return preg_match($pattern, $subject, $matches, $flags, $offset);;
    }

    /*
    * Smarty plugin
    * -------------------------------------------------------------
    * Type:     modifier
    * Name:     quoted
    * Purpose:  quote text by adding ">" or using {QUOTE()} plugin
    * -------------------------------------------------------------
    */
    public function smarty_modifier_quoted($string, $format = 'simple', $replyto = '')
    {
        if ($format == 'simple') {
            $string = str_replace("\n", "\n>", $string);
            $string = "\n>" . $string;
        } elseif ($format == 'fancy') {
            $string = "{QUOTE(replyto=>$replyto)}" . $string . '{QUOTE}';
        }
        return $string;
    }

    /**
     * Smarty plugin
     * @package Smarty
     * @subpackage plugins
     */

    /**
     * Smarty replacei modifier plugin
     *
     * Type:     modifier<br>
     * Name:     replacei<br>
     * Purpose:  Returns a case insensitive replaced string.
     *           Same arguments as PHP str_ireplace function.
     */
    public function smarty_modifier_replacei($string, $find, $replacement)
    {
        return str_ireplace($find, $replacement, $string);
    }

    /**
     * Smarty reverse_array modifier plugin
     *
     * Type:     modifier<br>
     * Name:     reverse_array<br>
     * Purpose:  reverse arrays
     * @param array
     * @return array
     */
    public function smarty_modifier_reverse_array($array)
    {
        return array_reverse($array);
    }

    /**
     * Smarty modifier plugin to convert a string suitable for a URL using the current SEFURL slug settings wiki_url_scheme
     *
     * - type:     modifier
     * - name:     slug
     * - purpose:  to return a usable SEFURL URL component string
     * - note:     also removes non-parse wiki tags generated by the list plugin
     *
     * Example: href="Page-Name-{$row.object_id}-{$row.title|slug}"
     *
     * @param string $string   String to be slugified
     * @param int  $maxLength  defaults to 70
     * @param bool $mixedCase  set true to preserve capitalisation
     * @param bool $breakWords set true to break words
     *
     * @return string
     *
     * @throws \Smarty\Exception
     */
    public function smarty_modifier_slug($string, $maxLength = 70, $mixedCase = false, $breakWords = false)
    {
        global $prefs;
        $length = strlen($string);

        if (! $breakWords && $length > $maxLength) {
            $offset = strrpos($string, ' ', $maxLength - $length);
            if ($offset) {
                $maxLength = $offset;
            }
        }
        $string = substr(smarty_modifier_nonp($string), 0, $maxLength);
        if (! $mixedCase) {
            $string = mb_strtolower($string);
        }

        $asciiOnly = $prefs['url_only_ascii'] === 'y';

        /** @var \Tiki\Wiki\SlugManager $slugManager */
        $slugManager = TikiLib::lib('slugmanager');
        // when used as a modifier in a smarty template (e.g. {$row.title|slug})
        // we don't want the -2 at the end if a wiki page also exists, so disable the validator fn
        $slugManager->setValidationCallback(function () {
            return false;
        });
        $str = $slugManager->generate($prefs['wiki_url_scheme'], $string, $asciiOnly);

        if (! $asciiOnly) {
            $str = urlencode($str);
        }
        return $str;
    }

    public function smarty_modifier_star($score)
    {
        global $prefs, $tikilib;

        if ($prefs['feature_score'] != 'y') {
            return '';
        }

        return $tikilib->get_star($score);
    }

    /**
     * Smarty substring modifier plugin
     *
     * Type:     modifier<br>
     * Name:     substring<br>
     * Purpose:  Returns a substring of string.  Same arguments as
     *           PHP substr function.
     * @link based on substr(): http://www.zend.com/manual/function.substr.php
     * @author   Mike Kerr <tiki.kerrnel at kerris dot com>
     * @param string
     * @param position: start position of substring (default=0, negative starts N from end)
     * @param length: length of substring (default=to end of string; negative=left N from end)
     * @return string
     */
    public function smarty_modifier_substring($string, $position = 0, $length = null)
    {

        if ($length == null) {
            return substr($string, $position);
        } else {
            return substr($string, $position, $length);
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

    /**
     * Smarty modifier stristr
     * -----------------------
     * Purpose: Returns a haystack substring, from the first occurrence case insensitive of needle (inclusive) to the end of the string.
     * 
     * @param string $haystack       The string in which to search.
     * @param string $needle         The string to look for.
     * @param bool   $before_needle  Optional. If true, stristr() returns the part of haystack before the first occurrence of needle (needle excluded).
     * @return string|false          Returns the matching part of the string. If needle is not found, the function returns false.
     * 
     */
    public function smarty_modifier_stristr($haystack, $needle, $before_needle = false)
    {
        return stristr($haystack, $needle, $before_needle);
    }
    /**
     * Smarty modifier strpos
     * ----------------------
     * Purpose: Find the position of the first occurrence in a string
     * 
     * @param string $haystack     The string in which to search.
     * @param string $needle       The string to search for.
     * @param int<0, max>  $offset       Optional. The position from which to start the search
     * @return int|false
     * 
     * Synthax: {$haystack|strpos:$needle:$offset}
     */
    public function smarty_modifier_strpos($haystack, $needle, $offset= 0)
    {
        return strpos($haystack, $needle, $offset);
    }

    public function smarty_modifier_tasklink($taskId, $class_name = "link", $offset = "0", $sort_mode = "priority_desc")
    {
        global $tasklib, $user, $prefs, $smarty;
        $tikilib = TikiLib::lib('tiki');

        include_once('lib/tasks/tasklib.php');

        $info = $tasklib->get_task($user, $taskId);
        if ($prefs['feature_community_mouseover'] == 'y') {
            $description = "";

            $my_length = strlen($info['description']);
            $my_pos = 0;
            $line = 0;
            if ($my_length > 0) {
                do {
                    $line++;
                    $my_pos = strpos($info['description'], "\n", ($my_pos + 1));
                } while (($line <= 15) && ($my_pos != ''));
            }

            if (($my_length >= 1300) || ($line >= 16)) {
                if ($line < 15) {
                    $my_pos = 1300;
                }
                $description .= substr($info['description'], 0, min(1300, $my_pos + 1));
                $append = "<br /><center><span class=\'highlight\'>" . tra("Text cut here") . "</span></center>";
            } else {
                $description = $info['description'];
                $append = '';
            }

            // FIXME: Truncated Tiki syntax cannot be parsed.
            $description = TikiLib::lib('parser')->parse_data($description) . $append;

            $tooltipContent = tra("Task") . ' ' . tra("from") . ' <b>' . $info['creator'] . '</b> ' . tra("for") .
            ' <b>' . $info['user'] . '</b>.<br />' . tra("Priority") . ': <b>' . $info['priority'] . '</b>, (<b>' .
            $info['percentage'] . '%</b>) ' . tra('done') . '.<br />';

            if ($info['start'] != 0) {
                $tooltipContent .= tra("Start date:") . " " . $tikilib->date_format("%H:%M -- %d. %e. %Y", $info['start']) . "<br />";
            } else {
                $tooltipContent .= tra("Start date:") . " -<br />";
            }

            if ($info['end']) {
                $tooltipContent .= tra("End date:") . " " . $tikilib->date_format("%H:%M -- %d. %e. %Y", $info['end']) . "<br />";
            } else {
                $tooltipContent .= tra("End date:") . " -<br />";
            }

            $tooltipContent .= "<hr />" . $description;

            $popupAttributes = smarty_function_popup(['text' => $tooltipContent, 'fullhtml' => true], $smarty->getEmptyInternalTemplate());
        } else {
            $popupAttributes = '';
        }
        $content = "<a class='" . $class_name . "' " . $popupAttributes . " href='tiki-user_tasks.php?taskId=" . $taskId . "&amp;tiki_view_mode=view&amp;offset=" .
        $offset . "&amp;sort_mode=" . $sort_mode . "' ";
        if ($info['status'] == 'c') {
            $content .= "style=\"text-decoration:line-through;\"";
        }
        $content .= ">" . htmlspecialchars($info['title']) . "</a>";
        return $content;
    }

    /**
     *  Fake modifier for pretty tracker fields. Sets template to use to display
     */
    function smarty_modifier_template($string)
    {
        return $string;
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

    /*
    * Smarty plugin
    * -------------------------------------------------------------
    * Type:     modifier
    * Name:     tiki_date_format
    * Purpose:  format a string representing a moment (timezone adjusted to user specified timezone)
    * Input:    string: string representing a moment
    *           format: strftime() format for output. For standard formats, see modifiers tiki_{long,short}_{date,datetime,time}.
    *           _user: if specified, use this user timezone instead of the current user
    * -------------------------------------------------------------
    */
    public function smarty_modifier_tiki_date_format($string, $format, $_user = false)
    {
        return TikiLib::date_format(tra($format), $string, $_user);
    }

    public function smarty_modifier_tiki_long_date($string)
    {
        global $prefs;
        $smarty = TikiLib::lib('smarty');
        return smarty_modifier_tiki_date_format($string, $prefs['long_date_format']);
    }

    public function smarty_modifier_tiki_long_datetime($string)
    {
        global $prefs;
        $smarty = TikiLib::lib('smarty');
        // if you change the separator do not forget to change the translation instruction in lib/prefs/long.php
        return smarty_modifier_tiki_date_format($string, $prefs['long_date_format'] . ' ' . $prefs['long_time_format']);
    }

    public function smarty_modifier_tiki_long_time($string)
    {
        global $prefs;
        $smarty = TikiLib::lib('smarty');
        return smarty_modifier_tiki_date_format($string, $prefs['long_time_format']);
    }

    /**
     * @param int $time future time to calculate number of day to go
     * @param string $format PHP date format for the displayed date set in $time.
     *
     * @return string
     */
    public function smarty_modifier_tiki_remaining_days_from_now($time, $format)
    {
        global $tikilib;

        $iNbDayBetween = ($time - $tikilib->now) / (60 * 60 * 24);

        if ($iNbDayBetween > 1) {
            $result = sprintf(
                ($iNbDayBetween > 1 ? tra('in %s days, the %s') : tra('in %s day, the %s')),
                '<b>' . round($iNbDayBetween) . '</b>',
                $tikilib->date_format($format, $time)
            );
        } elseif ($iNbDayBetween > 0) {
            $result = tra('Today');
        } else {
            $result = '-';
        }
        return $result;
    }

    /**
     * @param string  $string
     * @param string $same   if set to 'n' will bypass timeago preferences. Useful when markup is illegal in date
     *
     * @return string
     */

    public function smarty_modifier_tiki_short_date($string, $same = 'y')
    {
        global $prefs;
        $date = smarty_modifier_tiki_date_format($string, $prefs['short_date_format']);

        if ($prefs['jquery_timeago'] === 'y' && $same === 'y') {
            TikiLib::lib('header')->add_jq_onready('$("time.timeago").timeago();');
            return '<time class="timeago" datetime="' . TikiLib::date_format('c', $string, false, 5, false) . '">' . $date . '</time>';
        } else {
            return $date;
        }
    }

    /**
     * @param string $string
     * @param string $intro
     * @param string $same if set to 'n' will bypass timeago preferences. Useful when markup is illegal in date
     *
     * @return string
     */
    function smarty_modifier_tiki_short_datetime($string, $intro = '', $same = 'y')
    {
        global $prefs;
        $smarty = TikiLib::lib('smarty');
        $date = smarty_modifier_tiki_date_format($string, $prefs['short_date_format']);
        $time = smarty_modifier_tiki_date_format($string, $prefs['short_time_format']);

        $intro = ! empty($intro) ? tra($intro) . ' ' : '';

        if ($prefs['jquery_timeago'] === 'y' && $same === 'y') {
            TikiLib::lib('header')->add_jq_onready('$("time.timeago").timeago();');
            return '<time class="timeago" datetime="' . TikiLib::date_format('c', $string, false, 5, false) . '">' . $date . ' ' . $time . '</time>';
        } elseif ($same != 'n' && $prefs['tiki_same_day_time_only'] == 'y' && $date == smarty_modifier_tiki_date_format(time(), $prefs['short_date_format'])) {
            //tra('on') tra('on:') tra('at') tra('at:')
            return str_replace(['on', 'On'], ['at', 'At'], $intro) . $time;
        } else {
            // if you change the separator do not forget to change the translation instruction in lib/prefs/short.php
            $time = $date . ' ' . $time;
            return $intro . ' ' . $time;
        }
    }

    public function smarty_modifier_tiki_short_time($string)
    {
        global $prefs;
        $smarty = TikiLib::lib('smarty');
        return smarty_modifier_tiki_date_format($string, $prefs['short_time_format']);
    }

    public function smarty_modifier_times($n1, $n2)
    {
        return $n1 * $n2;
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

    /**
     * smarty modifier tra
     * -------------------
     * Purpose: Translate an English string
     * @param string $content English string
     * @param string $lg      language - if not specify = global current language
     * @param bool   $unused
     * @param array  $args
     *
     * @return mixed|string
     */
    public function smarty_modifier_tra($content, $lg = '', $unused = false, $args = [])
    {
        require_once(__DIR__ . '/../init/tra.php');
        return tra($content, $lg, $unused, $args);
    }

     /*
    * Smarty plugin
    * -------------------------------------------------------------
    * Type:     modifier
    * Replaces 1 (=TRUE) with a 'x'. Anything else with '-'
    * used for example to output file permissions in
    * tiki-admin_security
    * -------------------------------------------------------------
    */
    public function smarty_modifier_truex($string)
   {
       if ((int) $string == 1) {
           return('x');
       }
       return('-');
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
     * Smarty nodifier ucfirst
     * -----------------------
     * Purpose: Make a string's first character uppercase
     * 
     * @param string      $string   The input string.
     * @return string               A string with the first character of string capitalized, if that character is an ASCII character in the range from "a" (0x61) to "z" (0x7a).
     */
    public function smarty_modifier_ucfirst($string)
    {
        return ucfirst($string);
    }

    /**
     * Smarty nodifier ucwords
     * -----------------------
     * Purpose: Uppercase the first character of each word in a string
     * 
     * @param string  $string     The input string.
     * @param string  $separator  Optional. contains the word separator characters.
     * @return string             A string with the first character of each word in string capitalized, if that character is an ASCII character between "a" (0x61) and "z" (0x7a)
     */
    public function smarty_modifier_ucwords($string, $separator = " \t\r\n\f\v")
    {
        return ucwords($string, $separator);
    }

    /**
     * Smarty nodifier urlencode
     * -------------------------
     * Purpose: URL-encodes string
     * 
     * @param string  $string     The string to be encoded.
     * @return string             A string in which all non-alphanumeric characters except -_. have been replaced with a percent (%) sign followed by two hex digits and spaces encoded as plus (+) signs.
     */
    public function smarty_modifier_urlencode($string)
    {
        return urlencode($string);
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
    
    // convert utf-8 to unicode
    public function smarty_modifier_utf8unicode($utf8_text)
    {
        $output = [ ];

        for ($pos = 0, $pos_strlen_utf8_text = strlen($utf8_text); $pos < $pos_strlen_utf8_text; $pos++) {
            $chval = ord($utf8_text[$pos]);

            $bytes = 0;
            if (( $chval >= 0x00 ) && ( $chval <= 0x7F )) {
                $bytes = 1;
                $outputval = $chval;    // Since 7-bit ASCII is unaffected, the output equals the input
            } else {
                for ($i = 5; $i > 0; $i--) {
                    if (($chval >> $i) == ( (pow(2, (8 - $i))) - 2)) {
                        $bytes = 7 - $i;
                        $outputval = $chval & ((2 ^ $i) - 1);
                    }
                }
            }

            if ($bytes !== 0) {
                if ($pos + $bytes - 1 < strlen($utf8_text)) {
                    while ($bytes > 1) {
                        $pos++;
                        $bytes--;

                        $outputval = $outputval * 0x40 + ( (ord($utf8_text[$pos])) & 0x3F );
                    }
                    if ($outputval != 0) {
                        $output[] = $outputval;
                    }
                }
            }
        }

        $htmloutput = "";

        foreach ($output as $unistr) {
            if ($bytes < 3) {
                $htmloutput .= "&#" . str_pad($unistr, 3, "0", STR_PAD_LEFT) . ';';
            } else {
                $htmloutput .= "&#" . $unistr . ';';
            }
        }
        return $htmloutput;
    }

    /**
     * Smarty modifier var_dump
     * ------------------------
     * Purpose: Dumps information about a variable
     * 
     * @param mixed $value
     * @return void
     */
    public function smarty_modifier_var_dump($value)
    {
        return var_dump($value);
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

    public function smarty_modifier_yesno($string)
    {
        switch ($string) {
            case 'y':
                return tra('Yes');
                break;

            case 'n':
                return tra('No');
                break;

            default:
                return $string;
        }
    }

    public function smarty_modifier_zone_is_empty($zoneName)
    {
        return zone_is_empty($zoneName);
    }
}

if (class_exists('Kint')) {
    class SmartyKint extends \Kint\Parser\Plugin
    {
        public function getTypes()
        {
            return ['integer', 'string', 'array', 'object'];
        }

        public function getTriggers()
        {
            return \Kint\Parser\Parser::TRIGGER_BEGIN;
        }

        public function parse(&$var, \Kint\Zval\Value &$o, $trigger)
        {
            if ($trigger === \Kint\Parser\Parser::TRIGGER_BEGIN) {
                // we begin the Kint dump
                $replace = preg_replace('/.*\[\'(.*)\']->value/', '$$1', $o->access_path);
                // if the path of the var is $_smarty_tpl->tpl_vars[...]->value then just use the smarty var name for the title
                if ($o->access_path !== $replace) {
                    $o->name = $replace;
                }
            }
        }
    }
}

/**
 * Class StyleType
 *
 * functions for converting numbers to different formats defined by css List-Style-Type
 *
 *
 */
class StyleType
{
    /**
     * Takes a positive integer and returns the equivalent alpha value.
     *
     * eg. The below returns a lower case alphabetic value for 90.
     * toAlpha(90,range('a','z')
     *
     * eg. The below returns a lower case greek letters for 128.
     * toAlpha(128,array('α', 'β', 'γ', 'δ', 'ε', 'ζ', 'η', 'θ', 'ι', 'κ', 'λ', 'μ', 'ν', 'ξ', 'ο', 'π', 'ρ', 'σ', 'τ', 'υ', 'φ', 'χ', 'ψ', 'ω'));
     *
     * @param $number int The number to be turned into characters
     *
     * @param $alphabet array set of letters
     *
     * @return string alpha representation of $number
     */
    public function toAlpha($number, $alphabet)
    {

        $count = count($alphabet);
        if ($number <= $count) {
            return $alphabet[$number - 1];
        }
        $alpha = '';
        while ($number > 0) {
            $modulo = ($number - 1) % $count;
            $alpha  = $alphabet[$modulo] . $alpha;
            $number = floor((($number - $modulo) / $count));
        }
        return $alpha;
    }


    /**
     *
     * Turns a positive integer into a uppercase roman numeral.
     * @param $number int The value to be converted into a roman numeral
     * @param $table array An array of roman numerals, (zero normally specified)
     *
     * @return string|int   Roman numeral of $number
     */
    public function numerals($number, $table = ['M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1])
    {
        $return = '';
        while ($number > 0) {
            foreach ($table as $rom => $value) {
                if ($number >= $value) {
                    $number -= $value;
                    $return .= $rom;
                    break;
                }
            }
        }
        return $return;
    }
}
